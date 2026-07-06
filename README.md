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

## Notes

- The transport sends the full message envelope — Cc, Bcc, Reply-To, attachments, tags, metadata, and custom headers.
- Templated sends: encode `{"alias": "...", "model": {...}}` as the message HTML body to hit `POST /api/email/withTemplate`.

## License

MIT
