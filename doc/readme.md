Send mails via SMTP using PHP.

#### Features
- Programming
  - simple, easy, clean
  - PHP5, object-oriented style, chainable
  - automatic encoding
- Contents
  - multipart MIME: plain text and HTML
  - file attachments
- Partipicants
  - To, Cc, Bcc
  - sender and receiver names
- Transports
  - SMTP, with TLS support
  - local PHP mail function
- Checks
  - address validity
  - address validity
  - receiver reachability

#### Code Example

	\CeusMedia\Mail\Transport\SMTP::getInstance("example.com", 587)
		->setUsername("john@example.com")
		->setPassword("my_password")
		->setSecure(true)
		->send(\CeusMedia\Mail\Message::getInstance()
			->setSender("john@example.com", "John Doe")
			->addRecipient("mike@example.com", "Mike Foo")
			->setSubject("This is just a test")
			->addPart(new \CeusMedia\Mail\Part\Text("Test Message..."));
		);
