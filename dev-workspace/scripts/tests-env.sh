#!/usr/bin/env bash

TESTS_BASE_PATH=$(pwd)/tests
WP_CACHE=$(pwd)/dev-workspace/.cache/wordpress
MYSQL_CACHE=$(pwd)/dev-workspace/.cache/mysql
MAILHOG_CACHE=$(pwd)/dev-workspace/.cache/mailhog
DB_USER=testuser
DB_PASS=testpass
DB_HOST="127.0.0.1"
DB_NAME=testdb
WP_DOMAIN=localhost
WP_USER=admin
WP_PASS=admin

# If not in the `dev-workspace` directory, change to it
if [[ ! $(pwd) =~ .*dev-workspace$ ]]; then
  cd dev-workspace
fi

set -a
source ./.env
set +a

if [[ $# -eq 0 ]] || [[ $1 == "-h" ]]; then
  echo "Usage: $0 [up|stop|down|clenaup|refresh|info|bootstrap]"
  exit 1
fi

tests_up() {
  echo "Starting..."
  docker compose -f docker/compose.yaml up wp-cli
}

tests_stop() {
  echo "Stopping..."
  docker compose -f docker/compose.yaml stop wp-cli wordpress db mailhog
}

tests_down() {
  echo "Shutting down..."
  docker compose -f docker/compose.yaml down wp-cli wordpress db mailhog
}

tests_cleanup() {
  echo "Cleaning up..."
  docker compose -f docker/compose.yaml down -v wp-cli wordpress db mailhog
}

get_wp_port() {
  docker compose -f docker/compose.yaml port wordpress 80 | cut -d: -f2
}

get_db_port() {
  docker compose -f docker/compose.yaml port db 3306 | cut -d: -f2
}

get_mailhog_port_8025() {
  docker compose -f docker/compose.yaml port mailhog 8025 | cut -d: -f2
}

get_mailhog_port_1025() {
  docker compose -f docker/compose.yaml port mailhog 1025 | cut -d: -f2
}

tests_info() {
  WP_PORT=$(get_wp_port)
  DB_PORT=$(get_db_port)
  MAILHOG_PORT_8025=$(get_mailhog_port_8025)
  MAILHOG_PORT_1025=$(get_mailhog_port_1025)

  echo "=============================================="
  echo "🌐 WordPress Information"
  echo "=============================================="
  echo "📌 Site URL:       http://$WP_DOMAIN:$WP_PORT"
  echo "📌 Admin URL:      http://$WP_DOMAIN:$WP_PORT/wp-admin"
  echo "📌 Login:          $WP_USER / $WP_PASS"
  echo "📌 Root Directory: $WP_CACHE"
  echo "📌 Container ID:   $(docker compose -f docker/compose.yaml ps -q wordpress)"
  echo ""
  echo "=============================================="
  echo "🗄️  Database Information"
  echo "=============================================="
  echo "📌 Connection:     mysql://$DB_USER:$DB_PASS@$DB_HOST:$DB_PORT/$DB_NAME"
  echo "📌 Database:       $DB_NAME"
  echo "📌 Host:           $DB_HOST:$DB_PORT"
  echo "📌 Credentials:    $DB_USER / $DB_PASS"
  echo "📌 Data Directory: $MYSQL_CACHE"
  echo "📌 Container ID:   $(docker compose -f docker/compose.yaml ps -q db)"
  echo ""
  echo "=============================================="
  echo "📧 Mail Information"
  echo "=============================================="
  echo "📌 Web Interface:  http://$WP_DOMAIN:$MAILHOG_PORT_8025"
  echo "📌 SMTP Server:    smtp://$WP_DOMAIN:$MAILHOG_PORT_1025"
  echo "=============================================="
}

bootstrap() {
  if [[ ! -f $TESTS_BASE_PATH/.env ]]; then
    cp "$TESTS_BASE_PATH/.env.example" "$TESTS_BASE_PATH/.env"
  fi

  # Update the .env file with the correct values
  if [[ -f .env ]]; then
    WP_PORT=$(get_wp_port)
    DB_PORT=$(get_db_port)

    sed -i.bak "s|WORDPRESS_ROOT_DIR=.*|WORDPRESS_ROOT_DIR=\"${WP_CACHE}\"|" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s|WORDPRESS_DB_URL=.*|WORDPRESS_DB_URL=mysql://$DB_USER:$DB_PASS@$DB_HOST:$DB_PORT/$DB_NAME|" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s|WORDPRESS_DB_NAME=.*|WORDPRESS_DB_NAME=$DB_NAME|" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s|WORDPRESS_DB_USER=.*|WORDPRESS_DB_USER=$DB_USER|" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s|WORDPRESS_DB_PASSWORD=.*|WORDPRESS_DB_PASSWORD=$DB_PASS|" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s|WORDPRESS_DB_HOST=.*|WORDPRESS_DB_HOST=$DB_HOST:$DB_PORT|" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s|WORDPRESS_URL=.*|WORDPRESS_URL=http://$WP_DOMAIN:$WP_PORT|" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s|WORDPRESS_DOMAIN=.*|WORDPRESS_DOMAIN=$WP_DOMAIN:$WP_PORT|" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s/WORDPRESS_ADMIN_USER=.*/WORDPRESS_ADMIN_USER=$WP_USER/" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
    sed -i.bak "s/WORDPRESS_ADMIN_PASSWORD=.*/WORDPRESS_ADMIN_PASSWORD=$WP_PASS/" "$TESTS_BASE_PATH/.env" && rm "$TESTS_BASE_PATH/.env.bak"
  fi
}

if [[ $1 == "up" ]]; then
  # Create the mailhog cache directory if it doesn't exist
  mkdir -p "$MAILHOG_CACHE/maildir"

  tests_up
fi

if [[ $1 == "stop" ]]; then
  tests_stop
fi

if [[ $1 == "down" ]]; then
  tests_down
fi

if [[ $1 == "cleanup" ]]; then
  tests_down
  rm -rf "$WP_CACHE" "$MYSQL_CACHE" "$MAILHOG_CACHE"
fi

if [[ $1 == "refresh" ]]; then
  tests_cleanup
  tests_up
fi

if [[ $1 == "info" ]]; then
  tests_info
fi

if [[ $1 == "bootstrap" ]]; then
  bootstrap
fi
