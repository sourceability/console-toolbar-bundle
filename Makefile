.PHONY: help
.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: docker-sh
docker-sh: ## Starts a bash session in the php container
	docker-compose exec php /bin/bash

.PHONY: docker-up
docker-up: ## Start Docker containers
	docker-compose up --detach --build

EXEC_YAMLLINT = yamllint
ifeq (, $(shell which -s yamllint))
	EXEC_YAMLLINT = docker run --rm $$(tty -s && echo "-it" || echo) -v $(PWD):/data cytopia/yamllint:1.26
endif

.PHONY: yamllint
yamllint:
	$(EXEC_YAMLLINT) -c .yamllint.yaml --strict .
