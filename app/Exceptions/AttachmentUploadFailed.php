<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class AttachmentUploadFailed extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
