<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Enums;

/**
 * Output formats for financial statements.
 */
enum StatementFormat: string
{
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';
    case JSON = 'json';
    case XML = 'xml';
    case HTML = 'html';
}
