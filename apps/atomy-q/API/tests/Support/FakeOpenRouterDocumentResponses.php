<?php

declare(strict_types=1);

namespace Tests\Support;

final class FakeOpenRouterDocumentResponses
{
    /**
     * @return array<string, mixed>
     */
    public static function successfulQuoteExtraction(): array
    {
        return [
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'vendor_name' => 'Kuching Utama Sdn Bhd',
                        'quote_number' => '1809/2975',
                        'currency' => 'RM',
                        'total_amount' => 84.8,
                        'line_items' => [[
                            'description' => 'BREAKDOWN ASSIST JUMP START VEHICLE',
                            'quantity' => 1,
                            'unit_price' => 80,
                            'total' => 84.8,
                        ]],
                        'payment_terms' => '50% deposit required before work can be carried out',
                        'validity' => '30 days',
                    ], JSON_THROW_ON_ERROR),
                ],
            ]],
        ];
    }
}
