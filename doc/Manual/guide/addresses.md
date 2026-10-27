# Addresses

## Address objects

An [`Address`](../api/Address.md) consists of a local part, a domain and an optional name.

```php
use CeusMedia\Mail\Address;

$address = new Address('"Doe, Jürgen" <juergen@example.com>');

$address->getLocalPart();   // juergen
$address->getDomain();      // example.com
$address->getAddress();     // juergen@example.com
$address->getName();        // Doe, Jürgen
$address->get();            // "Doe, Jürgen" <juergen@example.com>
echo $address;              // same as get()
```

Building an address step by step:

```php
$address = Address::getInstance()
	->setLocalPart('mike')
	->setDomain('example.com')
	->setName('Mike Foo');
```

Invalid input throws an `InvalidArgumentException`. Use the [syntax check](checks.md#syntax) to validate input first.

## Collections

An [`Address\Collection`](../api/Address/Collection.md) is a countable, iterable list of addresses –
used for To, Cc and Bcc of a message.

```php
use CeusMedia\Mail\Address;
use CeusMedia\Mail\Address\Collection;

$list = new Collection([
	new Address('john@example.com'),
	new Address('Mike Foo <mike@example.com>'),
]);
$list->add(new Address('anna@example.com'));

count($list);                                          // 3
foreach ($list as $address)
	echo $address->getAddress(), PHP_EOL;

$external = $list->filter(fn(Address $address) => $address->getDomain() !== 'example.com');
```

## Parsing address lists

The [`Address\Collection\Parser`](../api/Address/Collection/Parser.md) reads header values like
`To` or `Cc` – quoted names containing commas are handled correctly.

```php
use CeusMedia\Mail\Address\Collection\Parser;

$list = Parser::getInstance()->parse('john@example.com, "Doe, Jürgen" <juergen@example.com>');
foreach ($list as $address)
	printf("%s (%s)\n", $address->getAddress(), $address->getName() ?? '-');
```

## Rendering address lists

The [`Address\Collection\Renderer`](../api/Address/Collection/Renderer.md) renders lists for headers:
names are quoted or encoded (RFC 2047) and long lists are folded between addresses.

```php
use CeusMedia\Mail\Address\Collection\Renderer;

$value = Renderer::getInstance()
	->setEncodeNames()
	->setLineLength(76)
	->render($list);
```

## Names

[`Address\Name`](../api/Address/Name.md) helps splitting person names, e.g. for salutations:

```php
use CeusMedia\Mail\Address\Name;

$name = Name::splitNameParts(new Name('Mike Foo'));
$name->getFirstname();   // Mike
$name->getSurname();     // Foo

$name = Name::swapCommaSeparatedNameParts(new Name('Foo, Mike'));
$name->getFullname();    // Mike Foo
```
