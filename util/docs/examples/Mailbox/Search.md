```php
use CeusMedia\Mail\Mailbox\Search;

$search = Search::getInstance()
	->setSubject('Invoice')
	->setSender('billing@example.com')
	->setLimit(20);

foreach ($mailbox->performSearch($search) as $mailId => $mail)   // Mailbox\Mail, lazy loading
	echo $mailId, ': ', $mail->getHeader()->getField('Subject')->getValue(), PHP_EOL;
```
