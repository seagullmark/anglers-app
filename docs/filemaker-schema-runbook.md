# FileMaker Schema 実行手順

この資料は、
Laravel から FileMaker OData 用 schema ツールを実行する手順をまとめたものです。

このプロジェクトでは、
コンテナ内で実行する前提なので、
コマンドは `./vendor/bin/sail artisan ...` を使います。

## 1. 前提

実行前に次の条件を満たしている必要があります。

- FileMaker Server 側で OData アクセスが有効になっている
- schema 操作用の FileMaker アカウントが作成されている
- Laravel 側の `.env` に `FM_SCHEMA_*` が設定されている
- `database/filemaker-schema/` に定義ファイルがある

## 2. `.env` 設定

`.env` には次の値を設定します。

```env
FM_SCHEMA_ENABLED=true
FM_SCHEMA_PROTOCOL=https
FM_SCHEMA_HOST=anglers-fms
FM_SCHEMA_DATABASE=anglers
FM_SCHEMA_USERNAME=schema_user
FM_SCHEMA_PASSWORD=secret
FM_SCHEMA_ODATA_VERSION=v4
FM_SCHEMA_TIMEOUT=30
FM_SCHEMA_VERIFY_SSL=false
FM_SCHEMA_REPOSITORY_TABLE=_schema_migrations
```

### 補足

- `FM_SCHEMA_ENABLED=true` にしないと schema コマンドは実行できません
- `FM_SCHEMA_USERNAME` と `FM_SCHEMA_PASSWORD` は schema 操作用の専用アカウントを使います
- ローカル検証で自己署名証明書なら `FM_SCHEMA_VERIFY_SSL=false` にします
- 本番では可能な限り `FM_SCHEMA_VERIFY_SSL=true` を使います

## 3. 実行前の確認

まず、現在の schema 定義の状態を確認します。

```bash
./vendor/bin/sail artisan fm:schema:status
```

このコマンドでは次のことが確認できます。

- 定義ファイルが読み込まれているか
- どの migration が未実行か
- どの migration が実行済みか

## 4. まず `--pretend` で確認する

いきなり本実行せず、
最初は必ず `--pretend` で確認します。

```bash
./vendor/bin/sail artisan fm:schema:apply --pretend
```

このコマンドでは、
実際には FileMaker を変更せず、
次に何を実行するかだけ表示します。

ここで確認するポイントは次のとおりです。

- 対象テーブル名が正しいか
- 追加されるフィールド名が正しいか
- 予想外の削除操作が含まれていないか

## 5. 本実行

`--pretend` の内容に問題がなければ、本実行します。

```bash
./vendor/bin/sail artisan fm:schema:apply
```

成功すると、未実行 migration が順に適用され、
FileMaker 側の `_schema_migrations` テーブルに記録されます。

## 6. 破壊的操作がある場合

次のような操作は破壊的とみなします。

- `delete_field`
- `delete_index`
- `change_field` で `drop_old=true`

これらを実行する場合だけ、
明示的に `--allow-destructive` を付けます。

```bash
./vendor/bin/sail artisan fm:schema:apply --allow-destructive
```

### 注意

初回に追加した
`fishing_trips`
と
`fishing_trip_photos`
の定義は、
`create_table` と `create_index` だけなので、
通常は `--allow-destructive` は不要です。

## 7. よく使う実行順

通常の流れはこの順です。

```bash
./vendor/bin/sail artisan fm:schema:status
./vendor/bin/sail artisan fm:schema:apply --pretend
./vendor/bin/sail artisan fm:schema:apply
```

## 8. 初回適用対象

今の定義ファイルでは、次の 2 テーブルを作成します。

1. `fishing_trips`
2. `fishing_trip_photos`

これにより、

- 釣行本体の登録
- ユーザー所有データの管理
- 釣行ごとの複数画像登録

の土台となる schema を作れます。

## 9. エラー時の見方

実行時にエラーが出たら、まず次を確認します。

- `FM_SCHEMA_HOST`
- `FM_SCHEMA_DATABASE`
- `FM_SCHEMA_USERNAME`
- `FM_SCHEMA_PASSWORD`
- FileMaker Server 側の OData 有効化
- 対象アカウントの権限

また、
自己署名証明書の環境では
`FM_SCHEMA_VERIFY_SSL=true`
のままだと SSL エラーになることがあります。

## 10. まとめ

実行の基本は次の 3 段階です。

1. `status` で状態確認
2. `apply --pretend` で実行予定確認
3. `apply` で本実行

この順を守ることで、
FileMaker の schema 変更を安全に進めやすくなります。
