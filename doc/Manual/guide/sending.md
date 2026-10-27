# Sending

Two transports are available: [`Transport\SMTP`](../api/Transport/SMTP.md) talks to a mail server directly,
[`Transport\Local`](../api/Transport/Local.md) uses PHP's `mail()` function (sendmail).
Both return a list of [`Transport\Result`](../api/Transport/Result.md) objects – one per recipient.

## SMTP

=== "STARTTLS (port 587)"

    ```php
    use CeusMedia\Mail\Transport\SMTP;

    $transport = SMTP::getInstance('smtp.example.com', 587)
    	->setAuth('john@example.com', 'my_password');
    $results = $transport->send($message);
    ```

=== "Implicit TLS (port 465)"

    ```php
    use CeusMedia\Mail\Transport\SMTP;

    $transport = SMTP::getInstance('smtp.example.com', 465)   // implicit TLS detected by port
    	->setAuth('john@example.com', 'my_password');
    $results = $transport->send($message);
    ```

=== "Plain (port 25, testing)"

    ```php
    use CeusMedia\Mail\Transport\SMTP;

    $transport = SMTP::getInstance('localhost', 25)
    	->setSecure(FALSE);
    $results = $transport->send($message);
    ```

### Options

```php
$transport
	->setHeloHost('app.example.com')     // name of this client, detected if not set
	->setReadTimeout(10)                 // seconds to wait for a server response, default: 30
	->setSecure(TRUE)                    // encrypt connection, validate certificate
	->setImplicitTls(FALSE)              // STARTTLS instead of implicit TLS, independent of port
	->setVerbose(TRUE);                  // print SMTP dialog, credentials are hidden
```

!!! example "Own certificate authority"
    ```php
    $transport->setSslOptions(['cafile' => '/path/to/ca.pem']);
    ```

!!! danger "Self-signed certificates"
    Switching off certificate validation makes the connection vulnerable to man-in-the-middle attacks.
    Use it for testing only:
    ```php
    $transport->setSecure(TRUE, FALSE);
    ```

### Errors

Connection problems (host not reachable, timeout, failed TLS handshake, login failed, …) throw a
[`Transport\SMTP\Exception`](../api/Transport/SMTP/Exception.md), which carries the last
[`SMTP\Response`](../api/Transport/SMTP/Response.md):

```php
use CeusMedia\Mail\Transport\SMTP\Exception as SmtpException;
use CeusMedia\Mail\Transport\SMTP\Response;

try {
	$results = $transport->send($message);
}
catch (SmtpException $e) {
	$response = $e->getResponse();
	if ($response?->error === Response::ERROR_TIMEOUT)
		echo 'Server did not answer in time', PHP_EOL;
	echo $e->getMessage(), PHP_EOL;
}
```

## Local transport (sendmail)

```php
use CeusMedia\Mail\Transport\Local;

$results = (new Local())->send($message);

//  with additional parameters for sendmail, e.g. the envelope sender
$results = (new Local())->send($message, ['-fbounce@example.com']);
```

On Unix, recipients (To, Cc, Bcc) are read from the headers by sendmail,
so the configured `sendmail_path` must contain the option `-t` (PHP default: `sendmail -t -i`).
Sendmail removes the Bcc header before delivery.

## Results

```php
use CeusMedia\Mail\Transport\Result;

foreach ($results as $result) {
	match ($result->getStatus()) {
		Result::STATUS_SUCCESS => printf("✔ %s\n", $result->getReceiver()),
		default                => printf("✘ %s: %d %s\n",
			$result->getReceiver(),
			$result->getCode(),
			$result->getMessage()
		),
	};
}
```

| Status | Meaning |
|--------|---------|
| `Result::STATUS_SUCCESS` (`ok`) | message accepted by the server for this recipient |
| `Result::STATUS_ERROR` (`error`) | rejected by the server (recipient or message), see code and message |
| `Result::STATUS_FAILURE` (`failed`) | unexpected failure while sending, e.g. lost connection |
| `Result::STATUS_UNKNOWN` (`unknown`) | not determined |

If no recipient is accepted, the message is not sent at all.
