#!/bin/sh

printenv | grep "REDIS_" > /etc/environment
service cron start

# 'service php8.0-fpm start' does not pass env variables to process.
/etc/init.d/php8.0-fpm start

# Hand off to the CMD
exec "$@"
