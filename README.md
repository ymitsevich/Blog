# Simple REST API

## Description

Simple rest API for demonstration Funker functional testing toolkit capabilities. Basically a short version of Blog
task, implementing several endpoint. See http://localhost/api/doc for the full list of endpoints.

## Requirements

* Docker desktop

## Run Unit Test

1. docker compose exec app php bin/console doctrine:database:create -e test -n
2. docker compose exec app php bin/console doctrine:schema:update -e test -f
3. docker compose exec app php vendor/bin/phpunit

## ToDo

1. Security: authentication, additional authorization etc.
2. Pagination