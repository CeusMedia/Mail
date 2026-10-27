```php
use CeusMedia\Mail\Transport\Result;

foreach ($transport->send($message) as $result)
	if (Result::STATUS_SUCCESS !== $result->getStatus())
		printf("%s: %d %s\n", $result->getReceiver(), $result->getCode(), $result->getMessage());
```
