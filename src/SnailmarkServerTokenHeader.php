<?php

namespace Snailmark\Mail;

use Symfony\Component\Mime\Header\UnstructuredHeader;

/**
 * Attach this header to a message to override the configured Snailmark server
 * token for that single send (e.g. a multi-tenant app sending on behalf of
 * different Snailmark servers).
 */
class SnailmarkServerTokenHeader extends UnstructuredHeader
{
    public const NAME = 'X-Snailmark-Server-Token';

    public function __construct(string $value)
    {
        parent::__construct(self::NAME, $value);
    }
}
