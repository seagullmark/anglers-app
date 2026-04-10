# Laravel × FileMaker OData スキーマ管理 設計書

この資料は、
Laravel から FileMaker のスキーマ変更を管理するための設計書です。

目的は、
Laravel 標準の migration をそのまま FileMaker に流すことではありません。

`FileMaker 専用に汎用化した schema ツール`
を Laravel プロジェクトの中に持ち、
OData を使って

- テーブル作成
- フィールド作成
- フィールド変更

を安全に実行できるようにすることです。

## 1. 前提

### この設計でやりたいこと

- FileMaker 用に再利用できる schema 管理機能を作る
- FileMaker への OData 接続情報は `.env` で管理する
- 定義ファイルを Git 管理し、チームで同じ schema を共有する
- Laravel からコマンドで schema を適用できるようにする

### この設計でやらないこと

- Laravel 標準の `Schema::create()` を FileMaker にそのまま対応させること
- MySQL、PostgreSQL、SQLite と同じ抽象度で完全共通化すること
- FileMaker のレイアウト、TO、リレーション、値一覧まで自動生成すること

## 2. 基本方針

構成は次の 3 層に分けます。

1. `Core`
   migration の読み込み、順序管理、実行済み管理、`pretend` 実行を担当する
2. `Driver`
   FileMaker OData への実際の通信を担当する
3. `Definition`
   テーブルやフィールドの定義ファイルを持つ

この分け方にすると、
schema 管理の流れは共通化しつつ、
FileMaker 固有の OData 仕様は Driver に閉じ込められます。

## 3. 対応範囲

v1 で対応する操作は次のとおりです。

- `create_table`
- `add_fields`
- `delete_field`
- `create_index`
- `delete_index`
- `change_field`

### `change_field` の考え方

ここは重要です。

FileMaker OData の公式 schema 操作では、
`テーブル作成`
`フィールド追加`
`フィールド削除`
`索引作成`
`索引削除`
は確認できますが、
一般的な RDB の `ALTER COLUMN` のような
`既存フィールドの定義変更`
をそのまま前提にしない方が安全です。

そのため、この設計では `change_field` を
`直接変更`
ではなく
`replace strategy`
として扱います。

つまり内部では次の流れにします。

1. 新しいフィールドを追加する
2. 必要ならデータ移送を行う
3. 旧フィールドは残す
4. 明示的に許可された場合だけ旧フィールドを削除する

## 4. ディレクトリ構成

```text
app/
  Console/
    Commands/
      FmSchemaApplyCommand.php
      FmSchemaStatusCommand.php
  FileMakerSchema/
    Contracts/
      SchemaDriver.php
    Drivers/
      FileMakerODataDriver.php
    Operations/
      AddFieldsOperation.php
      ChangeFieldOperation.php
      CreateIndexOperation.php
      CreateTableOperation.php
      DeleteFieldOperation.php
      DeleteIndexOperation.php
    DefinitionLoader.php
    MigrationRepository.php
    Migrator.php
    ODataClient.php
config/
  filemaker_schema.php
database/
  filemaker-schema/
    2026_04_10_000001_create_fishing_trip_tables.php
```

## 5. 環境変数

FileMaker の schema 操作用アカウントは、
通常のアプリ利用アカウントとは分けます。

`.env` には次の値を持たせます。

```env
FM_SCHEMA_ENABLED=true
FM_SCHEMA_PROTOCOL=https
FM_SCHEMA_HOST=your-filemaker-host
FM_SCHEMA_DATABASE=anglers
FM_SCHEMA_USERNAME=schema_user
FM_SCHEMA_PASSWORD=secret
FM_SCHEMA_ODATA_VERSION=v4
FM_SCHEMA_TIMEOUT=30
FM_SCHEMA_VERIFY_SSL=true
FM_SCHEMA_REPOSITORY_TABLE=_schema_migrations
```

### ポイント

- schema 操作は権限が強いので、専用アカウントを使う
- 本番環境では `VERIFY_SSL=true` を前提にする
- 通常のアプリ用 `DB_*` と分けることで責務を明確にする

## 6. 設定ファイル

`config/filemaker_schema.php` は次のような形を想定します。

```php
<?php

return [
    'enabled' => env('FM_SCHEMA_ENABLED', false),
    'protocol' => env('FM_SCHEMA_PROTOCOL', 'https'),
    'host' => env('FM_SCHEMA_HOST'),
    'database' => env('FM_SCHEMA_DATABASE'),
    'username' => env('FM_SCHEMA_USERNAME'),
    'password' => env('FM_SCHEMA_PASSWORD'),
    'odata_version' => env('FM_SCHEMA_ODATA_VERSION', 'v4'),
    'timeout' => (int) env('FM_SCHEMA_TIMEOUT', 30),
    'verify_ssl' => filter_var(env('FM_SCHEMA_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    'repository_table' => env('FM_SCHEMA_REPOSITORY_TABLE', '_schema_migrations'),
];
```

## 7. 主要クラスの責務

### `ODataClient`

- OData の URL を組み立てる
- 認証付き HTTP リクエストを送る
- JSON レスポンスとエラーを標準化する

### `SchemaDriver`

- schema 操作の抽象インターフェース
- Core から見た操作の入口

### `FileMakerODataDriver`

- `SchemaDriver` の FileMaker 実装
- 抽象的な操作を OData の endpoint と payload に変換する

### `DefinitionLoader`

- `database/filemaker-schema` の定義ファイルを読む
- migration ID と checksum を組み立てる

### `MigrationRepository`

- 実行済み migration を記録する
- 現在の batch を管理する

### `Migrator`

- 未実行 migration を順に適用する
- `pretend` 時は実行せず、予定操作だけ表示する
- 失敗時はそこで停止する

## 8. Driver インターフェース

```php
<?php

namespace App\FileMakerSchema\Contracts;

interface SchemaDriver
{
    public function createTable(array $definition): void;

    public function addFields(string $table, array $fields): void;

    public function deleteField(string $table, string $field): void;

    public function createIndex(string $table, array $index): void;

    public function deleteIndex(string $table, string $field): void;

    public function metadata(): array;

    public function ensureRepositoryTable(string $table): void;
}
```

ここでは
`migration の流れ`
ではなく
`schema 操作の最小単位`
だけを持たせます。

## 9. 定義ファイルの形式

定義ファイルは PHP 配列にします。

理由は次のとおりです。

- Laravel プロジェクト内で扱いやすい
- 条件分岐や補助関数が必要になっても対応しやすい
- JSON より表現力が高く、YAML より PHP 側で安全に扱いやすい

### 例

```php
<?php

return [
    'id' => '2026_04_10_000001_create_fishing_trip_tables',
    'description' => 'Create fishing trip tables',
    'operations' => [
        [
            'action' => 'create_table',
            'table' => 'fishing_trips',
            'fields' => [
                ['name' => 'trip_uuid', 'type' => 'string', 'length' => 36, 'primary' => true],
                ['name' => 'owner_user_id', 'type' => 'string', 'length' => 36],
                ['name' => 'trip_date', 'type' => 'date'],
                ['name' => 'start_at', 'type' => 'timestamp'],
                ['name' => 'end_at', 'type' => 'timestamp'],
                ['name' => 'river_name', 'type' => 'string', 'length' => 100],
                ['name' => 'point_name', 'type' => 'string', 'length' => 100],
                ['name' => 'tackle_name', 'type' => 'string', 'length' => 200],
                ['name' => 'memo', 'type' => 'text', 'length' => 2000],
                ['name' => 'created_at', 'type' => 'timestamp'],
                ['name' => 'updated_at', 'type' => 'timestamp'],
            ],
        ],
        [
            'action' => 'create_index',
            'table' => 'fishing_trips',
            'field' => 'owner_user_id',
        ],
        [
            'action' => 'create_table',
            'table' => 'fishing_trip_photos',
            'fields' => [
                ['name' => 'photo_uuid', 'type' => 'string', 'length' => 36, 'primary' => true],
                ['name' => 'trip_uuid', 'type' => 'string', 'length' => 36],
                ['name' => 'image', 'type' => 'container'],
                ['name' => 'caption', 'type' => 'string', 'length' => 500],
                ['name' => 'sort_order', 'type' => 'int'],
                ['name' => 'created_at', 'type' => 'timestamp'],
                ['name' => 'updated_at', 'type' => 'timestamp'],
            ],
        ],
    ],
];
```

## 10. 型マッピング

定義ファイルでは
FileMaker の生の型名を直接書きすぎず、
アプリ側の抽象型を使います。

その後 Driver 側で FileMaker OData 用の型へ変換します。

| 定義側 | FileMaker OData 側 |
| --- | --- |
| `string(length)` | `VARCHAR(length)` |
| `text(length)` | `VARCHAR(length)` |
| `int` | `INT` |
| `numeric` | `NUMERIC` |
| `date` | `DATE` |
| `time` | `TIME` |
| `timestamp` | `TIMESTAMP` |
| `container` | `BLOB` |

### 補足

- `text` は無制限 text としてではなく、長めの `VARCHAR` として扱う
- `container` は FileMaker OData では `BLOB` として作る
- 文字列長は Driver 側でデフォルト値を持ってもよい

## 11. `change_field` の仕様

### 目的

`change_field` は、
既存フィールドを安全に変更するための抽象命令です。

### 定義例

```php
[
    'action' => 'change_field',
    'table' => 'fishing_trips',
    'field' => 'memo',
    'target' => [
        'name' => 'memo_v2',
        'type' => 'text',
        'length' => 4000,
    ],
    'strategy' => 'replace',
    'copy_data' => false,
    'drop_old' => false,
]
```

### 実行ルール

- `strategy` は v1 では `replace` のみ対応
- `target` の新フィールドを追加する
- `copy_data` が必要なら、将来 `run_script` または別処理で対応する
- `drop_old` は `--allow-destructive` が付いたときだけ有効にする

### なぜこの方式か

FileMaker は通常の RDB と違い、
`ALTER COLUMN` を前提にした実装に寄せすぎると危険です。

そのため、変更操作も
`壊さず増やして切り替える`
方針に寄せます。

## 12. 実行履歴テーブル

実行済みの schema 定義は、
FileMaker 側の専用テーブルで管理します。

テーブル名は `_schema_migrations` を想定します。

### 必要フィールド

| フィールド名 | 型 | 用途 |
| --- | --- | --- |
| `migration_id` | Text | 実行済み migration の識別子 |
| `checksum` | Text | 定義内容のハッシュ |
| `batch_no` | Number | 同一実行グループ番号 |
| `applied_at` | Timestamp | 適用日時 |

### FileMaker 側に持つ理由

- どの FileMaker 環境に何が適用されたかをその環境自身が持てる
- 開発者ローカルの sqlite に依存しない
- ステージング、本番で状態を確認しやすい

## 13. 実行コマンド

### `php artisan fm:schema:status`

- 未実行 migration 一覧を表示する
- 実行済み migration を表示する

### `php artisan fm:schema:apply`

- 未実行 migration を順に適用する

### `php artisan fm:schema:apply --pretend`

- 実際には変更せず、実行予定だけ表示する

### `php artisan fm:schema:apply --allow-destructive`

- `delete_field`
- `delete_index`
- `change_field` の `drop_old`

のような破壊的操作を許可する

## 14. 実行フロー

1. `.env` と `config/filemaker_schema.php` を読み込む
2. OData 接続を初期化する
3. `_schema_migrations` の存在を確認する
4. なければ repository 用テーブルを作成する
5. 定義ファイルを ID 順に読み込む
6. 実行済み一覧と比較して未実行だけ抽出する
7. `--pretend` なら内容だけ表示する
8. 実行する場合は順に OData を呼ぶ
9. 成功した migration を repository に記録する
10. 失敗したらそこで止める

## 15. エラーハンドリング

### 基本方針

- 途中で失敗したら、その migration 以降は止める
- 失敗した migration は実行済みに記録しない
- エラー内容は endpoint、payload、FileMaker の応答を整理して表示する

### 注意点

OData 側の schema 変更は、
一般的な DB transaction のように
すべてを自動で巻き戻せる前提で考えない方がよいです。

そのため、
`1 migration = 小さく安全な変更`
に保つことが重要です。

## 16. セキュリティ方針

- schema 操作用の FileMaker アカウントを別に作る
- アプリ通常利用の認証情報と分離する
- `.env` で管理し、Git にコミットしない
- 本番では最小権限に近づける
- `--allow-destructive` を明示しない限り削除系を実行しない

## 17. 初回対象

このプロジェクトの教材では、
まず次の 2 テーブルを対象にします。

1. `fishing_trips`
2. `fishing_trip_photos`

これにより、

- 釣行の登録、修正、削除
- 釣行ごとの複数画像登録
- 一覧表示用の基本データ構造

を先に作れます。

## 18. まとめ

この設計のポイントは、
Laravel 標準 migration を FileMaker に無理やり合わせることではなく、
FileMaker OData に合った schema 管理レイヤーを別で持つことです。

特に重要なのは次の 3 点です。

- `Core` と `Driver` を分ける
- OData で確実に扱える操作を中心にする
- `change_field` は `replace strategy` で安全に扱う

これにより、
FileMaker 用としては十分に汎用的で、
かつ現実の運用で壊れにくい schema 管理にできます。
