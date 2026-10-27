```php
use CeusMedia\Mail\Address\Check\Syntax;

$checker = new Syntax();
$checker->isValid('john@example.com');                           // TRUE
$checker->isValidByMode('john@example.com', Syntax::MODE_FILTER); // TRUE
$checker->check('john@');                                         // throws InvalidArgumentException
```
