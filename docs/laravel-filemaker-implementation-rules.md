# Laravel × FileMaker 実装ルール

この資料は、
このプロジェクトで Laravel × FileMaker を実装するときの
基本ルールをまとめたものです。

設計や実装で迷ったときは、
まずこのルールに合わせます。

## 1. 主キーと外部キーの命名規則

主キーと外部キーは、
Laravel のデフォルト命名に合わせます。

### ルール

- 主キーは `id`
- 外部キーは `<単数形モデル名>_id`

### 例

- `users.id`
- `fishing_trips.id`
- `fishing_trip_photos.id`
- `fishing_trips.user_id`
- `fishing_trip_photos.fishing_trip_id`

### やらないこと

- 主キーを `trip_uuid` や `photo_uuid` にする
- 外部キーを `owner_user_id` や `trip_uuid` にする

### 補足

値そのものは UUID でもよいです。
ただし、
`カラム名`
は Laravel のデフォルトに寄せます。

つまり、

- カラム名は `id`
- 値は `Get(UUID)` や `Str::uuid()` でもよい

という考え方です。

## 2. FileMaker モデルの作成ルール

FileMaker 用モデルは、
通常の `Model` ではなく
`FMModel` ベースで作ります。

### ルール

生成するときは `--filemaker` を付けます。

```bash
./vendor/bin/sail artisan make:model FishingTrip --filemaker
./vendor/bin/sail artisan make:model FishingTripPhoto --filemaker
```

### 理由

- `gearbox-solutions/eloquent-filemaker` の `FMModel` を使うため
- 既存実装とそろえるため
- FileMaker 接続前提のモデルとして明示するため

## 3. 日付 field の扱い

日付 field の書き込みは、
Laravel の通常属性として扱います。

### ルール

日付 field への書き込みは次のどれかで整形します。

- `Carbon`
- `DateTimeInterface`
- `date` cast
- `datetime` cast
- set ミューテーター

### 方針

FileMaker だからという理由で、
Controller 側に独自変換を散らさないようにします。

モデル側で通常属性として扱える形に寄せます。

### 対象例

- `trip_date`
- `start_at`
- `end_at`

## 4. 画像登録の実装ルール

画像の登録処理は、
このプロジェクトで既に実装されている
プロフィール画像登録を基準にします。

### 参照元

- [`UserPhotoController.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Http/Controllers/UserPhotoController.php)
- [`ContainerRequest.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Http/Requests/ContainerRequest.php)
- [`UserPhoto.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Models/UserPhoto.php)

### ルール

- ファイル入力は `ContainerRequest` のように正規化する
- `file` と `file_base64` の両対応パターンを流用する
- 一度 `Storage` の tmp に保存してから加工する
- 加工後は `Illuminate\Http\File` にして FileMaker の container field に保存する
- 表示用 URL は accessor で返す

### 釣行画像で変える点

- `user_id` ではなく `fishing_trip_id` に紐づける
- 1 枚固定ではなく複数枚登録を前提にする
- `sort_order` と `caption` を扱う
- プロフィール画像用の `128x128 cover` をそのまま流用しない

## 5. 画像表示の実装ルール

画像の表示ロジックも、
このプロジェクトで既に実装されている
プロフィール画像表示を基準にします。

### 参照元

- [`UserPhoto.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Models/UserPhoto.php)
- [`MyUtil.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Mylib/MyUtil.php)
- [`ContainerController.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Http/Controllers/ContainerController.php)
- [`HandleInertiaRequests.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Http/Middleware/HandleInertiaRequests.php)

### ルール

- FileMaker の container 生 URL を Vue に直接渡さない
- Model accessor で表示用 URL に変換する
- 画像配信の安定化方針は [`filemaker-container-image-stability-design.md`](/Users/seagull_macmini4/dockerenv/anglers/docs/filemaker-container-image-stability-design.md) を優先する
- Vue 側は accessor で受けた URL をそのまま表示に使う

### 方針

表示ロジックは
`Model accessor -> Laravel route -> controller -> Vue`
の流れで統一します。

釣行画像でも、
この流れを崩さず実装します。

## 6. 実装時の優先順位

釣行機能を実装するときは、
次の優先順位でそろえます。

1. 命名規則を Laravel デフォルトに合わせる
2. FileMaker モデルは `--filemaker` で作る
3. 日付 field は通常属性としてモデル側で整える
4. 画像登録は既存のプロフィール画像実装を参考にする
5. 画像表示も既存のプロフィール画像表示ロジックを参考にする

## 7. 認可の実装ルール

この学習の主体である
`Policy` と `Gate` も、
Laravel の標準的な流儀で実装します。

### ルール

- 認可は Laravel の `Gate` / `Policy` を使う
- Controller や Vue に独自認可ロジックを散らさない
- モデル単位の認可は `Policy` を優先する
- 必要に応じて `authorize()`、`$this->authorize()` を使う
- 一覧取得の絞り込みと、更新可否の判定を分けて考える

### 方針

「誰が触れてよいか」は、
更新処理の中心にあるルールです。

そのため、
認可は場当たり的な `if` 文で増やさず、
Laravel 標準の置き場所にそろえます。

### 釣行機能での基本方針

- 一覧: 認証ユーザーなら他ユーザーの釣行も閲覧できる
- 編集・削除: 本人の釣行だけ許可する
- 更新: `FishingTripPolicy` で `user_id === auth()->id()` を判定する
- 削除: `FishingTripPolicy` で同様に判定する
- 画像操作: 親の `FishingTrip` に対する権限を基準に考える

## 8. 楽観的排他の実装ルール

楽観的排他は、
FileMaker の `Modification ID (modId)` を使って実装します。

### ルール

- 更新競合の判定は `modId` を使う
- 独自のバージョン field を増やして代用しない
- FileMaker 更新時は `modId` を含めて送る
- 競合時は FileMaker 側の不一致エラーを正しく扱う

### 方針

`gearbox-solutions/eloquent-filemaker` は、
`withModId()` と `setModId()` をサポートしています。

また、
取得したモデルには `modId` が自動でセットされるので、
更新時は画面から返ってきた `mod_id` を
`withModId($request->mod_id)->save()` に渡す前提で組みます。

ただし、
この教材では
`protected $withModId = true;`
を既定にはしません。

理由は、
更新リクエスト時にレコードを取り直すと、
その時点の最新 `modId` がモデルへ自動セットされるためです。

その状態で単に
`withModId()->save()`
すると、
「画面表示時に見ていた版」
ではなく
「更新時に再取得した版」
で保存してしまいます。

フォーム編集の楽観的排他では、
画面表示時に持っていた `modId` を
更新リクエストで返し、
その値を保存時に使うことを優先します。

### 釣行機能での基本方針

- 編集画面表示時に、対象レコードの `modId` を保持する
- 更新時はその `modId` を使って保存する
- `modId` が一致しなければ競合として扱う
- 競合時は「他のユーザーまたは別操作で先に更新された」と分かる形で返す

### 実装の流れ

1. 編集画面表示時に `FishingTrip` を取得する
2. 取得済みモデルに自動で入っている `modId` を Inertia の props に載せる
3. フロントの `useForm()` に `mod_id` として保持する
4. 更新時は `Policy` を通したうえで対象レコードを取得する
5. リクエスト値をモデルへ反映する
6. `withModId($request->string('mod_id'))` を付けて `save()` する
7. `FileMakerDataApiException` の code `306` を競合として扱う
8. 競合時は validation error か flash message で編集画面へ戻す

### 例

```php
use GearboxSolutions\EloquentFileMaker\Exceptions\FileMakerDataApiException;

$trip = FishingTrip::findOrFail($id);
$this->authorize('update', $trip);

$trip->trip_date = $request->date('trip_date');
$trip->start_at = $request->date('start_at');
$trip->end_at = $request->date('end_at');
$trip->river_name = $request->string('river_name');
$trip->point_name = $request->string('point_name');
$trip->tackle_name = $request->string('tackle_name');
$trip->memo = $request->string('memo');

try {
    $trip->withModId((string) $request->string('mod_id'))->save();
} catch (FileMakerDataApiException $e) {
    if ((int) $e->getCode() === 306) {
        throw ValidationException::withMessages([
            'mod_id' => '他の操作で先に更新されたため、再読み込みしてやり直してください。',
        ]);
    }

    throw $e;
}
```

### 注意

- `modId` は取得済みモデルに自動で入るので、通常は編集画面からその値を返せばよい
- 更新時に再取得したモデルの `getModId()` をそのまま使うだけでは、画面表示時点との競合検知にならない
- `protected $withModId = true;` は form edit の既定方針にはしない
- 事前に `getModId() !== request('mod_id')` を見るのは補助にはなるが、最終的な競合判定は `withModId($request->mod_id)->save()` と `306` で行う
- パッケージ標準の `modId` 対応は `update/save` 向けで、`delete()` にはそのままは乗っていない

### Issue #85 の整理

`gearbox-solutions/eloquent-filemaker` の
Issue #85 で `seagullmark` が指摘している論点は正しいものとして扱います。

### 正しい理解

- モデル取得時に `modId` が自動セットされること自体は正しい
- しかし、その自動セットされた最新 `modId` を更新時にそのまま使うだけでは、フォーム表示時点の版を比較したことにならない
- フォーム編集の楽観的排他では、画面表示時に保持した `mod_id` を更新時に返し、その値で `withModId($request->mod_id)->save()` する必要がある
- `getModId() !== $request->mod_id` の事前比較は補助として妥当
- 最終的な競合判定は FileMaker 側に `modId` を送って `306` を受ける形で行う

### このプロジェクトでの結論

- `seagullmark` の考え方を採用する
- `protected $withModId = true;` を安易に既定化しない
- 編集フォームでは hidden の `mod_id` を必須で持つ
- 更新処理では必ず request の `mod_id` を `withModId()` に渡す

## 9. Inertia v2 の通信ルール

Inertia v2 では、
画面からの送信処理を `axios` で組みません。

### ルール

- `axios` で CRUD を実装しない
- Inertia の機能で画面遷移と送信を組む
- フォーム送信は `useForm()` を優先する
- 必要な更新は Inertia の request lifecycle に乗せる

### 方針

このプロジェクトでは、
`Laravel + Inertia` の流れを崩さないことを優先します。

そのため、
画面保存のためだけに `axios` を直接呼ばず、
Inertia の標準的な流れでそろえます。

## 10. 釣行一覧の実装ルール

釣行一覧は、
画像を並べる一覧として実装します。

さらに、
一覧の追加読み込みは
Inertia v2 で実装された
スクロールでロードしていく方法で組みます。

### ルール

- 釣行一覧は画像付きカード一覧にする
- 一覧の追加読み込みは Inertia v2 の仕組みで実装する
- `axios` で独自に無限スクロールを組まない
- ページネーションと追加読み込みの責務は Laravel + Inertia の流れに乗せる

### 方針

この一覧は、
単純なテーブルではなく
`釣行画像を見せる一覧`
として設計します。

そのため、
初回ロードだけで全部を返さず、
スクロールに応じて追加読み込みできる形にします。

## 11. CSP 対応ルール

将来的に CSP 対応を入れる前提で実装します。

### ルール

- `unsafe-inline` 前提の実装をしない
- `unsafe-eval` 前提の実装をしない
- インライン script を増やさない
- イベント属性の直書きに寄せない
- 画面機能は Vue / Inertia の通常実装で組む

### 方針

後から CSP を入れるときに、
フロント全体を書き直す状態を避けます。

そのため最初から、

- `axios` を場当たり的に直書きしない
- インライン JavaScript を書かない
- ビルド設定や実装が `eval` に依存しない前提で考える

ようにします。

## 12. ルート変更時のルール

ルートを追加、変更したら、
Ziggy の生成ファイルも更新します。

### ルール

- `routes/web.php` を変更したら Ziggy を更新する
- フロントで `route()` を使う前提を崩さない

### 方針

Laravel 側の route 定義と、
フロント側の Ziggy 情報がずれると、
画面側だけ壊れたように見える状態が起きやすくなります。

そのため、
ルート変更後は Ziggy の再生成までを作業に含めます。

## 13. コマンド実行ルール

Laravel 系のコマンドは、
コンテナ内で実行する前提にします。

### ルール

- `artisan` は `./vendor/bin/sail artisan ...` で実行する
- PHP 系コマンドも原則 `sail` 経由で実行する
- ホスト環境の PHP 実行を前提にしない

### 方針

ローカルの PHP 環境差分で挙動がぶれないように、
このプロジェクトでは
`sail` を標準の実行入口にします。

## 14. このルールの目的

このルールの目的は、
FileMaker 固有の都合で実装がバラバラになるのを防ぐことです。

特に重要なのは次の 3 点です。

- Laravel の標準的な読み方を崩さない
- 既存実装を基準にして、似た処理を似た形で書く
- Controller に変換処理を寄せすぎない
