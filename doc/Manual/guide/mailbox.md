# Mailbox

Mailboxes are accessed via IMAP or POP3 using PHP's `imap` extension.

```php
use CeusMedia\Mail\Mailbox;

Mailbox::checkExtensionInstalled();   // throws if ext-imap is missing
```

## Connect

```php
use CeusMedia\Mail\Mailbox;
use CeusMedia\Mail\Mailbox\Connection;

$connection = Connection::getInstance('imap.example.com', 'john@example.com', 'my_password')
	->setSecure(TRUE, TRUE);          // encrypted, validate certificate (default)
$mailbox = Mailbox::getInstance($connection);
```

!!! warning
    Only switch off certificate validation (`setSecure(TRUE, FALSE)`) for testing.

## List and read mails

`index()` returns mail UIDs, newest first by default:

```php
foreach (array_slice($mailbox->index(), 0, 10) as $mailId) {
	$headers = $mailbox->getMailHeaders($mailId);          // Message\Header\Section
	echo $headers->getField('Subject')->getValue(), PHP_EOL;
}

$message = $mailbox->getMailAsMessage($mailId);           // fully parsed Message
echo $message->getSender()?->getAddress();
foreach ($message->getAttachments() as $attachment)
	file_put_contents('/tmp/'.$attachment->getFileName(), $attachment->getContent());
```

[`Mailbox\Mail`](../api/Mailbox/Mail.md) loads parts of a mail lazily – the header first, the body only if needed:

```php
$mail    = $mailbox->getMail($mailId);
$header  = $mail->getHeader();              // only the header is fetched
$message = $mail->getMessage(TRUE);         // now with body parts
```

## Search

```php
use CeusMedia\Mail\Mailbox\Search;

$search = Search::getInstance()
	->setSubject('Invoice')
	->setSender('billing@example.com')
	->setLimit(20)
	->setOffset(0);

foreach ($mailbox->performSearch($search) as $mailId => $mail)
	echo $mailId, ': ', $mail->getHeader()->getField('Subject')->getValue(), PHP_EOL;
```

Short form with conditions:

```php
$mails = $mailbox->search(['subject' => 'Invoice', 'sender' => 'billing@example.com']);
```

## Folders, moving, flags

```php
$folders = $mailbox->getFolders(TRUE);                 // recursive

$mailbox->moveMail($mailId, 'Archive/2026', TRUE);     // folder name in UTF-8, expunge now
$mailbox->setMailFlag((string) $mailId, 'seen');       // seen, answered, flagged, deleted, draft
$mailbox->unsetMailFlag((string) $mailId, 'flagged');
$mailbox->removeMail($mailId, TRUE);
```

## Upload

[`Mailbox\Upload`](../api/Mailbox/Upload.md) stores messages in a folder, e.g. a copy of a sent mail:

```php
use CeusMedia\Mail\Mailbox\Upload;

$upload = new Upload($connection, 'Sent', Upload::FLAG_SEEN);
$upload->storeMessage($message);
```
