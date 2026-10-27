```php
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection;

$mailbox = Mailbox::getInstance(
	Connection::getInstance('imap.example.com', 'john@example.com', 'my_password')
);
foreach (array_slice($mailbox->index(), 0, 10) as $mailId) {
	$message = $mailbox->getMailAsMessage($mailId);
	echo $message->getSubject(), PHP_EOL;
}
$mailbox->moveMail($mailId, 'Archive', TRUE);
```

See guide: [Mailbox](../guide/mailbox.md)
