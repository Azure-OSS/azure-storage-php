.PHONY: build
build: cs static test install ## Runs cs, static, and test targets

# https://www.gnu.org/software/make/manual/html_node/Force-Targets.html
always:

.PHONY: help
help:
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: install
install: ## Install depedencies
	docker compose run phpfpm composer install

.PHONY: cs
cs: ## Fixes coding standard issues with laravel/pint
	docker compose run phpfpm vendor/bin/pint --repair

.PHONY: coverage
coverage: ## Collects coverage with phpunit
	docker compose run phpfpm vendor/bin/phpunit --coverage-text --coverage-clover=.build/logs/clover.xml

.PHONY: test
test: ## Runs tests with phpunit
	docker compose run phpfpm vendor/bin/phpunit

.PHONY: static
static: ## Runs static analyzers
	docker compose run phpfpm vendor/bin/phpstan --memory-limit=2G

.PHONY: baseline
baseline: ## Generate baseline files
	docker compose run phpfpm vendor/bin/phpstan --memory-limit=2G --generate-baseline

.PHONY: clean
clean:   ## Cleans up build and vendor files
	rm -rf vendor composer.lock .build

