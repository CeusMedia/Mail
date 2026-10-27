```php
use CeusMedia\Mail\Message\Header\Encoding;

$encoder = Encoding::getInstance();
$encoded = $encoder->encodeIfNeeded('Grüße aus Köln', 'quoted-printable', TRUE, strlen('Subject: '));
$decoded = $encoder->decodeIfNeeded('=?UTF-8?Q?Gr=C3=BC=C3=9Fe?= aus =?UTF-8?Q?K=C3=B6ln?=');
$list    = $encoder->decodeAddressListIfNeeded('=?UTF-8?B?RG9lLCBKw7xyZ2Vu?= <john@example.com>');
// "Doe, Jürgen" <john@example.com>
```

See guide: [Headers & Encoding](../../../guide/headers.md)
