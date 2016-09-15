# CeusMedia Mail

Send mails via SMTP using PHP.

## Features
- Programming
  - simple, easy, clean
  - PHP5, object-oriented style, chainable
  - automatic encoding
  - automatic MIME type detection
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

## Code Examples

### Short version

This example shows how to send a text mail using chainability.

```php
\CeusMedia\Mail\Transport\SMTP::getInstance("example.com", 587)
	->setAuth("john@example.com", "my_password")
	->send(\CeusMedia\Mail\Message::getInstance()
		->setSender("john@example.com", "John Doe")
		->addRecipient("mike@example.com", "Mike Foo")
		->setSubject("This is just a test")
		->addText("Test Message...")
	);
```

### Long version

```php
use \CeusMedia\Mail\Message;
use \CeusMedia\Mail\Transport\SMTP;

$message	= new Message();
$message->setSender("john@example.com", "John Doe");
$message->addRecipient("mike@example.com", "Mike Foo");
$message->addRecipient("log@example.com", NULL, 'cc');
$message->addRecipient("spy@example.com", NULL, 'bcc' );

$message->setSubject("This is just a test");
$message->addText("Test Message...");
$message->addHtml('<h2><img src="CID:logo"/><br>Test Message</h2>');
$message->addHtmlImage("logo", "logo.png");
$message->addFile("readme.md");

$transport	= new SMTP("example.com", 587);
$transport->setUsername("john@example.com");
$transport->setPassword("my_password");
$transport->setVerbose(TRUE);
$transport->send( $message );
```

## Future plans
- documentation for already existing parser
- automatic virus scan
- support for logging
- factories and other design patterns
- slim API - see "Future version"

### Future version

Sending a mail should be as easy as possible.
This is an outlook how the interface could look like in future.

**Attention:** This is pseudo code. The used classes are not implemented yet.

```php
use \CeusMedia\Mail\Client;

Client::create("This is just a test")
	->from("john@example.com", "John Doe")
	->to("mike@example.com", "Mike Foo")
	->bcc("spy@example.com")
	->text("Test Message...")
	->auth("my_password")
	->port(587),
	->error("handleMailException")
	->send();

function handleMailException( $e ){
//  ...
}
```

#### Thoughts on this example
- Sending mail with this short code will be using SMTP, only.
- The SMTP server will be determined by fetching MX records of the user's domain.
- Setting the SMTP server port is still needed.
- Assigned receivers will be checked for existance automatically.
- If the auth method is receiving only one parameters, it will be understood as password.
- The auth username will be taken from sender address.
- Thrown exceptions can be catched by a defined error handler.
- It everything is set the mail can be sent.
