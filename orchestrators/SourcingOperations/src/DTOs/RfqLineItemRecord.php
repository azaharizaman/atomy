<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

final readonly class RfqLineItemRecord
{
    public string $id;
    public string $description;
    public float $quantity;
    public string $uom;
    public float $unitPrice;
    public string $currency;
    public ?string $specifications;
    public int $sortOrder;

    public function __construct(
        string $id,
        string $description,
        float $quantity,
        string $uom,
        float $unitPrice,
        string $currency,
        ?string $specifications,
        int $sortOrder,
    ) {
        if (trim($id) === '') {
            throw new \InvalidArgumentException('Line item id cannot be empty.');
        }

        if (trim($description) === '') {
            throw new \InvalidArgumentException('Description cannot be empty.');
        }

        if (trim($uom) === '') {
            throw new \InvalidArgumentException('Unit of measure cannot be empty.');
        }

        if (trim($currency) === '') {
            throw new \InvalidArgumentException('Currency cannot be empty.');
        }

        $this->id = trim($id);
        $this->description = trim($description);
        $this->quantity = $quantity;
        $this->uom = trim($uom);
        $this->unitPrice = $unitPrice;
        $this->currency = trim($currency);
        $this->specifications = $specifications !== null ? trim($specifications) : null;
        $this->sortOrder = $sortOrder;
    }
}
