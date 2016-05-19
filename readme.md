### Mail

Send mails via SMTP using PHP.

#### Features
- Programming
  - simple, easy, clean
  - PHP5, object-oriented style, chainable
  - automatic encoding
- MIME Contents
  - HTML
  - plain text
  - file attachments
  - inline images
- Partipicants
  - To, Cc, Bcc
  - sender and receiver names
- Transports
  - SMTP, with TLS support
  - local PHP mail function
- Checks
  - address validity
  - receiver reachability

#### Code Example

```php
\CeusMedia\Mail\Transport\SMTP::getInstance("example.com", 587)
	->setUsername("john@example.com")
	->setPassword("my_password")
	->send(\CeusMedia\Mail\Message::getInstance()
		->setSender("john@example.com", "John Doe")
		->addRecipient("mike@example.com", "Mike Foo")
		->setSubject("This is just a test")
		->addPart(new \CeusMedia\Mail\Part\Text("Test Message..."));
	);
```
