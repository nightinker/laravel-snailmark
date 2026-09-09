<?php

namespace Snailmark\Mail\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Snailmark\Mail\SnailmarkServerTokenHeader;
use Snailmark\Mail\SnailmarkTransportException;
use Snailmark\Mail\TemplatedMailable;
use Snailmark\Mail\TemplatedMailMessage;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;

class SnailmarkTransportTest extends TestCase
{
    private function fakeAccepted(): void
    {
        Http::fake(['api.snailmark.test/*' => Http::response([
            'MessageID' => '01J0000000000000000000ABCD', 'ErrorCode' => 0, 'Message' => 'OK',
        ])]);
    }

    private function sentRequest(): Request
    {
        $recorded = Http::recorded();
        $this->assertCount(1, $recorded, 'exactly one HTTP call expected');

        return $recorded[0][0];
    }

    public function test_a_plain_mailable_posts_the_postmark_shaped_payload_to_api_email(): void
    {
        $this->fakeAccepted();

        Mail::to('ana@example.com', 'Ana')->cc('ben@example.com')->send(new PlainMail);

        $request = $this->sentRequest();
        $this->assertSame('https://api.snailmark.test/api/email', $request->url());
        $this->assertSame('Bearer snl_test_configured', $request->header('Authorization')[0]);
        $this->assertSame('"Snailmark" <hello@snailmark.test>', $request['From']);
        $this->assertSame('"Ana" <ana@example.com>', $request['To']);
        $this->assertSame('ben@example.com', $request['Cc']);
        $this->assertSame('Plain subject', $request['Subject']);
        $this->assertStringContainsString('<p>Hello</p>', $request['HtmlBody']);
        $this->assertSame('outbound', $request['MessageStream']);
        $this->assertSame('invoice', $request['Tag']);
        $this->assertSame(['invoice' => '42'], $request['Metadata']);
        $this->assertContains(['Name' => 'X-Case', 'Value' => '2026-00042'], $request['Headers']);
        $this->assertSame('receipt.txt', $request['Attachments'][0]['Name']);
        $this->assertSame(base64_encode('paid'), $request['Attachments'][0]['Content']);
        $this->assertSame('text/plain', $request['Attachments'][0]['ContentType']);
    }

    public function test_a_templated_mailable_posts_alias_and_model_to_with_template(): void
    {
        $this->fakeAccepted();

        Mail::to('ana@example.com')->send(
            (new TemplatedMailable)->alias('LB_FORM_READY')->include(['name' => 'Ana', 'document' => 'EX-01'])
        );

        $request = $this->sentRequest();
        $this->assertSame('https://api.snailmark.test/api/email/withTemplate', $request->url());
        $this->assertSame('LB_FORM_READY', $request['TemplateAlias']);
        $this->assertSame(['name' => 'Ana', 'document' => 'EX-01'], $request['TemplateModel']);
        $this->assertArrayNotHasKey('HtmlBody', $request->data());
        $this->assertArrayNotHasKey('Subject', $request->data());
    }

    public function test_a_templated_notification_message_reaches_with_template_too(): void
    {
        $this->fakeAccepted();

        NotificationFacade::route('mail', 'ana@example.com')->notifyNow(new TemplatedNotification);

        $request = $this->sentRequest();
        $this->assertSame('https://api.snailmark.test/api/email/withTemplate', $request->url());
        $this->assertSame('LB_MAGIC_LOGIN', $request['TemplateAlias']);
        $this->assertSame(['expires_in' => 15], $request['TemplateModel']);
    }

    public function test_a_per_message_server_token_header_overrides_the_configured_token_and_never_ships(): void
    {
        $this->fakeAccepted();

        Mail::to('ana@example.com')->send((new PlainMail)->withSymfonyMessage(
            fn ($message) => $message->getHeaders()->add(new SnailmarkServerTokenHeader('snl_test_other'))
        ));

        $request = $this->sentRequest();
        $this->assertSame('Bearer snl_test_other', $request->header('Authorization')[0]);
        $this->assertNotContains(SnailmarkServerTokenHeader::NAME, array_column($request['Headers'], 'Name'));
    }

    public function test_a_rejected_send_throws_with_snailmarks_error_code_and_message(): void
    {
        Http::fake(['api.snailmark.test/*' => Http::response([
            'ErrorCode' => 406, 'Message' => 'Recipient is on the suppression list.',
        ], 422)]);

        try {
            Mail::to('blocked@example.com')->send(new PlainMail);
            $this->fail('expected a transport exception');
        } catch (SnailmarkTransportException $e) {
            $this->assertSame(406, $e->getCode());
            $this->assertSame('Recipient is on the suppression list.', $e->getMessage());
        }
    }
}

class PlainMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Plain subject',
            tags: ['invoice'],
            metadata: ['invoice' => '42'],
            using: [fn ($message) => $message->getHeaders()->addTextHeader('X-Case', '2026-00042')],
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Hello</p>');
    }

    public function attachments(): array
    {
        return [\Illuminate\Mail\Mailables\Attachment::fromData(fn () => 'paid', 'receipt.txt')->withMime('text/plain')];
    }
}

class TemplatedNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): TemplatedMailMessage
    {
        return (new TemplatedMailMessage)->alias('LB_MAGIC_LOGIN')->include(['expires_in' => 15]);
    }
}
