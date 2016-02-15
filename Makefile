IMAGE_NAME = cursor-playground/image
CONTAINER_NAME = playground-site1-with-bugs

HOME = /shared
PORT = 80

.PHONY: all

help:
	@echo " all \t\t to run all on production machine"
	@echo " dev \t\t to run all on development machine"
	@echo " clean \t\t to clean project-related containers & images"
	@echo " clean-all \t to clean all Docker containers & images"
	@echo " ssh \t\t to access into container"
	@echo " assets \t\t "
	@echo " assets-watch \t\t "

all: up clean build run assets
# configure 
dev: set-dev clean-all all

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

# configure:
	# docker exec -ti ${CONTAINER_NAME} bower install --allow-root --config.interactive=false
	# docker exec -ti ${CONTAINER_NAME} npm install

assets:
	docker exec -ti ${CONTAINER_NAME} grunt

assets-watch:
	docker exec -ti ${CONTAINER_NAME} grunt watch-all