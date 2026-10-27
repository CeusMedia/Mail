```php
use CeusMedia\Mail\Message\Renderer;

file_put_contents('message.eml', Renderer::render($message));
```
