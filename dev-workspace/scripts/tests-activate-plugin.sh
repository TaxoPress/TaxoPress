#!/usr/bin/env bash

# If not in the `dev-workspace` directory, change to it
if [[ ! $(pwd) =~ .*dev-workspace$ ]]; then
  cd dev-workspace
fi

set -a
source ../tests/.env
set +a

# Check if the plugin slug is set
if [ -z "$PLUGIN_SLUG" ]; then
  echo "The plugin slug is not set. Please set the PLUGIN_SLUG in the .env file."
  exit 1
fi

bash ./scripts/tests-wp-cli.sh plugin activate $PLUGIN_SLUG\/${PLUGIN_SLUG}.php
