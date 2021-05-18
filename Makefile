.PHONY: help
.DEFAULT_GOAL := help

EXEC_YAMLLINT = yamllint
ifeq (, $(shell which -s yamllint))
	EXEC_YAMLLINT = docker run --rm $$(tty -s && echo "-it" || echo) -v $(PWD):/data cytopia/yamllint:1.26
endif

COMPOSE_EXEC ?=

# Prefix any command that should be run within the fpm docker container with $(EXEC_FPM)
EXEC_PHP = docker-compose exec php
ifeq (, $(shell which docker-compose))
	EXEC_PHP =
endif

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: docker-sh
docker-sh: ## Starts a bash session in the php container
	docker-compose exec php /bin/bash

.PHONY: docker-up
docker-up: ## Start Docker containers
	docker-compose up --detach --build --remove-orphans

.PHONY: yamllint
yamllint: ## Lints yaml files
	$(EXEC_YAMLLINT) -c .yamllint.yaml --strict .

.PHONY: phpstan
phpstan: ## Static analysis
	$(EXEC_PHP) phpstan

.PHONY: cs
cs: ## Coding standards check
	$(EXEC_PHP) ecs check

.PHONY: cs
cs-fix: ## Coding standards fix
	$(EXEC_PHP) ecs check --fix

.PHONY: all
all: phpstan yamllint cs ## Runs all test/lint targets
