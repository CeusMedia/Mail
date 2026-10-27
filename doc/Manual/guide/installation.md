# Installation

## Requirements

| Requirement | Purpose |
|-------------|---------|
| PHP **8.1** or newer | |
| `ext-dom`, `ext-fileinfo`, `ext-iconv`, `ext-json`, `ext-mbstring` | rendering, parsing, MIME type detection |
| `ext-imap` | reading mailboxes (IMAP, POP3), upload of mails |

## Composer

```sh
composer require ceus-media/mail
```

The library follows PSR-4, all classes live in namespace `CeusMedia\Mail`.
Include Composer's autoloader and you are ready:

```php
require_once 'vendor/autoload.php';

use CeusMedia\Mail\Message;

$message = Message::getInstance();
```

!!! tip "Static constructors"
    Most classes provide a static constructor `getInstance()`, which allows method chaining
    right from the start: `Message::getInstance()->setSubject('...')`.
    The older static constructors named `create()` are deprecated.

## Library information

```php
use CeusMedia\Mail\Library;

echo Library::getVersion();    // e.g. 2.7.2
echo Library::getUserAgent();  // used as default user agent of messages
```
