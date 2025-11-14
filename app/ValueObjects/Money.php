<?php

namespace App\ValueObjects;

use App\Casts\MoneyCast;
use App\Enums\Currency;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;

class Money implements Arrayable, Castable, Responsable
{
    protected int $cents;

    const ROUND = 10000;

    public function __construct(protected int|float $rawAmount, protected bool $isCent = true)
    {
        $this->convertToCent();
    }

    public function euro(): float
    {
        return $this->cents / self::ROUND;
    }

    public function toEuro(): float
    {
        return $this->euro();
    }

    public function amount(): int
    {
        return $this->cents;
    }

    private function convertToCent(): void
    {
        if ($this->isCent) {
            $this->cents = (int) $this->rawAmount;

            return;
        }

        $this->cents = (int) ($this->rawAmount * self::ROUND);
    }

    public static function make(int|float $amount, bool $isCent = true): self
    {
        return new self($amount, $isCent);
    }

    public static function castUsing(array $arguments): string
    {
        return MoneyCast::class;
    }

    public function toArray(): array
    {
        return [
            'cents' => $this->cents,
            'euro' => $this->euro(),
            'currency' => Currency::EUR->value,
        ];
    }

    public function toResponse($request)
    {
        return $this->toArray();
    }
}
