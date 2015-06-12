PATH_DOCCREATOR := /var/www/lib/cmTools/DocCreator

create-doc:
	@php ${PATH_DOCCREATOR}/create.php -c=doc.xml

test:
	@phpunit --bootstrap=Test/bootstrap.php --coverage-html=Coverage Test


