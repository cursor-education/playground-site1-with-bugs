IMAGE_NAME = cursor-playground/image
CONTAINER_NAME = playground-site1-with-bugs

HOME = /shared
PORT = 80

.PHONY: all

all: up clean build run configure assets server
dev: set-dev all

up:
	git pull --force

set-dev:
	$(eval PORT = 8080)

clean: clean-image clean-container

clean-all: clean
	docker rm -f $$(docker ps -a -q) || true
	docker ps -a

clean-image:
	docker rmi -f ${IMAGE_NAME} 2>/dev/null || true
	docker images | grep ${IMAGE_NAME} || true

clean-container:
	docker rm -f ${CONTAINER_NAME} 2>/dev/null || true
	docker ps -a

build: stop clean-container
	docker build -t ${IMAGE_NAME} .

stop: clean-container

run: stop
	docker run --name=${CONTAINER_NAME} \
		-p ${PORT}:8080 \
		-v $$PWD:${HOME} \
		-ti -d ${IMAGE_NAME}

ssh:
	docker exec -ti ${CONTAINER_NAME} bash

configure:
	docker exec -ti ${CONTAINER_NAME} bower install --allow-root --config.interactive=false
	docker exec -ti ${CONTAINER_NAME} npm install

assets:
	docker exec -ti ${CONTAINER_NAME} grunt

assets-watch:
	docker exec -ti ${CONTAINER_NAME} grunt watch-all

server: server-stop
	docker exec ${CONTAINER_NAME} /bin/sh -c 'nohup http-server web/ -p 8080 -a 0.0.0.0 >/dev/null 2>&1 &'

server-stop:
	docker exec -ti ${CONTAINER_NAME} /bin/sh -c 'killall node || true'