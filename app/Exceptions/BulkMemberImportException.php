<?php

namespace App\Exceptions;

use Exception;

class BulkMemberImportException extends Exception
{
    protected array $errors;

    public function __construct(
        string $message,
        array $errors = []
    ) {
        parent::__construct($message);

        $this->errors = $errors;
    }

    /**
     * Return row-level validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Backward compatibility with existing CSV import code.
     */
    public function rows(): array
    {
        return $this->errors;
    }
}
