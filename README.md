# 自動プルリクエスト生成スクリプト

## 概要

このプロジェクトは、PHP で実装された自動プルリクエスト生成スクリプトです。指定された条件に基づいて、GitHub リポジトリに対して自動的にプルリクエストを作成します。

## 前提

- Claude Pro の契約
- git コマンドが利用可能
- PHP が利用可能（7.4 以上あれば多分問題ない）

## 使用方法

### 0. clone

対象プロジェクトの直下に clone してください

たとえば /project-root というディレクトリがある場合、以下のようになります。

```
/project-root
  /create-pull-request
```

create-pull-request ディレクトリは gitignore しておくことをおすすめします。

### 1. env の作成

env.example を参考に env を作成してください。

```bash
cp .env.example .env
```

### 2. env に必要な情報を記入

### 3. 以下の情報を .env ファイルに記入してください：

- API_KEY: Claude のパーソナルアクセストークン
- TARGET_BRANCH: プルリクエストの対象となるブランチ名

### 4. スクリプトを実行してください

```bash
php create-pull-request.php
```

### 5. logs ディレクトリにログが出力されます。

| ファイル名         | 内容                                               |
| ------------------ | -------------------------------------------------- |
| content.log        | Claude にリクエストした内容                        |
| diff.log           | 作業ブランチとマージ先ブランチとの差分             |
| pr_description.log | 実際に生成されたプルリクエストのディスクリプション |
| source_tree.log    | プルリクエストのソースツリー                       |
