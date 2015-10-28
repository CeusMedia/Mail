
.PHONY: test

doc: _composer-install
	@test -f doc/API/search.html && rm -Rf doc/API || true
	@php vendor/ceus-media/doc-creator/doc-creator.php --config-file=doc/doc.xml

test: _composer-install
	@phpunit --bootstrap=test/bootstrap.php --coverage-html=doc/Coverage test

_composer-install:
	@test ! -f vendor/autoload.php && composer install || true

