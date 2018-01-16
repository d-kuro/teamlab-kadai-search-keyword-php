# 検証用SQL文の作成

## 検索仕様

`page`テーブルの`title`カラムを前方一致でキーワード検索   
キーワードが指定されないことを考慮する

##### SELECT

+ ユーザID
+ ページID
+ ページタイトル
+ 閲覧数

##### ORDER BY

+ 第一ソート
    + ユーザID : 昇順
+ 第二ソート
    + ページID : 昇順

##### LIMIT

+ 取得件数
    + ~~10件~~
    + 総件数の表示を行うために,  
      Controllerで10件抽出するという処理があり,  
      Controllerに変更は加えないという要件が存在するためLIMIT句は使用しない

## テーブル構造

##### PAGE

```sql
mysql> show create table mydb.page\G
*************************** 1. row ***************************
       Table: page
Create Table: CREATE TABLE `page` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1819775 DEFAULT CHARSET=utf8
1 row in set (0.00 sec)
```

##### ACTIVITY

```sql
mysql> show create table mydb.activity\G
*************************** 1. row ***************************
       Table: activity
Create Table: CREATE TABLE `activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  `page_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=131080 DEFAULT CHARSET=utf8
1 row in set (0.00 sec)
```

##### USER

```sql
mysql> show create table mydb.user\G
*************************** 1. row ***************************
       Table: user
Create Table: CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16389 DEFAULT CHARSET=utf8
1 row in set (0.00 sec)
```

## SQL

キーワード指定なしを想定

```sql
SELECT   user.id AS user_id,
         user.name,
         page.id AS page_id,
         page.title,
         count(*) AS count
FROM     mydb.page
JOIN     mydb.activity ON page.id = activity.page_id
JOIN     mydb.user ON user.id = activity.user_id
WHERE    page.title LIKE '%'
GROUP BY user.id,
         page.id,
         page.title
ORDER BY user.id ASC,
         page.id ASC;
```

## 実行時間の測定

### vimのインストール

コンテナ内でvimが使用できないためインストール

```bash
root@4abc05327f15:/# apt-get update
root@4abc05327f15:/# apt-get install vim
```

### クエリファイルの作成

```bash
root@4abc05327f15:/tmp# vi query.sql
```

### timeコマンドを使用して計測

```bash
$ time (cat temp.sql | mysql -u root -p mydb > /dev/null)
Enter password: 

real	0m2.289s
user	0m0.050s
sys	0m0.020s
```

`real`の値が実行時間