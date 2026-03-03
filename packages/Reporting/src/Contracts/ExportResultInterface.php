<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

interface ExportResultInterface
{
    public function getFilePath(): string;

    public function getFileSize(): int;
}
