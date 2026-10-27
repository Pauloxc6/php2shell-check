#!/usr/bin/env bash

docker run --rm -d -p 8080:8080 -v "$(pwd)":/app -w /app php:5.6-cli php -S 0.0.0.0:8080 router.php
docker run --rm -d -p 8081:8081 -v "$(pwd)":/app -w /app php:8.4-cli php -S 0.0.0.0:8081 router.php
docker run --rm -d -p 8082:8082 -v "$(pwd)":/app -w /app php:8.2-cli php -S 0.0.0.0:8082 router.php
docker run --rm -d -p 8083:8083 -v "$(pwd)":/app -w /app php:8.4.26-cli php -S 0.0.0.0:8083 router.php