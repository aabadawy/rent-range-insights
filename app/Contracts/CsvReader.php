<?php

namespace App\Contracts;

use Generator;

interface CsvReader
{
    public function read(): Generator;
}
