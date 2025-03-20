#!/usr/bin/env bash

# Check for running containers matching CONTAINER_NAME but exclude test containers
docker ps --format "{{.Names}}" | grep "$CONTAINER_NAME" | grep -v "_tests_" | grep -q .

if [ $? -ne 0 ]; then
    echo ""
else
    # Get the container name, excluding test containers
    RUNNING_CONTAINER=$(docker ps --format "{{.Names}}" | grep "$CONTAINER_NAME" | grep -v "_tests_" | head -n 1)
    echo "$RUNNING_CONTAINER"
fi
