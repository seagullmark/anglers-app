# Laravel × FileMaker のサンプルデータ投入メモ

この資料は、
Laravel × FileMaker 開発で
「サンプルデータをどう入れるか」
を整理したメモです。

## 1. Seeder は自分で書くもの

Laravel の Seeder は、
framework が業務データを自動生成してくれる仕組みではありません。

開発者が

- どんなデータを
- 何件
- どういう関連で
- どの順番で

入れるかを、
自分で定義するための仕組みです。

つまり、
Seeder は
`開発用の初期データ投入スクリプト`
です。

## 2. このプロジェクトでは command を優先する

このプロジェクトでは、
通常の `DatabaseSeeder` よりも
専用 command の方が実用的です。

理由は次の通りです。

- FileMaker 側のユーザーに紐づけて入れたい
- 釣行だけ / 画像付き の切り替えをしたい
- 件数をその場で変えたい
- 一覧のスクロール確認用に何度も追加投入したい

そのため、
固定の初期データを 1 回だけ流す Seeder よりも、
実行時オプションを持つ command の方が扱いやすくなります。

## 3. 役割分担

このプロジェクトでは、
サンプルデータ投入の役割をこう分けます。

- schema 作成: `fm:schema:*`
- データ投入: `fm:seed:*`

整理すると、

- 構造は `OData`
- 中身は `Data API`

です。

## 4. 釣行サンプルデータ投入コマンド

釣行機能の確認用に、
専用 command を用意しています。

参照:

- [`FmSeedFishingTripsCommand.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Console/Commands/FmSeedFishingTripsCommand.php)
- [`FishingTripSampleFactory.php`](/Users/seagull_macmini4/dockerenv/anglers/app/Support/FishingTripSampleFactory.php)

### 基本コマンド

```bash
./vendor/bin/sail artisan fm:seed:fishing-trips
```

### できること

- デフォルトで釣行を `6件` 作る
- デフォルトで各釣行に画像を `1枚` 付ける
- `--count` で件数を変えられる
- `--photos` で 1 件あたりの画像枚数を変えられる
- `--without-photos` で画像なしにもできる
- `user_id` または `--user-email` で投入先 user を変えられる

### 例

```bash
./vendor/bin/sail artisan fm:seed:fishing-trips --count=3
./vendor/bin/sail artisan fm:seed:fishing-trips --count=12 --photos=2
./vendor/bin/sail artisan fm:seed:fishing-trips --without-photos
./vendor/bin/sail artisan fm:seed:fishing-trips <user_id>
./vendor/bin/sail artisan fm:seed:fishing-trips --user-email=test@example.com
```

## 5. 画像はダミーで自動生成する

この command の画像は、
手元の写真ファイルを使わなくてもよいように、
command 側でダミー JPEG を自動生成します。

つまり、

- 外部画像を選ばなくてよい
- 一覧確認用の「画像あり状態」をすぐ作れる
- フォーム確認と一覧確認をすぐ回せる

ということです。

実写真を使うための仕組みではなく、
あくまで確認用の placeholder 画像です。

## 6. 何度実行してもよい

この command は
`追加投入`
です。

既存データを消さず、
実行するたびに新しい釣行を追加します。

そのため、
一覧のスクロール読み込み確認には向いています。

### 繰り返し投入の例

```bash
./vendor/bin/sail artisan fm:seed:fishing-trips --count=20
./vendor/bin/sail artisan fm:seed:fishing-trips --count=20
```

このように何度も流して、
件数を増やせます。

## 7. 同じデータではないが、パターンは繰り返す

現在のサンプル生成は、
毎回完全ランダムではありません。

毎回変わるもの:

- `id`
- `photo id`
- `created_at`
- `updated_at`
- 画像ファイル

繰り返しやすいもの:

- `river_name`
- `point_name`
- `tackle_name`
- `memo`
- 時間帯のパターン

つまり、
スクロール確認には十分ですが、
内容は教材向けの定型パターンです。

必要なら将来、

- 既存件数を見て続きから生成する
- ランダム化する
- `--offset`
- `--random`

のように拡張できます。

## 8. 今回 command を採用した理由

今回の教材では、
目的が
「初期マスタ投入」
ではなく
「一覧・フォーム・画像・スクロール確認」
だからです。

そのため、
Laravel の一般論としては Seeder でもよいですが、
この教材では
`fm:seed:fishing-trips`
のような command の方が実務的です。
