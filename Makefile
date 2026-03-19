SHELL := /bin/bash

.PHONY: phpunit phpstan phpcs cs-fix rector validate

phpunit:
	composer phpunit

phpstan:
	composer phpstan

phpcs:
	composer cs

cs-fix:
	composer cs-fix

rector:
	@if [ -x vendor/bin/rector ]; then vendor/bin/rector process; else echo "Rector is not installed, skipping."; fi

validate:
	composer validate
