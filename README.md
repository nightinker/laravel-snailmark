# Laravel Snailmark

A Laravel mail transport for sending email through the [Snailmark](https://snailmark.com) API.

Register the `snailmark` mail driver and keep using `Mail`, `Notification`, and Mailables exactly as you do today — Snailmark handles delivery, templates, message streams, and tracking.

## Installation

```bash
composer require nightinker/laravel-snailmark
```

The service provider is auto-discovered.

## Configuration

Add a `snailmark` entry to the `services` config (`config/services.php`):

```php
'snailmark' => [
    'token' => env('SNAILMARK_TOKEN'),
    // Optional — override for a self-hosted Snailmark instance.
    'base_url' => env('SNAILMARK_BASE_URL', 'https://api.snailmark.com'),
    // Optional — default message stream for every send.
    'message_stream' => env('SNAILMARK_MESSAGE_STREAM', 'outbound'),
],
```

Add the mailer to `config/mail.php` under `mailers`:

```php
'snailmark' => [
    'transport' => 'snailmark',
    // Optional per-mailer stream override.
    'message_stream_id' => env('SNAILMARK_MESSAGE_STREAM'),
],
```

Then point your app at it in `.env`:

```env
MAIL_MAILER=snailmark
SNAILMARK_TOKEN=snl_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS="you@your-verified-domain.com"
MAIL_FROM_NAME="Your App"
```

`SNAILMARK_TOKEN` is a **server token** from the Snailmark dashboard (Server → API Tokens). The `From` address must be on a domain verified in Snailmark.

## Usage

Nothing changes in your app code — send mail as usual:

```php
Mail::to($user)->send(new OrderShipped($order));

$user->notify(new ResetPasswordNotification($token));
```

### Overriding the server token per message

Attach a `SnailmarkServerTokenHeader` to send a single message through a
different Snailmark server (useful for multi-tenant apps):

```php
use Snailmark\Mail\SnailmarkServerTokenHeader;

Mail::to($user)->send(
    (new OrderShipped($order))->withSymfonyMessage(function ($message) use ($token) {
        $message->getHeaders()->add(new SnailmarkServerTokenHeader($token));
    })
);
```

### Sending a Snailmark template

Render the email from a template stored in Snailmark instead of a Blade view. From a
mailable:

```php
use Snailmark\Mail\TemplatedMailable;

Mail::to($user)->send(
    (new TemplatedMailable)
        ->alias('LB_FORM_READY')            // or ->identifier($templateId)
        ->include(['name' => $user->first_name, 'document' => $document->name])
);
```

From a notification, return a `TemplatedMailMessage` from `toMail()`:

```php
use Snailmark\Mail\TemplatedMailMessage;

public function toMail($notifiable): TemplatedMailMessage
{
    return (new TemplatedMailMessage)
        ->alias('LB_MAGIC_LOGIN')
        ->include(['action_url' => $this->url, 'expires_in' => 15]);
}
```

Both post to `POST /api/email/withTemplate` with `TemplateAlias`/`TemplateId` and
`TemplateModel`; the subject and body come from the template.

## Notes

- The transport sends the full message envelope — Cc, Bcc, Reply-To, attachments (inline ones keep their Content-ID), tags, metadata, and custom headers.
- Snailmark also accepts Postmark's `X-Postmark-Server-Token` header and paths, so an app already on `coconutcraig/laravel-postmark` can switch by swapping the driver.

## Testing

```bash
composer install
vendor/bin/phpunit
```

## License

MIT
