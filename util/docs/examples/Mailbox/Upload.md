```php
use CeusMedia\Mail\Mailbox\Upload;

$upload = new Upload($connection, 'Sent', Upload::FLAG_SEEN);
$upload->storeMessage($message);
```
