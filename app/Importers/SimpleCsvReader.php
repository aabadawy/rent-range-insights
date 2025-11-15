<?php

namespace App\Importers;

use App\Contracts\CsvReader;

readonly class SimpleCsvReader implements CsvReader
{
    public function __construct(
        private string $path,
        private string $delimiter = ';'
    ) {}

    public function read(): \Generator
    {
        $file = fopen($this->path, 'r');
        $headers = fgetcsv($file, 0, $this->delimiter);

        while (($row = fgetcsv($file, 0, $this->delimiter)) !== false) {
            yield array_combine($headers, $row);
        }

        fclose($file);
    }
}
