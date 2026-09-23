<?php

namespace App\Exceptions;

use Exception;

class BulkMemberImportException extends Exception
{
    public function __construct(
        string $message,
        protected array $rows = []
    ) {
        parent::__construct($message);
    }

    public function rows(): array
    {
        return $this->rows;
    }
}
