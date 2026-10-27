# Parsing

## Raw messages

The [`Message\Parser`](../api/Message/Parser.md) turns a raw RFC 5322 message (e.g. an `.eml` file) into a
[`Message`](../api/Message.md) object – with decoded headers, participants and all MIME parts.

```php
use CeusMedia\Mail\Message\Parser;

$message = Parser::getInstance()->parse(file_get_contents('message.eml'));

echo $message->getSubject(), PHP_EOL;                   // decoded
echo $message->getSender()?->get(), PHP_EOL;
foreach ($message->getRecipientsByType('to') as $address)
	echo $address->get(), PHP_EOL;

foreach ($message->getParts() as $nr => $part) {
	echo match (TRUE) {
		$part->isHTML()        => 'HTML',
		$part->isText()        => 'Text',
		$part->isAttachment()  => 'Attachment: '.$part->getFileName(),
		$part->isInlineImage() => 'Inline image: '.$part->getFileName(),
		$part->isMail()        => 'Attached mail',
		default                => 'Unknown',
	}, PHP_EOL;
}
```

!!! tip "Round trip"
    A parsed message can be rendered and sent again:
    `Message\Renderer::render(Parser::getInstance()->parse($raw))`.
    A Bcc header of the parsed message is never rendered.

## Headers only

```php
use CeusMedia\Mail\Message\Header\Parser as HeaderParser;

$section = HeaderParser::getInstance()->parse($rawHeader);    // Message\Header\Section

foreach ($section->getFields() as $field)
	printf("%s: %s\n", $field->getName(), $field->getValue());

$contentType = $section->getField('Content-Type');
echo $contentType->getValue();                // e.g. multipart/mixed
echo $contentType->getAttribute('boundary');  // parameters, case-insensitive
```

The header parser

- unfolds folded lines,
- decodes encoded words (RFC 2047) and parameters (RFC 2231, e.g. `filename*=`),
- splits only `Content-Type` and `Content-Disposition` into value and parameters,
- skips fields with invalid names, so a parsed message can always be rendered again.

## Delivery chain

The `Received` headers tell the way a message took through the servers:

```php
use CeusMedia\Mail\Message\Header\Received;

foreach ($message->getHeaders()->getFieldsByName('Received') as $field) {
	$hop = Received::parse($field->getValue());
	printf("%s → %s (%s)\n", $hop->getFrom(), $hop->getBy(), $hop->getDate()?->format('c'));
}
```
