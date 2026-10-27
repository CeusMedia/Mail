```php
use CeusMedia\Mail\Util\MX;

$servers = MX::getInstance()->fromAddress('mike@example.com');
// [10 => 'mx1.example.com', 20 => 'mx2.example.com']
```
