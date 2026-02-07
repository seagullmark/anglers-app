# anglers-app

## セットアップ（clone から sail 起動まで）

1. 作業ディレクトリへ移動
2. リポジトリを clone して移動
3. `.env` を作成
4. `vendor` を先に作成（Docker で Composer 実行）
5. コンテナ起動
6. 依存インストールと初期化

### コマンド

```bash
cd <作業ディレクトリ>

git clone https://github.com/seagullmark/anglers-app.git
cd anglers-app

cp .env.example .env

# vendor がないので sail コマンドは使えない
# Docker で Composer を実行して vendor を作成
docker run --rm \
  -v "$PWD":/app \
  -w /app \
  composer:2 \
  composer install

# コンテナ起動
docker compose up -d --build

# 以降は sail を使う
./vendor/bin/sail npm install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail npm run dev
```

## ライセンス

本リポジトリのライセンスは `LICENSE` を参照してください。
依存パッケージのライセンスは `vendor/` 配下に含まれています。
