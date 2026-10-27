```php
use CeusMedia\Mail\Transport\SMTP;

$transport = SMTP::getInstance('smtp.example.com', 587)    // 587: STARTTLS, 465: implicit TLS
	->setAuth('john@example.com', 'my_password')
	->setReadTimeout(10)
	->setSslOptions(['cafile' => '/path/to/ca.pem']);

foreach ($transport->send($message) as $result)
	echo $result->getReceiver(), ': ', $result->getStatus(), PHP_EOL;
```

See guide: [Sending](../../guide/sending.md)
