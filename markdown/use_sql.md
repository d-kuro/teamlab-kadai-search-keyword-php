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
    + 10件

## テーブル構造

##### PAGE

| column | option |
| - | - |
| id | |
| title | |

##### ACTIVITY

| column | option |
| - | - |
| id | - |
| page_id | - |
| user_id | - |
| created | - |

##### USER

| column | option |
| - | - |
| id | - |
| name | - |

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