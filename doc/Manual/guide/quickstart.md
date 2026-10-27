# Quickstart

## Send a text mail

```php
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP;

$message = Message::getInstance()
	->setSender('john@example.com', 'John Doe')
	->addRecipient('mike@example.com', 'Mike Foo')
	->setSubject('This is just a test')
	->addText('Test Message...');

$results = SMTP::getInstance('smtp.example.com', 587)
	->setAuth('john@example.com', 'my_password')
	->send($message);
```

## Text, HTML, attachments and recipients

```php
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP;

$message = new Message();
$message->setSender('john@example.com', 'John Doe');
$message->addRecipient('mike@example.com', 'Mike Foo');            // To
$message->addRecipient('log@example.com', NULL, 'cc');              // Cc
$message->addRecipient('archive@example.com', NULL, 'bcc');         // Bcc, never visible
$message->addReplyTo('support@example.com', 'Support');

$message->setSubject('Grüße aus Köln');                             // encoded automatically
$message->addText('Test Message...');
$message->addHTML('<h2><img src="CID:logo"/><br>Test Message</h2>');
$message->addInlineImage('logo', 'logo.png');
$message->addAttachment('readme.md');

$transport = new SMTP('smtp.example.com', 587);
$transport->setAuth('john@example.com', 'my_password');
$transport->setVerbose(TRUE);                                       // print SMTP dialog, credentials hidden
$results = $transport->send($message);
```

## Check the results

`send()` returns one [`Result`](../api/Transport/Result.md) per recipient (To, Cc and Bcc).
A rejected recipient does not stop sending to the others.

```php
use CeusMedia\Mail\Transport\Result;

foreach ($results as $result) {
	if ($result->getStatus() !== Result::STATUS_SUCCESS)
		printf("%s: %d %s\n",
			$result->getReceiver(),
			$result->getCode(),
			$result->getMessage()
		);
}
```

## Read the newest mail of a mailbox

```php
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection;

$mailbox = new Mailbox(new Connection('imap.example.com', 'john@example.com', 'my_password'));
foreach (array_slice($mailbox->index(), 0, 1) as $mailId) {
	$message = $mailbox->getMailAsMessage($mailId);
	echo $message->getSubject(), PHP_EOL;
	if ($message->hasText())
		echo $message->getText()->getContent(), PHP_EOL;
}
```

## Next steps

<div class="grid cards" markdown>

- :material-email-edit-outline: [Compose messages](messages.md)
- :material-send-outline: [Send via SMTP or sendmail](sending.md)
- :material-inbox-outline: [Work with mailboxes](mailbox.md)
- :material-api: [API Reference](../api/index.md)

</div>
