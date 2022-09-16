#!/usr/bin/env bash

set -e

# Set environment variables from .env file
export $(grep -v '^#' .env | xargs)

# Build image
(cd .. && docker build \
--build-arg LIBRARY_RELEASE_NAME=$LIBRARY_RELEASE_NAME \
--build-arg RUN_INTEGRATION_TESTS=$RUN_INTEGRATION_TESTS \
--build-arg AZURE_DEVOPS_EXT_PAT=$AZURE_DEVOPS_EXT_PAT \
--build-arg DO_PUBLISH=$DO_PUBLISH \
-t "$PROJECT_NAME" -f build/Dockerfile .)

# Run a container to download artifacts
docker run --name "${PROJECT_NAME}-build" "$PROJECT_NAME" /bin/true

# Calculate test line coverage
docker cp "${PROJECT_NAME}-build":/publish/reports/coverage.txt .
LINE_COVERAGE=$(grep "Lines" -m 1 coverage.txt | cut -d " " -f 6 | sed "s/%//")
rm -f coverage.txt

# Update README.md file
VERSION_BADGE="![Version $LIBRARY_RELEASE_NAME](https://img.shields.io/badge/version-$LIBRARY_RELEASE_NAME-blue)"
sed -i "/Version /c $VERSION_BADGE" ../README.md
COVERAGE_BADGE="![Coverage $LINE_COVERAGE%](https://img.shields.io/badge/coverage-$LINE_COVERAGE%25-brightgreen)"
sed -i "/Coverage /c $COVERAGE_BADGE" ../README.md

docker rm "${PROJECT_NAME}-build"