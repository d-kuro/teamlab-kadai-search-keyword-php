<?php

namespace App\Libs;

use DB;

class PageUtility
{
    /**
     * 検索
     *
     * @param $keyword
     * @return array
     */
    public static function findUserViewedPage($keyword){
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

        // JSONにして配列に戻すことでviewの変更が不要になる
        $userPageArray = json_decode(json_encode($result), true);

        return $userPageArray;
    }
}