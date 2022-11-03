#!/usr/bin/env bash

set -e

# Set environment variables from .env file
export $(grep -v '^#' .env | xargs)

# Build image
(cd .. && docker build \
--build-arg LIBRARY_RELEASE_NAME=$LIBRARY_RELEASE_NAME \
--build-arg RUN_INTEGRATION_TESTS=$RUN_INTEGRATION_TESTS \
-t "$PROJECT_NAME" -f build/Dockerfile .)