---
title: Home
hide:
  - navigation
  - toc
---

<div class="hero" markdown>

# CeusMedia Mail

Produce, send and read mails using PHP – with SMTP, IMAP and a clean, chainable API.

[Get started](guide/quickstart.md){ .md-button .md-button--primary }
[API Reference](api/index.md){ .md-button }
[GitHub](https://github.com/CeusMedia/Mail){ .md-button }

</div>

```php
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Transport\SMTP;

SMTP::getInstance('smtp.example.com', 587)
	->setAuth('john@example.com', 'my_password')
	->send(Message::getInstance()
		->setSender('john@example.com', 'John Doe')
		->addRecipient('mike@example.com', 'Mike Foo')
		->setSubject('Grüße aus Köln')
		->addText('Hello Mike!')
		->addHTML('<h1>Hello Mike!</h1>')
	);
```

<div class="grid cards" markdown>

-   :material-email-edit-outline:{ .lg .middle } __Compose messages__

    ---

    Plain text, HTML, attachments, inline images and attached mails – encoded automatically by MIME, RFC 2047 and RFC 2231.

    [:octicons-arrow-right-24: Messages](guide/messages.md)

-   :material-send-lock-outline:{ .lg .middle } __Send securely__

    ---

    SMTP with STARTTLS or implicit TLS, certificate validation, own CA files, timeouts and a result per recipient.

    [:octicons-arrow-right-24: Sending](guide/sending.md)

-   :material-inbox-arrow-down-outline:{ .lg .middle } __Read mailboxes__

    ---

    Access IMAP and POP3 mailboxes, search, move, flag and upload mails, parse them into message objects.

    [:octicons-arrow-right-24: Mailbox](guide/mailbox.md)

-   :material-shield-check-outline:{ .lg .middle } __Safe by design__

    ---

    Protection against header injection, Bcc never rendered, strict parsing of addresses and headers.

    [:octicons-arrow-right-24: Headers & encoding](guide/headers.md)

-   :material-account-check-outline:{ .lg .middle } __Check addresses__

    ---

    Validate syntax, ask the receiving server for availability, resolve MX records and read DMARC policies.

    [:octicons-arrow-right-24: Checks & utilities](guide/checks.md)

-   :material-book-open-variant:{ .lg .middle } __Full API reference__

    ---

    Every class, constant and method – generated from the source code, with examples for the main classes.

    [:octicons-arrow-right-24: API Reference](api/index.md)

</div>

## Requirements

- PHP 8.1 or newer
- Extensions: `dom`, `fileinfo`, `iconv`, `imap` (for mailbox access), `json`, `mbstring`

```sh
composer require ceus-media/mail
```

License: [GPL-3.0-or-later](https://www.gnu.org/licenses/gpl-3.0.txt)
