SHELL := /bin/bash

all: build

help:
	@echo "Available targets:"
	@awk '/^[a-zA-Z0-9_-]+:/ {print "  " $$1}' $(MAKEFILE_LIST)

.PHONY: build buildpush update

build:
	source ./.env && \
	CONTAINER_NAME=$${CONTAINER_NAME} \
	IMAGE_NAME=$${IMAGE_NAME} \
	BIN_PATH=$${BIN_PATH} \
	CACHE_PATH=$${CACHE_PATH} \
	$${BIN_PATH}/exec build "$$@";

push:
	source ./.env && \
	CONTAINER_NAME=$${CONTAINER_NAME} \
	IMAGE_NAME=$${IMAGE_NAME} \
	BIN_PATH=$${BIN_PATH} \
	CACHE_PATH=$${CACHE_PATH} \
	$${BIN_PATH}/exec build-push "$$@";

update:
	source ./.env && \
	CONTAINER_NAME=$${CONTAINER_NAME} \
	IMAGE_NAME=$${IMAGE_NAME} \
	BIN_PATH=$${BIN_PATH} \
	CACHE_PATH=$${CACHE_PATH} \
	$${BIN_PATH}/exec update "$$@"
