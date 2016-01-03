IMAGE_NAME = cursor-playground/image
CONTAINER_NAME = playground-site1-with-bugs

HOME = /shared

clean:
	docker rmi -f ${IMAGE_NAME} 2>/dev/null || true
	docker images | grep ${IMAGE_NAME} || true
	docker rm -f $$(docker ps -a -q) || true
	docker ps -a

build:
	docker build -t ${IMAGE_NAME} .

stop:
	docker rm -f ${CONTAINER_NAME} 2>/dev/null || true

run: stop
	docker run --name=${CONTAINER_NAME} -p 80:8080 -v $$PWD:${HOME} -ti -d ${IMAGE_NAME}

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
	docker exec ${CONTAINER_NAME} /bin/sh -c 'nohup http-server web/ -a 0.0.0.0 >/dev/null 2>&1 &'

server-stop:
	docker exec -ti ${CONTAINER_NAME} /bin/sh -c 'killall node || true'

.PHONY: clean build run configure assets server