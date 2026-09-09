<?php

namespace Snailmark\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * A mailable rendered by a Snailmark template (alias or id) with a model, sent
 * through POST /api/email/withTemplate. Mirrors laravel-postmark's TemplatedMailable.
 */
class TemplatedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $alias = null;

    public string|int|null $id = null;

    public array $model = [];

    public function build(): self
    {
        return $this->view('snailmark::template');
    }

    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function identifier(string|int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function include(array $data): self
    {
        $this->model = $data;

        return $this;
    }
}
