
.PHONY: test

doc: test
	@test -f doc/API/search.html && rm -Rf doc/API || true
	@php vendor/ceus-media/doc-creator/doc-creator.php --config-file=doc/doc.xml

test: _composer-install test-syntax
	@phpunit --colors --strict --bootstrap=test/bootstrap.php --coverage-html=doc/Coverage test

test-syntax:
	@find src -type f -print0 | xargs -0 -n1 xargs php -l

_composer-install:
	@test ! -f vendor/autoload.php && composer install || true

