<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface BankAccountProjectionInterface
{
    public function getId(): string;
    public function getAccountNumber(): string;
    public function getBankName(): string;
    public function getGLAccountCode(): string;
    public function getCurrency(): string;
}
