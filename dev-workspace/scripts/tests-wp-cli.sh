#!/usr/bin/env bash

# If not in the `dev-workspace` directory, change to it
if [[ ! $(pwd) =~ .*dev-workspace$ ]]; then
  cd dev-workspace
fi

set -a
source ./.env
set +a

# Check if wp-cli container is running, if not start it
if ! docker compose -f docker/compose.yaml ps | grep -q "_tests_wpcli.*Up"; then
    echo "Starting wp-cli container..."
    docker compose -f docker/compose.yaml up -d wp-cli
fi

# Execute WP-CLI command and pass all arguments
echo "Running: wp $@"
docker compose -f docker/compose.yaml exec wp-cli wp "$@"
