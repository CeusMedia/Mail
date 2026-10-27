# Messages

A [`Message`](../api/Message.md) holds headers, participants and MIME parts.
All setters return the message itself, so calls can be chained.

## Participants

```php
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Message;

$message = Message::getInstance()
	->setSender('john@example.com', 'John Doe')
	->addRecipient('mike@example.com', 'Mike Foo')           // type defaults to TO
	->addRecipient('anna@example.com', 'Anna', 'cc')
	->addRecipient('archive@example.com', NULL, 'bcc')
	->addReplyTo('support@example.com', 'Support Team')
	->setReadNotificationRecipient('john@example.com');      // Disposition-Notification-To
```

Addresses can be given as string or as [`Address`](../api/Address.md) object.
A string may contain a name, too:

```php
$message->addRecipient('"Doe, Jürgen" <juergen@example.com>');
$message->addRecipient(new Address('Mike Foo <mike@example.com>'));
```

Good to know:

- The same recipient is added only once; a name given later is taken, if it was missing before.
- To, Cc and Reply-To are rendered as **one header each**, folded between addresses (RFC 5322).
- **Bcc is never rendered**, not even for parsed messages.
- Names with special characters are quoted or encoded (RFC 2047) automatically.

Reading participants:

```php
$sender = $message->getSender();                          // Address|NULL
foreach ($message->getRecipientsByType('cc') as $address)  // Address\Collection, iterable
	echo $address->getAddress(), PHP_EOL;
$message->hasRecipients();                                // any of To, Cc, Bcc
```

## Subject and other headers

```php
$message->setSubject('Grüße aus Köln');       // non-ASCII encoded, line breaks become a space
$message->setUserAgent('MyApp/1.0');
$message->addHeaderPair('X-Campaign', 'autumn-2026');

echo $message->getSubject();                  // decoded
$headers = $message->getHeaders();                    // Message\Header\Section
if ($headers->hasField('X-Campaign'))
	echo $headers->getField('X-Campaign')->getValue();
```

!!! note "Header injection"
    Line breaks in header values are rejected, invalid header names as well.
    In the subject, line breaks are replaced by a space.

## Content parts

| Method | Part class | Purpose |
|--------|-----------|---------|
| `addText($content, $charset = 'UTF-8', $encoding = 'base64')` | [`Part\Text`](../api/Message/Part/Text.md) | plain text body |
| `addHTML($content, $charset = 'UTF-8', $encoding = 'base64')` | [`Part\HTML`](../api/Message/Part/HTML.md) | HTML body |
| `addInlineImage($id, $filePath, ...)` | [`Part\InlineImage`](../api/Message/Part/InlineImage.md) | image referenced by `CID:<id>` |
| `addAttachment($filePath, $mimeType = NULL, $encoding = NULL, $fileName = NULL)` | [`Part\Attachment`](../api/Message/Part/Attachment.md) | file attachment |
| `addMail($content, ...)` | [`Part\Mail`](../api/Message/Part/Mail.md) | attached message (`message/rfc822`) |
| `addPart(Part $part)` | any | own part object |

```php
$message
	->addText("Hello Mike,\nsee the attached report.")
	->addHTML('<p>Hello Mike,</p><p><img src="CID:chart"></p>')
	->addInlineImage('chart', '/path/to/chart.png')
	->addAttachment('/path/to/report-2026.pdf', 'application/pdf', NULL, 'Report 2026.pdf');
```

MIME types are detected automatically if not given. File names of attachments are encoded by RFC 2231.

=== "Text with quoted-printable"

    ```php
    $message->addText($text, 'UTF-8', 'quoted-printable');
    ```

=== "Own part object"

    ```php
    use CeusMedia\Mail\Message\Part\Attachment;

    $part = (new Attachment())
    	->setFile('/path/to/data.csv', 'text/csv')
    	->setFileName('export.csv');
    $message->addPart($part);
    ```

## Inspecting parts

```php
$message->hasText();            $message->getText();          // Part\Text
$message->hasHTML();            $message->getHTML();          // Part\HTML
$message->hasAttachments();     $message->getAttachments();   // list of Part\Attachment
$message->hasInlineImages();    $message->getInlineImages();  // list of Part\InlineImage

foreach ($message->getParts(withAttachments: FALSE) as $part)
	echo $part->getMimeType(), ' ', strlen($part->getContent() ?? ''), ' bytes', PHP_EOL;
```

## Rendering

To get the raw RFC 5322 message, e.g. for storing or debugging:

```php
use CeusMedia\Mail\Message\Renderer;

$raw = Renderer::render($message);
file_put_contents('message.eml', $raw);
```

Transports render messages by themselves – rendering is only needed for own purposes.
