test:
	pwd && vendor/bin/phpunit --bootstrap vendor/autoload.php tests/src/*

test-up:
	make test-down && docker-compose -f ./tests/docker-compose.yaml up -d \
	&& sleep 25 && make test \
	&& make test-down

test-down:
	docker-compose -f ./tests/docker-compose.yaml down
# 	&& make docker-clean && make docker-prune

push:
	make test-up && git push

docker-prune:
	docker image prune -a -f

docker-clean:
	docker rmi -f $$(docker images -a -q) || docker ps
