#!/bin/sh

set -e
host="$1"
shift
user="$1"
shift
password="$1"

cd /var/www/app

echo "Execute mydb.sql"
until mysql -h"$host" -u"$user" -p"$password" mydb < database/sql/init/mydb.sql &> /dev/null
do
        sleep 1
        echo "Waiting for mysql(mydb.sql)"
done

echo "Execute alter.sql"
until mysql -h"$host" -u"$user" -p"$password" mydb < database/sql/alter.sql &> /dev/null
do
        sleep 1
        echo "Waiting for mysql(alter.sql)"
done

echo "MySQL is up - executing command"

echo "memory_limit = 2048M" >> /etc/php.ini

composer install
php artisan serve --port 8080 --host 0.0.0.0
