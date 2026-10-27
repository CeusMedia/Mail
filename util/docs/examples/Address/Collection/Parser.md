```php
use CeusMedia\Mail\Address\Collection\Parser;

$list = Parser::getInstance()->parse('john@example.com, "Doe, Jürgen" <juergen@example.com>');
count($list);   // 2
```
