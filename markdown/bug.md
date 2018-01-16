# 確認している現象について

`startup.sh`にて`alter.sql`を実行しているが  
使用している`ADD INDEX`文は同じでも  
インデックスのカーディナリティが異なる状況が発生した

#### startup.sh
```bash
echo "Execute alter.sql"
until mysql -h"$host" -u"$user" -p"$password" mydb < database/sql/alter.sql &> /dev/null
do
        sleep 1
        echo "Waiting for mysql(alter.sql)"
done
```

#### alter.sql
```sql
ALTER TABLE mydb.activity ADD INDEX index_activity_userid_pageid(user_id,page_id);
```

## 正常なインデックス

```sql
mysql> show index from mydb.activity\G
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

## 異常なインデックス

```sql
mysql> show index from mydb.activity\G
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
  Cardinality: 100035
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

`user_id`の`Cardinality`値がなぜか10万になってしまう問題  
正常なインデックスが貼られた際の検索は10万件取得で1秒以下だが  
異常なインデックスが貼られた際は2.5秒程度になってしまう

## MySQLのカーディナリティについて

>MySQLにおいてはカーディナリティは計測されず、  
>精密さを犠牲にした高速処理のために推定しているらしい。  
>カーディナリティのパラメータは値を用いて何かを行うのではなく、  
>あくまで「指標」としての役割にとどまるので、  
>MySQLのテーブルアナライズは正確な値が出ていない…というのがカラクリのようでした。

なら手動で再アナライズを行ってみる

## ANALYZE TABLE

```sql
ANALYZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE
    tbl_name [, tbl_name] ...
```

上記コマンドで手動でアナライズが行えるらしい

>InnoDBは以下の条件に適合すると、ANALYZE TABLEを自動的に行う仕組みになっている。
>+ 前回インデックス統計情報を更新してから、テーブルの行数全体の1/16が更新された。
>+ 前回インデックス統計情報を更新してから、20億行以上更新された。

今回はテーブルの更新は伴いため手動でアナライズを行う  
`alter.sql`を以下のように更新

```sql
ANALYZE TABLE mydb.activity;
ALTER TABLE mydb.activity ADD INDEX index_activity_userid_pageid(user_id,page_id);
```

インデックスを付与する前にアナライズを実施  
できる限り正確なカーディナリティが推測されるように対応を行う

数回起動を繰り返し、インデックスを確認した結果  
正常なインデックスが付与されていることは確認