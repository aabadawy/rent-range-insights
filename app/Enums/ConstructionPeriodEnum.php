<?php

namespace App\Enums;

enum ConstructionPeriodEnum: int
{
    case Before1946 = 1;

    case Between1946And1970 = 2;

    case Between1971And1990 = 3;

    case After1990 = 4;

    public static function fromString($option): self
    {
        return match ($option) {
            'Avant 1946' => ConstructionPeriodEnum::Before1946,
            '1946-1970' => ConstructionPeriodEnum::Between1946And1970,
            '1971-1990' => ConstructionPeriodEnum::Between1971And1990,
            'Apres 1990' => ConstructionPeriodEnum::After1990,
        };
    }

    public static function stringOptions(): array
    {
        return [
            'Avant 1946', '1946-1970',
            '1971-1990', 'Apres 1990',
        ];
    }
}
