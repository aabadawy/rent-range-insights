<?php

use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;

it('should cast money setter to amount', function () {
    $this->model->fill(['amount' => 123.45]);

    expect($this->model->amount)->toBeInstanceOf(Money::class)
        ->and($this->model->amount->amount())->toBe(1234500)
        ->and($this->model->amount->value())->toBe(123.45);
});

it('', function () {
    
});

beforeEach(fn () => $this->model = new class extends Model {
    protected $fillable = ['amount'];
    protected function casts(): array
    {
        return [
            'amount' => Money::class,
        ];
    }
});
