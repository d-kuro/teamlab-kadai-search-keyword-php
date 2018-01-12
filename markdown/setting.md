# DB設定

## 検証用にMySQLを永続化する
DBに`restart: always`を追記
```docker-compose.yml
services:
    db:
        restart: always
        image: mysql:5.6
        ports:
            - "3306:3306"
        volumes:
            - ./database/sql/init/:/docker-entrypoint-initdb.d
            - ./database/sql/config/:/etc/mysql/conf.d
        environment:
            MYSQL_DATABASE: mydb
            MYSQL_USER: docker
            MYSQL_PASSWORD: docker
            MYSQL_ROOT_PASSWORD: hoge
```

コンテナIDを確認する
```bash
$ docker ps
CONTAINER ID        IMAGE                             COMMAND                  CREATED              STATUS              PORTS                    NAMES
90fe64bbe81f        motikan2010/centos7_php7:latest   "/bin/sh -c 'sh /var…"   About a minute ago   Up About a minute   0.0.0.0:8080->8080/tcp   teamlabkadaisearchkeywordphp_app_1
4abc05327f15        mysql:5.6                         "docker-entrypoint.s…"   About a minute ago   Up About a minute   0.0.0.0:3306->3306/tcp   teamlabkadaisearchkeywordphp_db_1
```

`docker exec`コマンドで接続
```bash
$ docker exec -it 4abc05327f15 bash

root@4abc05327f15:/# mysql -u root -p
Enter password: 
Welcome to the MySQL monitor.  Commands end with ; or \g.
Your MySQL connection id is 3
Server version: 5.6.38 MySQL Community Server (GPL)

Copyright (c) 2000, 2017, Oracle and/or its affiliates. All rights reserved.

Oracle is a registered trademark of Oracle Corporation and/or its
affiliates. Other names may be trademarks of their respective
owners.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

mysql> 
```

## DB情報の確認

```bash
mysql> show databases;
+--------------------+
| Database           |
+--------------------+
| information_schema |
| mydb               |
| mysql              |
| performance_schema |
+--------------------+
4 rows in set (0.00 sec)

mysql> use mydb;
Reading table information for completion of table and column names
You can turn off this feature to get a quicker startup with -A

Database changed
mysql> show tables;
+----------------+
| Tables_in_mydb |
+----------------+
| activity       |
| page           |
| user           |
+----------------+
3 rows in set (0.00 sec)
```

## 文字コードの変更

このような文字化けが起こった場合に

```sql
+----+----+--------------------+-------------------------+
| id | id | title              | created                 |
+----+----+--------------------+-------------------------+
|  1 |  1 | ????????_2004?4?   | 2017-01-01 15:00:00.000 |
|  1 |  1 | ????????_2004?4?   | 2017-10-05 06:18:22.484 |
|  1 |  2 | ????/????_2002?12? | 2017-10-05 06:18:22.484 |
|  1 |  5 | ??????             | 2017-10-05 06:18:22.484 |
|  1 |  6 | Sandbox            | 2017-10-05 06:11:03.384 |
|  1 |  6 | Sandbox            | 2017-10-05 06:18:22.484 |
|  1 |  7 | Brion_VIBBER       | 2017-10-05 06:18:22.484 |
|  1 | 10 | ??                 | 2017-10-05 06:18:22.484 |
|  1 | 11 | ???                | 2017-10-05 06:18:22.484 |
|  1 | 12 | ???                | 2017-10-05 06:18:22.484 |
+----+----+--------------------+-------------------------+
10 rows in set (0.30 sec)
```

文字コードの確認

```sql
mysql> show variables like "chara%";
+--------------------------+----------------------------+
| Variable_name            | Value                      |
+--------------------------+----------------------------+
| character_set_client     | latin1                     |
| character_set_connection | latin1                     |
| character_set_database   | utf8                       |
| character_set_filesystem | binary                     |
| character_set_results    | latin1                     |
| character_set_server     | utf8                       |
| character_set_system     | utf8                       |
| character_sets_dir       | /usr/share/mysql/charsets/ |
+--------------------------+----------------------------+
8 rows in set (0.00 sec)
```

my.confに書き込み

```my.conf
[mysqld]
lower_case_table_names=1
collation-server = utf8_unicode_ci
init-connect='SET NAMES utf8'
character-set-server = utf8

# 以下追加
[client]
default-character-set=utf8
```

再起動

```bash
$ docker restart 4abc05327f15
```

再度確認する

```sql
mysql> show variables like "chara%";
+--------------------------+----------------------------+
| Variable_name            | Value                      |
+--------------------------+----------------------------+
| character_set_client     | utf8                       |
| character_set_connection | utf8                       |
| character_set_database   | utf8                       |
| character_set_filesystem | binary                     |
| character_set_results    | utf8                       |
| character_set_server     | utf8                       |
| character_set_system     | utf8                       |
| character_sets_dir       | /usr/share/mysql/charsets/ |
+--------------------------+----------------------------+
8 rows in set (0.00 sec)
```
