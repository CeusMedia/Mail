```php
use CeusMedia\Mail\Transport\Local;

//  sendmail_path must contain option -t (PHP default: sendmail -t -i)
$results = (new Local())->send($message, ['-fbounce@example.com']);
```

See guide: [Sending](../../guide/sending.md#local-transport-sendmail)
