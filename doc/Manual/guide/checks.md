# Checks & Utilities

## Syntax

[`Address\Check\Syntax`](../api/Address/Check/Syntax.md) validates the syntax of mail addresses
by PHP's filter and regular expressions.

```php
use CeusMedia\Mail\Address\Check\Syntax;

$checker = new Syntax();
$checker->isValid('john@example.com');      // TRUE
$checker->isValid('john@');                 // FALSE

//  throws InvalidArgumentException if not valid
$checker->check($input);

//  only one test mode
$checker->isValidByMode('john@example.com', Syntax::MODE_FILTER);
```

| Mode | Test |
|------|------|
| `MODE_FILTER` | `filter_var()` with `FILTER_VALIDATE_EMAIL` |
| `MODE_SIMPLE_REGEX` | simple regular expression |
| `MODE_EXTENDED_REGEX` | extended regular expression, more special characters allowed |
| `MODE_ALL` | all tests |

## Availability

[`Address\Check\Availability`](../api/Address/Check/Availability.md) asks the receiving mail server
whether a mailbox exists – without sending a mail. The server is found by MX records of the domain.

```php
use CeusMedia\Mail\Address\Check\Availability;

$check = new Availability('checker@my-domain.com');    // sender address used in SMTP dialog
try {
	$available = $check->test('mike@example.com');
}
catch (Throwable $e) {
	$available = FALSE;
}
if (!$available) {
	$response = $check->getLastResponse();
	printf("Error %d: %d %s\n", $response->error, $response->code, $response->message);
}
```

!!! note
    Many servers answer positively for every address (catch-all) or block such checks.
    A positive result is a hint, not a guarantee.

## MX records

```php
use CeusMedia\Mail\Util\MX;

//  mail servers, keyed and sorted by priority, e.g. [10 => 'mx1.example.com', 20 => 'mx2.example.com']
$servers = MX::getInstance()->fromAddress('mike@example.com');
$servers = MX::getInstance()->fromHostname('example.com');

//  without exception if no records are found
$servers = MX::getInstance()->fromHostname('example.com', strict: FALSE);
```

Results can be cached with any PSR-16 cache via `setCache()`.

## DMARC

Read and write DMARC policies (TXT record of `_dmarc.<domain>`) with
[`Util\Dmarc\Parser`](../api/Util/Dmarc/Parser.md), [`Record`](../api/Util/Dmarc/Record.md) and
[`Renderer`](../api/Util/Dmarc/Renderer.md):

```php
use CeusMedia\Mail\Util\Dmarc\Parser;
use CeusMedia\Mail\Util\Dmarc\Record;
use CeusMedia\Mail\Util\Dmarc\Renderer;

$txt    = dns_get_record('_dmarc.example.com', DNS_TXT)[0]['txt'] ?? '';
$record = Parser::getInstance()->parse($txt);

if ($record->policy === Record::POLICY_REJECT)
	echo 'Mails failing DMARC are rejected', PHP_EOL;
foreach ($record->reportAggregate as $address)
	echo 'Reports to ', $address->getAddress(), PHP_EOL;

$record->policy  = Record::POLICY_QUARANTINE;
$record->percent = 50;
echo Renderer::getInstance()->render($record);
// v=DMARC1; p=quarantine; adkim=r; aspf=r; pct=50; rua=mailto:dmarc@example.com
```
