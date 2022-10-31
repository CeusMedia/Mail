install: composer-install
	@cp Mail.ini.dist Mail.ini

install-dev: composer-install-dev dev-configure

composer-install:
	@test ! -f vendor/autoload.php && XDEBUG_MODE=off composer install --no-dev || true

composer-install-dev:
	@test ! -d vendor/phpunit/phpunit && XDEBUG_MODE=off composer install || true

composer-update:
	@XDEBUG_MODE=off composer update --no-dev

composer-update-dev:
	@XDEBUG_MODE=off composer update

dev-configure:
	@cp Mail.ini.dist Mail.ini
	@read -p 'Sender Server Host (eg. smtp.myserver.tld): ' input && sed -i "s@{{phpunit.sender.server.host}}@$$input@" Mail.ini
	@read -p 'Sender Server Port (587 for SSL, 465 for TLS, empty for auto ): ' input && sed -i "s@{{phpunit.sender.server.port}}@$$input@" Mail.ini
	@read -p 'Sender Mailbox Address (eg. me@myserver.tld): ' input && sed -i "s*{{phpunit.sender.mailbox.address}}*$$input*" Mail.ini
	@read -p 'Sender Mailbox Name (eg. Firstname Surname): ' input && sed -i "s@{{phpunit.sender.mailbox.name}}@$$input@" Mail.ini
	@read -p 'Sender Auth Mode (eg. LOGIN, CRAM-MD5, empty to auto): ' input && sed -i "s@{{phpunit.sender.auth.mode}}@$$input@" Mail.ini
	@read -p 'Sender Auth Username: ' input && sed -i "s*{{phpunit.sender.auth.username}}*$$input*" Mail.ini
	@read -p 'Sender Auth Password: ' input && sed -i "s {{phpunit.sender.auth.password}} $$input " Mail.ini
	@read -p 'Receiver Server Host (eg. smtp.myserver.tld): ' input && sed -i "s@{{phpunit.receiver.server.host}}@$$input@" Mail.ini
	@read -p 'Receiver Server Port (993:IMAP+SSL, 143:IMAP, 995:POP3+SSL, 110:POP3): ' input && sed -i "s@{{phpunit.receiver.server.port}}@$$input@" Mail.ini
	@read -p 'Receiver Mailbox Address (eg. me@myserver.tld): ' input && sed -i "s*{{phpunit.receiver.mailbox.address}}*$$input*" Mail.ini
	@read -p 'Receiver Mailbox Name (eg. Firstname Surname): ' input && sed -i "s@{{phpunit.receiver.mailbox.name}}@$$input@" Mail.ini
	@read -p 'Receiver Auth Mode (eg. LOGIN, CRAM-MD5, empty to auto): ' input && sed -i "s@{{phpunit.receiver.auth.mode}}@$$input@" Mail.ini
	@read -p 'Receiver Auth Username: ' input && sed -i "s*{{phpunit.receiver.auth.username}}*$$input*" Mail.ini
	@read -p 'Receiver Auth Password: ' input && sed -i "s {{phpunit.receiver.auth.password}} $$input " Mail.ini

dev-doc: composer-install-dev
	@test -f doc/API/search.html && rm -Rf doc/API || true
	@php vendor/ceus-media/doc-creator/doc.php --config-file=util/doc.xml

