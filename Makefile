DC  = docker compose
PHP = $(DC) exec php

.DEFAULT_GOAL := help

## —— Stack ————————————————————————————————————————————————————————————————
up: ## Build (if needed) and start the stack in the background
	$(DC) up -d --build --wait

setup: up install migrate ## First-time setup: start the stack, install deps, migrate

down: ## Stop the stack and remove the containers
	$(DC) down

build: ## Rebuild the PHP image
	$(DC) build

logs: ## Tail logs from all services
	$(DC) logs -f

sh: ## Open a shell in the PHP container
	$(PHP) sh

## —— Application ——————————————————————————————————————————————————————————
install: ## Install Composer dependencies
	$(PHP) composer install

console: ## Run a Symfony console command, e.g. make console c="cache:clear"
	$(PHP) php bin/console $(c)

migrate: ## Run database migrations
	$(PHP) php bin/console doctrine:migrations:migrate --no-interaction

## —— Quality gates ————————————————————————————————————————————————————————
qa: lint stan deptrac test ## Run the full quality suite

lint: ## Check coding standards (no changes)
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs: ## Fix coding standards
	$(PHP) vendor/bin/php-cs-fixer fix

stan: ## Run static analysis (PHPStan level 9)
	$(PHP) vendor/bin/phpstan analyse --no-progress

deptrac: ## Check bounded-context and layer boundaries
	$(PHP) vendor/bin/deptrac analyse --no-progress
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml --no-progress

rector: ## Preview automated refactorings (no changes)
	$(PHP) vendor/bin/rector process --dry-run

test: ## Run the test suite
	$(PHP) vendor/bin/phpunit

coverage: ## Run the test suite with coverage (HTML report in var/coverage)
	$(PHP) vendor/bin/phpunit --coverage-html var/coverage --coverage-text

help: ## List available targets
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; /^##/ {printf "\n%s\n", substr($$0, 4)} /^[a-zA-Z_-]+:/ {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

.PHONY: up setup down build logs sh install console migrate qa lint cs stan deptrac rector test coverage help
