<?php

namespace Snailmark\Mail;

use Illuminate\Notifications\Messages\MailMessage as Message;

/**
 * A notification MailMessage rendered by a Snailmark template. Return it from
 * a notification's toMail() and set alias()/identifier() plus include().
 */
class TemplatedMailMessage extends Message
{
    protected ?string $alias = null;

    protected array $data = [];

    protected string|int|null $id = null;

    public $view = 'snailmark::template';

    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function data(): array
    {
        return [
            'id' => $this->id,
            'alias' => $this->alias,
            'model' => $this->data,
        ];
    }

    public function identifier(string|int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function include(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
