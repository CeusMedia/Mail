```php
use CeusMedia\Mail\Util\Dmarc\Parser;

$record = Parser::getInstance()->parse('v=DMARC1; p=quarantine; rua=mailto:dmarc@example.com; pct=50');
$record->policy;     // quarantine
$record->percent;    // 50
```

See guide: [Checks & Utilities](../../../guide/checks.md#dmarc)
