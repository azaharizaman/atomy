<?php

declare(strict_types=1);

namespace App\Support\SeedData\Quotations;

use Illuminate\Support\Facades\File;

final class SimplePdfWriter
{
    /**
     * @param list<string> $lines
     */
    public function write(string $path, array $lines): void
    {
        File::ensureDirectoryExists(dirname($path));

        $content = "BT\n/F1 12 Tf\n50 760 Td\n";

        foreach (array_values($lines) as $index => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);

            if ($index > 0) {
                $content .= "0 -16 Td\n";
            }

            $content .= sprintf("(%s) Tj\n", $escaped);
        }

        $content .= "ET\n";

        File::put($path, $this->wrapPdfObjects($content));
    }

    private function wrapPdfObjects(string $content): string
    {
        $objects = [
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Count 1 /Kids [3 0 R] >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
            4 => "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream",
            5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
        ];

        $pdf = "%PDF-1.4\n% seed quotation fixture\n";
        $offsets = [0];

        foreach ($objects as $objectNumber => $objectBody) {
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $objectNumber . " 0 obj\n" . $objectBody . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($objectNumber = 1; $objectNumber <= count($objects); $objectNumber++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$objectNumber]) . "\n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF\n";

        return $pdf;
    }
}
