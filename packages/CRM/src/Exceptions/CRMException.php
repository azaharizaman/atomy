<?php

declare(strict_types=1);

namespace Nexus\CRM\Exceptions;

/**
 * Base CRM Exception
 * 
 * Base exception class for all CRM package exceptions.
 * 
 * @package Nexus\CRM\Exceptions
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
class CRMException extends \Exception
{
    /**
     * @param string $message Exception message
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}