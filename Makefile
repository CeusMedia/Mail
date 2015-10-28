PATH_DOCCREATOR := /var/www/lib/cmTools/DocCreator

.PHONY: test

create-doc:
	@php ${PATH_DOCCREATOR}/create.php -c=doc.xml

test:
	@phpunit --bootstrap=test/bootstrap.php --coverage-html=doc/Coverage test


