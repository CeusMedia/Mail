```php
use CeusMedia\Mail\Message\Parser;

$message = Parser::getInstance()->parse(file_get_contents('message.eml'));
echo $message->getSubject(), PHP_EOL;
foreach ($message->getAttachments() as $attachment)
	echo $attachment->getFileName(), PHP_EOL;
```

See guide: [Parsing](../../guide/parsing.md)
