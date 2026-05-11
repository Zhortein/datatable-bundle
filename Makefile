# —— 🛠️ Configuration ————————————————————————————————————————————————————————————————
.DEFAULT_GOAL := help
.PHONY: help build installdeps updatedeps composer csfixer cscheck phpstan test twigcs qa php

TOOLS_IMAGE ?= zhortein-datatable-tools:php84
APP_DIR := /app

TTY := $(shell test -t 0 && echo -it)

UID := $(shell id -u)
GID := $(shell id -g)
USER_FLAGS := --user $(UID):$(GID)

COMPOSER_CACHE_HOST := $(PWD)/.cache/composer
COMPOSER_CACHE_CONT := /tmp/composer-cache

DOCKER_VOLUME := -v "$(PWD)":$(APP_DIR) -w $(APP_DIR) -v "$(COMPOSER_CACHE_HOST)":$(COMPOSER_CACHE_CONT)
DOCKER_RUN := docker run --rm $(TTY) $(USER_FLAGS) -e COMPOSER_CACHE_DIR=$(COMPOSER_CACHE_CONT) $(DOCKER_VOLUME) $(TOOLS_IMAGE)

## —— 🐳 Zhortein Datatable Bundle Makefile 🐳 ————————————————————————————————————————
help: ## 📖 Show available commands
	@echo ""
	@echo "📖 Available make commands:"
	@echo ""
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'

build: ## Build local tooling image
	@docker build -t $(TOOLS_IMAGE) -f docker/Dockerfile .

installdeps: build ## Install Composer deps
	@mkdir -p .cache/composer
	$(DOCKER_RUN) composer install --prefer-dist --no-progress

updatedeps: build ## Update deps
	$(DOCKER_RUN) composer update --prefer-dist --no-progress

composer: build ## Run composer (usage: make composer ARGS='update')
	$(DOCKER_RUN) composer $(ARGS)

csfixer: build ## Run PHP-CS-Fixer and fix files
	$(DOCKER_RUN) composer cs:fix

cscheck: build ## Run PHP-CS-Fixer in check mode
	$(DOCKER_RUN) composer cs:check

phpstan: build ## Run PHPStan
	$(DOCKER_RUN) composer phpstan

test: build ## Run PHPUnit
	$(DOCKER_RUN) composer test

twigcs: build ## Run twigcs
	$(DOCKER_RUN) composer twigcs

qa: build ## Run all QA checks
	$(DOCKER_RUN) composer qa

php: build ## Run PHP command (usage: make php ARGS='script.php')
	$(DOCKER_RUN) php $(ARGS)