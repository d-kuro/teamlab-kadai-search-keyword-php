# PHPファイルの変更

## SQLの取り込み

作成したSQLを実行するように`PageUtility.php`を改修

```php
$result = DB::select(
            'SELECT  activity.user_id AS user_id,
                     user.name AS user_name,
                     activity.page_id AS page_id,
                     page.title AS page_title,
                     COUNT(activity.user_id) AS view_count
            FROM     user
            JOIN     activity ON user.id = activity.user_id
            JOIN     page ON page.id = activity.page_id
            WHERE    page.title LIKE ?
            GROUP BY activity.user_id,
                     user.name,
                     activity.page_id,
                     page.title
            ORDER BY activity.user_id ASC,
                     activity.page_id ASC', ["$keyword%"]);
```

## Viewに返すために配列形式に変換

`stdClass`を`Array`に変換する  
配列形式に変換することでViewの変更が不要になるため

```php
$userPageArray = json_decode(json_encode($result), true);
```