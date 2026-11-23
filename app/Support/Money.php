<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    private function __construct(private readonly string $value) {}

    public static function of(string|int|float $value): self
    {
        $normalized = bcadd((string) $value, '0', 4);
        if (bccomp($normalized, '0', 4) < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
        return new self($normalized);
    }

    public function decimal(): string
    {
        return $this->value;
    }

    public function add(self $other): self
    {
        return new self(bcadd($this->value, $other->value, 4));
    }
}
