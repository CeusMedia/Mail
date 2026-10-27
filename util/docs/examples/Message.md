```php
use CeusMedia\Mail\Message;

$message = Message::getInstance()
	->setSender('john@example.com', 'John Doe')
	->addRecipient('mike@example.com', 'Mike Foo')
	->addRecipient('log@example.com', NULL, 'cc')
	->addRecipient('archive@example.com', NULL, 'bcc')
	->addReplyTo('support@example.com')
	->setSubject('Grüße aus Köln')
	->addText('Hello Mike!')
	->addHTML('<p>Hello <b>Mike</b>!</p><img src="CID:logo">')
	->addInlineImage('logo', 'logo.png')
	->addAttachment('report.pdf');

$message->hasAttachments();                  // TRUE
$message->getRecipientsByType('cc');         // Address\Collection
```

See guide: [Messages](../guide/messages.md)
