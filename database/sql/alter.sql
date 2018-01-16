-- 修正SQL文
ALTER TABLE mydb.activity ADD INDEX index_activity_userid_pageid(user_id,page_id);