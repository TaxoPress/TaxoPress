#!/usr/bin/env bash

# If not in the `dev-workspace` directory, change to it
if [[ ! $(pwd) =~ .*dev-workspace$ ]]; then
  cd dev-workspace
fi

set -a
source .env
source ../tests/.env
set +a

DB_CONTAINER_NAME=${PROJECT_NAME}_tests_db
DB_LOGS_FILE="${PWD}/.cache/logs/mysql/general.log"

run_mysql_query() {
  docker exec -i $DB_CONTAINER_NAME bash -c "mysql -u root -prootpass -e '$1' 2>&1 | grep  -v \"Using a password\""
}

if [[ $1 == "off" ]]; then
  run_mysql_query "SET GLOBAL general_log = OFF;"
  echo "MySQL general log is disabled."
else
  run_mysql_query "SET GLOBAL general_log = ON;"
  echo "MySQL general log is enabled. Check the logs at ${DB_LOGS_FILE}"
fi
