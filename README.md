# Ceus Media Mail Library

![Branch](https://img.shields.io/badge/Branch-2.8.x-blue?style=flat-square)
![Release](https://img.shields.io/badge/Release-2.8.1-blue?style=flat-square)
![PHP version](https://img.shields.io/badge/PHP-%5E8.3-blue?style=flat-square&color=777BB4)
![PHPStan level](https://img.shields.io/badge/PHPStan_level-7-darkgreen?style=flat-square)

Produce, send and read mails using PHP + IMAP & SMTP.

[![Latest Stable Version](https://poser.pugx.org/ceus-media/mail/v)](//packagist.org/packages/ceus-media/mail)
[![Total Downloads](https://poser.pugx.org/ceus-media/mail/downloads)](//packagist.org/packages/ceus-media/mail)
[![License](https://poser.pugx.org/ceus-media/mail/license)](//packagist.org/packages/ceus-media/mail)

----

## Features
- Programming
  - simple, easy, clean
  - PHP 8.1+, object-oriented style, chainable
  - automatic encoding
    - header values and names by RFC 2047, independent of PHP version
    - file names of attachments by RFC 2231
    - protection against header injection
  - automatic MIME type detection
- MIME Contents
  - HTML
  - plain text
  - file attachments
  - inline images
- Participants
  - To, Cc, Bcc, Reply-To
  - sender and receiver names, encoded if needed
- Transports
  - SMTP
    - STARTTLS (port 587) and implicit TLS (port 465)
    - certificate validation, SSL context options (e.g. own CA file)
    - read timeout, EHLO host detection
  - local PHP mail function (sendmail), incl. Cc and Bcc
  - **transport result entity** *(new)*
- Mailbox
  - access via IMAP and POP3
  - search with criteria
  - upload mail to mailbox
- Checks
  - address validity
  - receiver reachability

## Code Examples

### Short version

This example shows how to send a text mail using method chaining.

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
$message->addHTML('<h2><img src="CID:logo"/><br>Test Message</h2>');
$message->addInlineImage("logo", "logo.png");
$message->addFile("readme.md");

$transport	= new SMTP("example.com", 587);
$transport->setUsername("john@example.com");
$transport->setPassword("my_password");
$transport->setVerbose(TRUE);
$transport->send( $message );
```

### SMTP options

```php
use \CeusMedia\Mail\Transport\SMTP;

$transport	= new SMTP("mail.example.com", 465);	//  port 465: implicit TLS, 587: STARTTLS
$transport->setAuth("john@example.com", "my_password");
$transport->setHeloHost("app.example.com");			//  name of this client, detected if not set
$transport->setReadTimeout(10);						//  seconds to wait for a server response, default: 30

//  encryption, independent of port
$transport->setSecure(TRUE);						//  encrypt connection, validate certificate
$transport->setImplicitTls(FALSE);					//  STARTTLS instead of implicit TLS

//  trust own certificate authority, e.g. for internal servers
$transport->setSslOptions(["cafile" => "/path/to/ca.pem"]);

//  self-signed certificate without validation, for testing only
$transport->setSecure(TRUE, FALSE);
```

Sending returns a list of results, one per recipient (To, Cc and Bcc):

```php
foreach ($transport->send($message) as $result)
	if ($result->getStatus() !== \CeusMedia\Mail\Transport\Result::STATUS_SUCCESS)
		echo $result->getReceiver()->getAddress().': '.$result->getMessage().PHP_EOL;
```

### Local transport

Uses the PHP mail function, i.e. sendmail.
On Unix, recipients (To, Cc, Bcc) are read from the headers by sendmail,
so the configured `sendmail_path` must contain the option `-t` (PHP default: `sendmail -t -i`).
Sendmail removes the Bcc header before delivery.

Additional parameters for sendmail can be given, e.g. the envelope sender:

```php
use \CeusMedia\Mail\Transport\Local;

$results	= (new Local())->send($message, ["-fbounce@example.com"]);
```

### Header encoding

Header values are encoded automatically when rendering a message.
For own headers or other uses, the encoder and the address list renderer are available:

```php
use \CeusMedia\Mail\Address;
use \CeusMedia\Mail\Address\Collection as AddressCollection;
use \CeusMedia\Mail\Address\Collection\Renderer as AddressCollectionRenderer;
use \CeusMedia\Mail\Message\Header\Encoding;

$encoder	= Encoding::getInstance();

//  unstructured value, e.g. subject: ASCII stays plain, otherwise encoded words, folded
//  offset: length of header name, colon and space
$value		= $encoder->encodeIfNeeded("Grüße aus Köln", "quoted-printable", TRUE, strlen("Subject: "));

//  encode whole string as encoded words (base64 or quoted-printable)
$value		= $encoder->encodeWords("Grüße aus Köln");

//  decode unstructured value, e.g. subject: also characters split across encoded words, unknown charsets
$value		= $encoder->decodeIfNeeded("=?UTF-8?Q?Gr=C3=BC=C3=9Fe?= aus =?UTF-8?Q?K=C3=B6ln?=");

//  decode address list, names with special characters are quoted: "Doe, Jürgen" <john@example.com>
$list		= $encoder->decodeAddressListIfNeeded("=?UTF-8?B?RG9lLCBKw7xyZ2Vu?= <john@example.com>");

//  render address list for a header: names encoded or quoted, folded between addresses
$value		= AddressCollectionRenderer::getInstance()
	->setEncodeNames()
	->setLineLength(76)
	->render(new AddressCollection([
		new Address("Doe, Jürgen <john@example.com>"),
		new Address("mike@example.com"),
	]));
```

Line breaks in header values are rejected (header injection), line breaks in the subject become a space.

## Development

### Tests

```sh
composer dev:phpunit-all				# unit and integration tests
composer dev:phpstan					# static analysis
```

Integration tests against local [Mailpit](https://mailpit.axllent.org/) instances (Docker),
plain SMTP, STARTTLS and implicit TLS with authentication:

```sh
composer dev:mailpit-up					# start instances (Docker), see util/mailpit
composer dev:phpunit-mailpit			# start instances if needed, run Mailpit tests
composer dev:mailpit-down				# stop instances
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

Client::getInstance("This is just a test")
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
- Assigned receivers will be checked for existence automatically.
- If the auth method is receiving only one parameter, it will be understood as password.
- The auth username will be taken from sender address.
- Thrown exceptions can be caught by a defined error handler.
- If everything is set the mail can be sent.


#### Good to know

##### Using Google as SMTP

Google tried to protect its SMTP access by several measures.
If you are [having problems](https://support.google.com/accounts/answer/6009563) sending mails using Google SMTP, tried [these steps](https://serverfault.com/a/745666):

1. Open a browser and log into Google using a Google account.
2. [Allow "less secure apps"](https://www.google.com/settings/security/lesssecureapps) to have access.
3. [Allow app](https://accounts.google.com/DisplayUnlockCaptcha) to have access.
4. Try again!
