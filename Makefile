test:
	pwd && vendor/bin/phpunit --bootstrap vendor/autoload.php tests/src/*

test-up:
	make test-down && docker-compose -f ./tests/docker-compose.yaml up -d \
	&& sleep 15 && make test \
	&& make test-down

test-down:
	docker-compose -f ./tests/docker-compose.yaml down
