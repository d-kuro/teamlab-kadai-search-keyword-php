-- 修正SQL文
ANALYZE TABLE mydb.activity;
ALTER TABLE mydb.activity ADD INDEX index_activity_userid_pageid(user_id,page_id);