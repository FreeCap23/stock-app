CONTAINER_NAME = php-app

PHP_UNIT_BIN = vendor/bin/phpunit
TEST_FILES = ingest_data_test.php parse_json_test.php

all: build_container up update_composer

up:
	docker compose up -d

build_container:
	docker compose build

update_composer:
	docker compose exec $(CONTAINER_NAME) composer update

test:
	docker compose exec $(CONTAINER_NAME) $(PHP_UNIT_BIN) $(TEST_FILES)
