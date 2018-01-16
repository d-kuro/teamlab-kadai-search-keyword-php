# チューニング

## 現状の実行時間

```bash
$ time (cat temp.sql | mysql -u root -p mydb > /dev/null)
Enter password: 

real	0m2.289s
user	0m0.050s
sys	0m0.020s
```

実行時間は2回目と3回目で早かった方を記載  
(OS, `mysqld` のキャッシュに乗せてから計測するため)

## EXPLAINする

```sql
EXPLAIN
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
         user.name,
         page.id,
         page.title
ORDER BY user.id ASC,
         page.id ASC\G
```

```sql
*************************** 1. row ***************************
           id: 1
  select_type: SIMPLE
        table: activity
         type: ALL
possible_keys: NULL
          key: NULL
      key_len: NULL
          ref: NULL
         rows: 100035
        Extra: Using where; Using temporary; Using filesort
*************************** 2. row ***************************
           id: 1
  select_type: SIMPLE
        table: user
         type: eq_ref
possible_keys: PRIMARY
          key: PRIMARY
      key_len: 4
          ref: mydb.activity.user_id
         rows: 1
        Extra: Using where
*************************** 3. row ***************************
           id: 1
  select_type: SIMPLE
        table: page
         type: eq_ref
possible_keys: PRIMARY
          key: PRIMARY
      key_len: 4
          ref: mydb.activity.page_id
         rows: 1
        Extra: Using where
3 rows in set (0.00 sec)
```

`EXPLAIN`の結果、`activity`テーブルがフルスキャンされており  
非常に遅いことがわかる

## SQL文のチューニング

+ テーブルのJOIN順をレコード数が少ない順に変更

+ SQLのORDER BY句を  
  `activity.user_id`, `activity.page_id`を使用するように変更

+ `count(*)`の部分にカラム名を設定するように変更


```sql
SELECT   activity.user_id AS user_id,
         user.name AS user_name,
         activity.page_id AS page_id,
         page.title AS page_title,
         COUNT(activity.user_id) AS view_count
FROM     mydb.user
JOIN     mydb.activity ON user.id = activity.user_id
JOIN     mydb.page ON page.id = activity.page_id
WHERE    page.title LIKE '%'
GROUP BY activity.user_id,
         user.name,
         activity.page_id,
         page.title
ORDER BY activity.user_id ASC,
         activity.page_id ASC;
```

## activityテーブルにINDEXを貼る

インデックスの確認

```sql
SHOW INDEX FROM mydb.activity\G
```

```sql
mysql> SHOW INDEX FROM mydb.activity\G
*************************** 1. row ***************************
        Table: activity
   Non_unique: 0
     Key_name: PRIMARY
 Seq_in_index: 1
  Column_name: id
    Collation: A
  Cardinality: 100035
     Sub_part: NULL
       Packed: NULL
         Null: 
   Index_type: BTREE
      Comment: 
Index_comment: 
1 row in set (0.00 sec)
```

ORDER BY句で指定されている  
`activity.user_id`, `activity.page_id`に対して  
複合インデックスを設定

```sql
ALTER TABLE mydb.activity
ADD INDEX index_activity_userid_pageid(user_id, page_id);
```

インデックスを再度確認

```sql
mysql> SHOW INDEX FROM mydb.activity\G
*************************** 1. row ***************************
        Table: activity
   Non_unique: 0
     Key_name: PRIMARY
 Seq_in_index: 1
  Column_name: id
    Collation: A
  Cardinality: 100035
     Sub_part: NULL
       Packed: NULL
         Null: 
   Index_type: BTREE
      Comment: 
Index_comment: 
*************************** 2. row ***************************
        Table: activity
   Non_unique: 1
     Key_name: index_activity_userid_pageid
 Seq_in_index: 1
  Column_name: user_id
    Collation: A
  Cardinality: 202
     Sub_part: NULL
       Packed: NULL
         Null: YES
   Index_type: BTREE
      Comment: 
Index_comment: 
*************************** 3. row ***************************
        Table: activity
   Non_unique: 1
     Key_name: index_activity_userid_pageid
 Seq_in_index: 2
  Column_name: page_id
    Collation: A
  Cardinality: 100035
     Sub_part: NULL
       Packed: NULL
         Null: YES
   Index_type: BTREE
      Comment: 
Index_comment: 
3 rows in set (0.00 sec)
```

この`ADD INDEX`文を`after.sql`に記載する

## 再度EXPLAINする

```sql
EXPLAIN
SELECT   activity.user_id AS user_id,
         user.name AS user_name,
         activity.page_id AS page_id,
         page.title AS page_title,
         COUNT(activity.user_id) AS view_count
FROM     mydb.user
JOIN     mydb.activity ON user.id = activity.user_id
JOIN     mydb.page ON page.id = activity.page_id
WHERE    page.title LIKE '%'
GROUP BY activity.user_id,
         user.name,
         activity.page_id,
         page.title
ORDER BY activity.user_id ASC,
         activity.page_id ASC\G
```

```sql
*************************** 1. row ***************************
           id: 1
  select_type: SIMPLE
        table: activity
         type: index
possible_keys: index_activity_userid_pageid
          key: index_activity_userid_pageid
      key_len: 10
          ref: NULL
         rows: 100035
        Extra: Using where; Using index
*************************** 2. row ***************************
           id: 1
  select_type: SIMPLE
        table: user
         type: eq_ref
possible_keys: PRIMARY
          key: PRIMARY
      key_len: 4
          ref: mydb.activity.user_id
         rows: 1
        Extra: Using where
*************************** 3. row ***************************
           id: 1
  select_type: SIMPLE
        table: page
         type: eq_ref
possible_keys: PRIMARY
          key: PRIMARY
      key_len: 4
          ref: mydb.activity.page_id
         rows: 1
        Extra: Using where
3 rows in set (0.00 sec)
```

`activity`テーブルにてインデックスが使用されることで  
+ `Using temporary`の消失
  + テンポラリテーブルが使用されていないことの確認
+ `Using filesort`の消失
  + インデックスを用いてソートが行われていることの確認

## 実行時間の再計測

```bash
$ time (cat temp.sql | mysql -u root -p mydb > /dev/null)
Enter password: 

real	0m0.980s
user	0m0.070s
sys	0m0.010s
```

検索キーワードが指定された場合の実行時間

```sql
SELECT   activity.user_id AS user_id,
         user.name AS user_name,
         activity.page_id AS page_id,
         page.title AS page_title,
         COUNT(activity.user_id) AS view_count
FROM     mydb.user
JOIN     mydb.activity ON user.id = activity.user_id
JOIN     mydb.page ON page.id = activity.page_id
WHERE    page.title LIKE 'a%'
GROUP BY activity.user_id,
         user.name,
         activity.page_id,
         page.title
ORDER BY activity.user_id ASC,
         activity.page_id ASC;
```

```bash
$ time (cat temp.sql | mysql -u root -p mydb > /dev/null)
Enter password: 

real	0m0.952s
user	0m0.000s
sys	0m0.000s
```

## titleにインデックスを貼ることについて

`page`テーブルに以下のインデックスを作成して検証  
`USE INDEX`で使用を強制

+ `title`
+ `(id, title)`
+ `(titile, id)`

### title

```sql
*************************** 1. row ***************************
           id: 1
  select_type: SIMPLE
        table: page
         type: range
possible_keys: index_page_title
          key: index_page_title
      key_len: 767
          ref: NULL
         rows: 30442
        Extra: Using where; Using index; Using temporary; Using filesort
*************************** 2. row ***************************
           id: 1
  select_type: SIMPLE
        table: user
         type: ALL
possible_keys: PRIMARY
          key: NULL
      key_len: NULL
          ref: NULL
         rows: 10194
        Extra: Using join buffer (Block Nested Loop)
*************************** 3. row ***************************
           id: 1
  select_type: SIMPLE
        table: activity
         type: ref
possible_keys: index_activity_userid_pageid
          key: index_activity_userid_pageid
      key_len: 10
          ref: mydb.user.id,mydb.page.id
         rows: 1
        Extra: Using where; Using index
3 rows in set (0.00 sec)
```

`page`テーブルに`Using temporary`, `Using filesort`の出現  
`user`テーブルがフルスキャンになる

### (id, title)

```sql
*************************** 1. row ***************************
           id: 1
  select_type: SIMPLE
        table: activity
         type: range
possible_keys: index_activity_userid_pageid
          key: index_activity_userid_pageid
      key_len: 5
          ref: NULL
         rows: 50017
        Extra: Using where; Using index
*************************** 2. row ***************************
           id: 1
  select_type: SIMPLE
        table: user
         type: eq_ref
possible_keys: PRIMARY
          key: PRIMARY
      key_len: 4
          ref: mydb.activity.user_id
         rows: 1
        Extra: Using where
*************************** 3. row ***************************
           id: 1
  select_type: SIMPLE
        table: page
         type: ref
possible_keys: index_page_id_title
          key: index_page_id_title
      key_len: 4
          ref: mydb.activity.page_id
         rows: 1
        Extra: Using where; Using index
3 rows in set (0.00 sec)
```

複合インデックス`(id, title)`にて  
`key_len: 4`より`id`しか使用されていないことがわかる

### (title, id)

```sql
*************************** 1. row ***************************
           id: 1
  select_type: SIMPLE
        table: page
         type: range
possible_keys: index_page_title_id
          key: index_page_title_id
      key_len: 767
          ref: NULL
         rows: 30442
        Extra: Using where; Using index; Using temporary; Using filesort
*************************** 2. row ***************************
           id: 1
  select_type: SIMPLE
        table: user
         type: ALL
possible_keys: PRIMARY
          key: NULL
      key_len: NULL
          ref: NULL
         rows: 10194
        Extra: Using join buffer (Block Nested Loop)
*************************** 3. row ***************************
           id: 1
  select_type: SIMPLE
        table: activity
         type: ref
possible_keys: index_activity_userid_pageid
          key: index_activity_userid_pageid
      key_len: 10
          ref: mydb.user.id,mydb.page.id
         rows: 1
        Extra: Using where; Using index
3 rows in set (0.00 sec)
```

`title`のみにインデックスを貼った時と同じ

### 結果

`title`にインデックスを貼ると遅い

### おまけ

以下のクエリも試した結果  
検索ワード指定なしの際に速度が出なかったため実装見送り

#### 検索ワードあり

```sql
EXPLAIN
SELECT   user.id as user_id,
         user.name,
         extraction_page.id as page_id,
         extraction_page.title,
         count(user.id) as count
FROM (
  SELECT * FROM mydb.page
  WHERE    page.title LIKE 'a%'
) extraction_page
JOIN     mydb.activity ON extraction_page.id = activity.page_id
JOIN     mydb.user ON user.id = activity.user_id
GROUP BY activity.user_id,
         activity.page_id
ORDER BY activity.user_id ASC,
         activity.page_id ASC\G
```

```sql
*************************** 1. row ***************************
           id: 1
  select_type: PRIMARY
        table: activity
         type: index
possible_keys: index_activity_userid_pageid
          key: index_activity_userid_pageid
      key_len: 10
          ref: NULL
         rows: 100035
        Extra: Using where; Using index
*************************** 2. row ***************************
           id: 1
  select_type: PRIMARY
        table: user
         type: eq_ref
possible_keys: PRIMARY
          key: PRIMARY
      key_len: 4
          ref: mydb.activity.user_id
         rows: 1
        Extra: Using where
*************************** 3. row ***************************
           id: 1
  select_type: PRIMARY
        table: <derived2>
         type: ref
possible_keys: <auto_key0>
          key: <auto_key0>
      key_len: 4
          ref: mydb.activity.page_id
         rows: 10
        Extra: Using where
*************************** 4. row ***************************
           id: 2
  select_type: DERIVED
        table: page
         type: range
possible_keys: title,title_id
          key: title
      key_len: 767
          ref: NULL
         rows: 30442
        Extra: Using where; Using index
4 rows in set (0.01 sec)
```

```bash
$ time (cat temp.sql | mysql -u root -p mydb > /dev/null)
Enter password: 

real	0m0.876s
user	0m0.000s
sys	0m0.000s
```

#### 検索ワードなし

```sql
EXPLAIN
SELECT   user.id as user_id,
         user.name,
         extraction_page.id as page_id,
         extraction_page.title,
         count(user.id) as count
FROM (
  SELECT * FROM mydb.page
  WHERE    page.title LIKE '%'
) extraction_page
JOIN     mydb.activity ON extraction_page.id = activity.page_id
JOIN     mydb.user ON user.id = activity.user_id
GROUP BY activity.user_id,
         activity.page_id
ORDER BY activity.user_id ASC,
         activity.page_id ASC\G
```

```sql
*************************** 1. row ***************************
           id: 1
  select_type: PRIMARY
        table: activity
         type: index
possible_keys: index_activity_userid_pageid
          key: index_activity_userid_pageid
      key_len: 10
          ref: NULL
         rows: 100035
        Extra: Using where; Using index
*************************** 2. row ***************************
           id: 1
  select_type: PRIMARY
        table: user
         type: eq_ref
possible_keys: PRIMARY
          key: PRIMARY
      key_len: 4
          ref: mydb.activity.user_id
         rows: 1
        Extra: Using where
*************************** 3. row ***************************
           id: 1
  select_type: PRIMARY
        table: <derived2>
         type: ref
possible_keys: <auto_key0>
          key: <auto_key0>
      key_len: 4
          ref: mydb.activity.page_id
         rows: 15
        Extra: Using where
*************************** 4. row ***************************
           id: 2
  select_type: DERIVED
        table: page
         type: index
possible_keys: NULL
          key: title
      key_len: 767
          ref: NULL
         rows: 1509570
        Extra: Using where; Using index
4 rows in set (0.00 sec)
```

```bash
$ time (cat temp.sql | mysql -u root -p mydb > /dev/null)
Enter password: 

real	0m8.912s
user	0m0.050s
sys	0m0.020s
```

