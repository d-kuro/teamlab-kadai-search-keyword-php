# チームラボ選考課題

このリポジトリで動作するサンプルアプリケーションのキーワード検索機能を、  
可能な限りチューニングして結果が速く表示されるようにしてください  
※ サンプルは何もしないとまともに動作しません

## 検索仕様

<b>page</b> テーブルの <b>title</b> カラムを前方一致でキーワード検索し、

* ユーザID
* ユーザ名
* ページID
* ページタイトル
* 閲覧数

を 第1ソート：**ユーザID(昇順)**、第2ソート：**ページID(昇順)** で10件取得してください

## 制限

* 表示される項目や結果の順番が変わらないこと
* memcached等、キャッシュサーバを用いないこと
* controllerパッケージは修正しないこと
* キーワード「あり/なし」どちらの場合も考慮すること

## 評価方法

* チューニングした結果の検索速度を評価対象とします
    * 公平を期すため弊社で用意した計測用サーバで速度の検証をします
    * 計測用サーバのメモリは<b>2G</b>のものを用意しています

## 提出方法

* このリポジトリをクローンし、ご自身のgithub.com にプッシュしてリポジトリのURLを提出してください
    * 【例】https://github.com/team-lab/teamlab-kadai-search-keyword-php
    * 差分が分かるようにこのサンプルのコミットと自分がチューニングしたコミットは分けてください
    * README.md ファイルを作成し、チューニングした内容を説明してください

## サンプルアプリケーションの説明

### フォルダ構成(一部抜粋)
```
.
├── .env                        ・・・設定ファイル
├── Dockerfile
├── app
│   ├── Http
│   │   ├── Controllers         ・・・コントローラー
│   │   │   ├── Controller.php
│   │   │   └── IndexController.php
│   │   └── Models              ・・・モデル
│   │       ├── Activity.php
│   │       ├── Page.php
│   │       └── User.php
│   └── Libs                    ・・・ユーティリティ
│       └── PageUtility.php
├── composer.json
├── config
│   └── app.php
├── database
│   └── sql
│       ├── alter.sql           ・・・修正用SQL
│       ├── config
│       │   └── my.cnf          ・・・MySQL設定
│       └── init
│           └── mydb.sql        ・・・DB初期化ファイル
├── docker-compose.yml
├── package.json
├── public
│   └── index.php
├── resources
│   └── views                   ・・・ビュー
│       ├── index.blade.php
│       └── page.blade.php
├── routes
│   └── web.php
└── startup.sh                  ・・・初期化スクリプト
```

### 開発環境

* 言語: PHP 7.1
* フレームワーク: Laravel 5.5
* データベース: MySQL
* コンテナ: Docker

### データベース構成

![er](https://user-images.githubusercontent.com/342957/31817043-7d1a2040-b5cd-11e7-928d-205952d75b35.png)

* page 150万件
   * ページ情報を格納するテーブルです。
* user 1万件
   * ユーザ情報を格納するテーブルです。
* activity 10万件
   * ユーザの閲覧履歴を格納するテーブルです。

### ローカル環境構築

#### 1. 課題ソースコードクローン
* Macbook: Dockerはデフォルト“/Users”, “/Volumes”, “/tmp”, “/private”のディレクトリを参考できるので、その下においてください。
```
git clone https://github.com/team-lab/teamlab-kadai-search-keyword-php.git
```

#### 2. Docker インストール手順

* Macbook: Docker for Macのインストール
    * 以下のURLより Docker for Mac をダウンロードしてインストールします
    * https://download.docker.com/mac/stable/Docker.dmg
* Window: Docker for Windowのインストール
    * 以下のURLより Docker for Mac をダウンロードしてインストールします
    * https://download.docker.com/win/stable/Docker%20for%20Windows%20Installer.exe
* Docker インストールした後、動作確認方法<br>
 
```
docker --version
docker-compose --version
docker-machine --version
```

エラーが出なければ、Dockerのインストールは成功です！

#### 3. アプリケーションの起動

課題ソースコードのディレクトリで下記コマンドを実行してください
```
docker-compose up
```
※起動毎に「mydb.sql」「alter.sql」が実行されます。

```
app_1  | Laravel development server started: <http://0.0.0.0:8080>
```
が表示されたら、ブラウザで http://localhost:8080 確認。
