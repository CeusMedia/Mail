```php
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection;
use CeusMedia\Mail\Address\Collection\Renderer;

echo Renderer::getInstance()
	->setEncodeNames()
	->setLineLength(76)
	->render(new Collection([
		new Address('Doe, Jürgen <john@example.com>'),
		new Address('mike@example.com'),
	]));
```
