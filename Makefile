vendor:
	docker-compose build --no-cache

.PHONY: tests
tests:
	./vendor/bin/phpunit -c tests
