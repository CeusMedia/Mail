```php
use CeusMedia\Mail\Address\Check\Availability;

$check = new Availability('checker@my-domain.com');
if (!$check->test('mike@example.com'))
	echo $check->getLastResponse()->message, PHP_EOL;
```
