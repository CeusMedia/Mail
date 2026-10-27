```php
use CeusMedia\Mail\Address;

$address = new Address('Mike Foo <mike@example.com>');
$address->getName();        // Mike Foo
$address->getAddress();     // mike@example.com
$address->getDomain();      // example.com
echo $address;              // "Mike Foo" <mike@example.com>
```

See guide: [Addresses](../guide/addresses.md)
