CONTAINER_NAME = php-app

PHP_UNIT_BIN = vendor/bin/phpunit
TEST_FILES = ingest_data_test.php parse_json_test.php

all: api_key build_container up update_composer

api_key:
	cp app.env.template app.env
	read -s -p "Input API key for Tiingo: " key; \
	sed --in-place -e "s/REPLACE_ME/$${key}/" app.env

build_container:
	docker compose build

up:
	docker compose up -d

update_composer:
	docker compose exec $(CONTAINER_NAME) composer update

test:
	docker compose exec $(CONTAINER_NAME) $(PHP_UNIT_BIN) $(TEST_FILES)
