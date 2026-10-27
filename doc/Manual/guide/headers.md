# Headers & Encoding

Header values are encoded automatically when rendering a message.
For own headers or other purposes, the [`Message\Header\Encoding`](../api/Message/Header/Encoding.md)
and the [address list renderer](../api/Address/Collection/Renderer.md) are available.

## Encoding

```php
use CeusMedia\Mail\Message\Header\Encoding;

$encoder = Encoding::getInstance();

//  unstructured value, e.g. subject: ASCII stays plain, otherwise encoded words, folded
//  offset: length of header name, colon and space
$value = $encoder->encodeIfNeeded('Grüße aus Köln', 'quoted-printable', TRUE, strlen('Subject: '));

//  encode whole string as encoded words (base64 or quoted-printable)
$value = $encoder->encodeWords('Grüße aus Köln');
```

## Decoding

```php
//  unstructured value: also characters split across encoded words, unknown charsets
$value = $encoder->decodeIfNeeded('=?UTF-8?Q?Gr=C3=BC=C3=9Fe?= aus =?UTF-8?Q?K=C3=B6ln?=');
// Grüße aus Köln

//  address list: names with special characters are quoted
$list = $encoder->decodeAddressListIfNeeded('=?UTF-8?B?RG9lLCBKw7xyZ2Vu?= <john@example.com>');
// "Doe, Jürgen" <john@example.com>
```

## Header fields and sections

```php
use CeusMedia\Mail\Message\Header\Field;
use CeusMedia\Mail\Message\Header\Section;

$section = new Section();
$section->setFieldPair('X-Mailer', 'MyApp');
$section->addField(new Field('Content-Type', 'text/plain'));
$section->getField('Content-Type')->setAttribute('charset', 'UTF-8');

echo $section->toString();
```

Own headers can be added to a message directly:

```php
$message->addHeaderPair('List-Unsubscribe', '<mailto:unsubscribe@example.com>');
```

## Security

!!! success "Protection against header injection"
    - Line breaks in header values are rejected (except folding), invalid header names as well.
    - Line breaks in the subject become a space.
    - Decoded line breaks become a space, so decoded values cannot break a header when rendered again.
    - The Bcc header is never rendered.
