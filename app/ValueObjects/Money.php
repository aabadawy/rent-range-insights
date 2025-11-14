<?php

namespace App\ValueObjects;

use App\Casts\MoneyCast;
use App\Enums\Currency;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;

class Money implements Arrayable, Castable, Responsable
{
    protected int $amount;

    protected Currency $currency;

    const ROUND = 10000;

    public function __construct(int|float $amount, Currency|string $currency = Currency::EUR)
    {
        $this->currency = $currency instanceof Currency ? $currency : Currency::from($currency);

        $this->convertAmountToInt($amount);
    }

    public function value(): float
    {
        return $this->amount / self::ROUND;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    private function convertAmountToInt(int|float $amount): void
    {
        if (is_int($amount)) {
            $this->amount = $amount;

            return;
        }

        $this->amount = (int) ($amount * self::ROUND);
    }

    public static function make(int|float $amount, Currency|string $currency = Currency::EUR): self
    {
        return new self($amount, $currency);
    }

    public static function castUsing(array $arguments): string
    {
        return MoneyCast::class;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount(),
            'value' => $this->value(),
            'currency' => $this->currency->value,
        ];
    }

    public function toResponse($request)
    {
        return $this->toArray();
    }
}
