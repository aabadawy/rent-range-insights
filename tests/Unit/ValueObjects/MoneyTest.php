<?php

use App\ValueObjects\Money;

test('it saves float money should be converted to integer', function () {
   expect(($money = Money::make(123.456789))->amount())
       ->toBe(1234567)
       ->and($money->value())->toBe(123.4567);
});
