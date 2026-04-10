# FileMaker Container 画像表示の安定化設計

この資料は、
FileMaker の container 画像が
ブラウザの戻る・進むでリンク切れし、
`502 Bad Gateway` になる問題に対する
設計方針をまとめたものです。

今回は設計のみを残し、
実装は別ブランチで行います。

## 1. 何が起きているか

現在の画像表示は、
FileMaker から取得した container の URL を
暗号化して Laravel の route に載せ、
`/container/{path}` で proxy する形です。

流れはこうです。

1. FileMaker の container URL を取得する
2. `MyUtil::getContainerUrl()` で暗号化 URL に変換する
3. Vue はその URL を `<img :src>` に入れる
4. `ContainerController` が元の URL に対して `GET` する

この方式では、
ブラウザの戻る・進むで過去の Inertia page が復元されたときに、
古い container URL を再利用してしまいます。

その結果、
上流の FileMaker 取得に失敗し、
Laravel 側では `502 Bad Gateway` になります。

## 2. なぜ起きるのか

### 事実

- FileMaker Data API は access token を使って database session を表現する
- access token は、最後の呼び出しから一定時間で失効する
- FileMaker Server には `Authenticated Stream` の cookie check 設定がある
- 現在のアプリは、画像表示時に「その時点の FileMaker container URL」をブラウザへ持たせている

### 推論

Claris の公開情報と現在の挙動から見ると、
問題の本質は
`FileMaker の container URL を永続リンクとして扱っていること`
にあります。

つまり、
画像 URL は
`アプリ側の安定したリソース ID`
ではなく、
`その時点でたまたま取得できた FileMaker 側の参照`
にすぎません。

そのため、
ブラウザ履歴に残った古い page を再表示すると、
古い参照を再利用して失敗します。

## 3. この問題に対する判断

### 採用しない方針

#### FMS のセキュリティレベルを落とす

`Authenticated Stream` の cookie check を弱めて回避する案は採用しません。

理由:

- 問題の本質は URL 設計であり、FMS の保護設定ではない
- セキュリティ設定を弱めると、別のリスクを増やす
- 教材としても「サーバ設定を緩めて回避する」は避けたい

#### 502 時にページ再読み込みでごまかす

これも採用しません。

理由:

- 根本原因が残る
- 戻る・進むのたびに不安定になる
- 学習上も悪い設計になる

## 4. 採用する方針

### 方針の要点

FileMaker の container 生 URL を、
ブラウザに長く持たせないようにします。

代わりに、
アプリ側で安定した route を持ちます。

### 具体像

- `UserPhoto` は `user-photos/{photo}/image`
- `FishingTripPhoto` は `fishing-trip-photos/{photo}/image`

のように、
`モデル ID ベースの route`
で画像を返すようにします。

Vue 側は
FileMaker の生 URL ではなく、
この Laravel route を受け取ります。

## 5. 新しい画像表示フロー

### UserPhoto

1. `UserPhoto` モデルの accessor が
   `route('user-photos.image', $this->id)` を返す
2. Vue はその URL を `<img :src>` に使う
3. リクエストごとに Laravel が `UserPhoto` を取得する
4. その時点の最新 container 参照を使って FileMaker から画像を取得する
5. 画像をレスポンスとして返す

### FishingTripPhoto

1. `FishingTripPhoto` モデルの accessor が
   `route('fishing-trip-photos.image', $this->id)` を返す
2. Vue はその URL を `<img :src>` に使う
3. リクエストごとに Laravel が `FishingTripPhoto` を取得する
4. 親 `FishingTrip` に対する認可を確認する
5. その時点の最新 container 参照を使って FileMaker から画像を取得する
6. 画像をレスポンスとして返す

## 6. この設計で変わること

### 変えるもの

- `MyUtil::getContainerUrl()` に FileMaker の生 URL を渡す設計
- `/container/{encrypted-path}` に依存した画像表示
- モデル accessor が「外部 URL の暗号化結果」を返す実装

### 変えないもの

- FileMaker container への upload 処理
- `tmp -> Intervention Image -> FileMaker container` の保存フロー
- Vue 側の `<img :src="...">` の基本形
- Inertia + Vue のページ構造
- `Policy` / `modId` / CRUD の流れ

## 7. 期待できる効果

- 戻る・進むで古い page が出ても、画像取得時に最新の参照を取り直せる
- FileMaker の一時的な参照をブラウザ履歴へ直接残さない
- FMS 側のセキュリティ設定を弱めずに済む
- `UserPhoto` と `FishingTripPhoto` で同じ設計にそろえられる

## 8. 注意点

### 認可

画像 route は必ず認可を通します。

- `UserPhoto`: ログインユーザー本人の画像だけ返す
- `FishingTripPhoto`: 親 `FishingTrip` の `Policy` を基準に返す

### 上流取得の方式

最初の実装では、
現在と同様に Laravel 側で proxy します。

ただし、
もし FileMaker 側の streaming URL が
cookie や session に強く依存して不安定なら、
次の段階で
`認証付き取得`
へ寄せることを検討します。

ここは実装時に切り分けます。

## 9. 影響範囲

実装時に主に触る候補は次のとおりです。

- [`MyUtil.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Mylib/MyUtil.php)
- [`ContainerController.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Http/Controllers/ContainerController.php)
- [`UserPhoto.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Models/UserPhoto.php)
- [`FishingTripPhoto.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Models/FishingTripPhoto.php)
- `routes/web.php`
- 必要なら画像配信用の専用 controller

## 10. このプロジェクトでの結論

この問題は、
`FMS の設定を緩める問題`
ではなく、
`画像 URL をどう設計するかの問題`
として扱います。

そのため、
このプロジェクトでは次を採用します。

- FMS のセキュリティレベルは下げない
- FileMaker の生 container URL をブラウザへ長く持たせない
- モデル ID ベースの安定 route に置き換える
- 画像取得は Laravel 側で毎回最新参照を解決する

## 参考

- [Claris Data API: Log in to a database session](https://help.claris.com/en/data-api-guide/content/log-in-database-session.html)
- [Claris Data API: Write FileMaker Data API calls](https://help.claris.com/en/data-api-guide/content/write-data-api-calls.html)
- [Claris Data API: Upload container data](https://help.claris.com/en/data-api-guide/content/upload-container-data.html)
- [Claris FileMaker Server Admin API](https://help.claris.com/en/connect-reference/content/filemaker_admin.html)
