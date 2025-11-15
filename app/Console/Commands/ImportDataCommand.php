<?php

namespace App\Console\Commands;

use App\Enums\ConstructionPeriodEnum;
use App\Importers\SimpleCsvReader;
use App\Models\District;
use App\Models\RentData;
use App\ValueObjects\GeometryPoint;
use App\ValueObjects\GeometryShape;
use App\ValueObjects\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDataCommand extends Command
{
    protected $signature = 'data:import
                            {--districts : Import only districts data}
                            {--rent : Import only rent data}
                            {--force : Force reimport (truncate tables)}';

    protected $description = 'Import rent control data from CSV files with idempotency and memory optimization';

    private const DISTRICTS_CSV = 'Dataset/quartier_paris.csv';

    private const RENT_CSV = 'Dataset/logement-encadrement-des-loyers.csv';

    public function handle(): int
    {
        $this->info('🚀 Starting import process...');

        try {
            $importDistricts = $this->option('districts') || (! $this->option('districts') && ! $this->option('rent'));
            $importRent = $this->option('rent') || (! $this->option('districts') && ! $this->option('rent'));

            if ($this->option('force')) {
                $this->handleForceOption($importDistricts, $importRent);
            }

            if ($importDistricts) {
                $this->importDistricts();
            }

            if ($importRent) {
                $this->importUnits();
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            logger()->error('Import command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function handleForceOption(bool $importDistricts, bool $importRent): void
    {
        if (! $this->confirm('⚠️  Force mode will DELETE all existing data. Continue?', false)) {
            $this->info('Import cancelled.');
            exit(0);
        }

        if ($importRent) {
            DB::table('rent_data')->truncate();
            $this->warn('🗑️  Truncated rent_data table');
        }

        if ($importDistricts) {
            DB::table('districts')->truncate();
            $this->warn('🗑️  Truncated districts');
        }
    }

    private function importDistricts(): void
    {
        $reader = new SimpleCsvReader(base_path($this->getDistrictCsvFilePath()));

        $imported = 0;
        $skipped = 0;

        foreach ($reader->read() as $row) {

            if ($this->createDistrict($row)->wasRecentlyCreated) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        $this->info("✅ Districts: {$imported} imported, {$skipped} skipped.");
    }

    private function importUnits(): void
    {
        $reader = new SimpleCsvReader(base_path($this->getUnitsCsvFilePath()));

        $imported = $skipped = 0;

        foreach ($reader->read() as $row) {
            if ($this->createUnit($row)->wasRecentlyCreated) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        $this->info("✅ Units: {$imported} imported, {$skipped} skipped.");
    }

    private function getUnitsCsvFilePath(): string
    {
        if (app()->runningUnitTests()) {
            return 'tests/Fixtures/rent_data_samples.csv';
        }

        return self::RENT_CSV;
    }

    private function getDistrictCsvFilePath(): string
    {
        if (app()->runningUnitTests()) {
            return 'tests/Fixtures/districts_samples.csv';
        }

        return self::DISTRICTS_CSV;
    }

    private function getConstructionPeriod(string $rawConstructionPeriod): ConstructionPeriodEnum
    {
        return ConstructionPeriodEnum::fromString($rawConstructionPeriod);
    }

    private function createDistrict(array $row): District
    {
        $coordinates = explode(',', $row['Geometry X Y']);

        [$latitude, $longitude] = $coordinates;

        $geoPoint = GeometryPoint::make($longitude, $latitude);

        return District::query()->firstOrCreate(
            [
                'district_number' => $districtNumber = (int) $row['Numéro du quartier / C_QU'],
            ],
            [
                'district_section_number' => $row['N_SQ_QU'],
                'district_number' => $districtNumber,
                'insee_code' => $row['C_QUINSEE'],
                'district_name' => $row['L_QU'],
                'borough_code' => $row['C_AR'],
                'borough_section_number' => $row['N_SQ_AR'],
                'perimeter' => $row['PERIMETRE'] ?: null,
                'surface_area' => $row['SURFACE'] ?: null,
                'geometry_coordinates' => $row['Geometry X Y'],
                'latitude' => $geoPoint->latitude,
                'longitude' => $geoPoint->longitude,
                'postal_code' => $row['ZIP CODE'],
            ]);
    }

    private function createUnit(array $row): RentData
    {
        $constructionPeriod = $this->getConstructionPeriod($row['Epoque de construction']);

        $geoPoint = GeometryPoint::make($row['geo_point_2d']);

        $data = [
            'geographic_sector' => $row['Secteurs géographiques'] ?: null,
            'district_number' => (int) $row['Numéro du quartier'],
            'district_name' => $row['Nom du quartier'],
            'number_of_rooms' => (int) $row['Nombre de pièces principales'],
            'construction_period' => $constructionPeriod,
            'rental_type' => $row['Type de location'] === 'meublé',
            'reference_rent' => Money::make(data_get($row, 'Loyers de référence', 0)),
            'maximum_rent' => Money::make(data_get($row, 'Loyers de référence majorés', 0)),
            'minimum_rent' => Money::make(data_get($row, 'Loyers de référence minorés', 0)),
            'year' => (int) $row['Année'],
            'city' => $row['Ville'],
            'geometry_shape' => GeometryShape::fromJson($row['geo_shape']),
            'geometry_point' => $geoPoint,
        ];

        $uniqueUnitNumber = hex2bin(md5(
            $row['Numéro du quartier'].$row['Nombre de pièces principales'].$constructionPeriod->value.($row['Type de location'] === 'meublé').$row['Année']
        ));

        return RentData::query()->firstOrCreate(['unit_md5' => $uniqueUnitNumber], $data);
    }
}
