test:
	pwd && vendor/bin/phpunit --bootstrap vendor/autoload.php tests/src/*

test-docker:
	make test-down && docker-compose -f ./tests/docker-compose.yaml up -d \
	&& sleep 20 && make test \
	&& docker-compose -f ./tests/docker-compose.yaml down -d

test-down:
	docker-compose -f ./tests/docker-compose.yaml down
