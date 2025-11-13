<?php

namespace App\Console\Commands;

use App\Enums\ConstructionPeriodEnum;
use App\Models\District;
use App\Models\RentData;
use App\ValueObjects\GeometryPoint;
use App\ValueObjects\GeometryShape;
use App\ValueObjects\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportDataCommand extends Command
{
    protected $signature = 'data:import
                            {--districts : Import only districts data}
                            {--rent : Import only rent data}
                            {--force : Force reimport (truncate tables)}';

    protected $description = 'Import rent control data from CSV files with idempotency and memory optimization';

    private const DISTRICTS_CSV = 'Dataset/quartier_paris.csv';

    private const RENT_CSV = 'Dataset/logement-encadrement-des-loyers.csv';

    private int $importedCount = 0;

    private int $skippedCount = 0;

    private int $failedCount = 0;

    public function handle(): int
    {
        $this->info('🚀 Starting import process...');
        $startTime = microtime(true);

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
                $this->importRentData();
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->displaySummary($duration);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Import failed: {$e->getMessage()}");
            Log::error('Import command failed', [
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
        $this->info("\n📍 Importing districts...");
        $this->resetCounters();

        $csvPath = base_path($this->getDistrictCsvFilePath());
        if (! file_exists($csvPath)) {
            throw new \Exception("Districts CSV file not found: {$csvPath}");
        }

        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file, 0, ';'); // Semicolon delimiter
        $batch = [];
        $rowNumber = 1;

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            $rowNumber++;

            try {
                $data = array_combine($headers, $row);

                // Check if district already exists (idempotency)
                $districtNumber = (int) $data['Numéro du quartier / C_QU'];
                if (District::where('district_number', $districtNumber)->exists()) {
                    $this->skippedCount++;

                    continue;
                }

                // Parse coordinates
                $coordinates = str_contains($data['Geometry X Y'], ',') ?
                    explode(',', $data['Geometry X Y']) : explode(';', $data['Geometry X Y']);
                $latitude = isset($coordinates[0]) ? (float) trim($coordinates[0]) : null;
                $longitude = isset($coordinates[1]) ? (float) trim($coordinates[1]) : null;

                $geoPoint = GeometryPoint::make($latitude, $longitude);
                $data = [
                    'district_section_number' => $data['N_SQ_QU'],
                    'district_number' => $districtNumber,
                    'insee_code' => $data['C_QUINSEE'],
                    'district_name' => $data['L_QU'],
                    'borough_code' => $data['C_AR'],
                    'borough_section_number' => $data['N_SQ_AR'],
                    'perimeter' => $data['PERIMETRE'] ?: null,
                    'surface_area' => $data['SURFACE'] ?: null,
                    'geometry_coordinates' => $data['Geometry X Y'],
                    'latitude' => $geoPoint->latitude,
                    'longitude' => $geoPoint->longitude,
                    'postal_code' => $data['ZIP CODE'],
                ];
                District::create($data);

                $this->importedCount++;
            } catch (\Exception $e) {
                $this->failedCount++;
                logger()->error("Failed to import district row {$rowNumber}", [
                    'error' => $e->getMessage(),
                    'row' => $row,
                ]);
            }
        }

        fclose($file);
        $this->info("✅ Districts: {$this->importedCount} imported, {$this->skippedCount} skipped, {$this->failedCount} failed");
    }

    private function importRentData(): void
    {
        $this->info("\n💰 Importing rent data...");
        $this->resetCounters();

        $csvPath = base_path($this->getRentCsvFilePath());
        if (! file_exists($csvPath)) {
            throw new \Exception("Rent data CSV file not found: {$csvPath}");
        }

        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file, 0, ';');
        $rowNumber = 1;
        $processedHashes = []; // Track processed rows in current session

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            $rowNumber++;

            try {
                $data = array_combine($headers, $row);

                $constructionPeriod = $this->getConstructionPeriod($data['Epoque de construction']);
                // Create unique hash to prevent duplicates
                $uniqueHash = md5(
                    $data['Numéro du quartier'].
                    $data['Nombre de pièces principales'].
                    $constructionPeriod->value.
                    $data['Type de location'].
                    $data['Année']
                );

                // Check if already processed in this session
                if (isset($processedHashes[$uniqueHash])) {
                    $this->skippedCount++;

                    continue;
                }

                // Check if exists in database (idempotency)
                $exists = RentData::where('district_number', (int) $data['Numéro du quartier'])
                    ->where('number_of_rooms', (int) $data['Nombre de pièces principales'])
                    ->where('construction_period', $constructionPeriod)
                    ->where('rental_type', $data['Type de location'] === 'meublé')
                    ->where('year', (int) $data['Année'])
                    ->exists();

                if ($exists) {
                    $this->skippedCount++;
                    $processedHashes[$uniqueHash] = true;

                    continue;
                }

                $geoPoint = GeometryPoint::make($data['geo_point_2d']);

                $data = [
                    'geographic_sector' => $data['Secteurs géographiques'] ?: null,
                    'district_number' => (int) $data['Numéro du quartier'],
                    'district_name' => $data['Nom du quartier'],
                    'number_of_rooms' => (int) $data['Nombre de pièces principales'],
                    'construction_period' => $constructionPeriod,
                    'rental_type' => $data['Type de location'] === 'meublé',
                    'reference_rent' => Money::make(data_get($data, 'Loyers de référence', 0)),
                    'maximum_rent' => Money::make(data_get($data, 'Loyers de référence majorés', 0)),
                    'minimum_rent' => Money::make(data_get($data, 'Loyers de référence minorés', 0)),
                    'year' => (int) $data['Année'],
                    'city' => $data['Ville'],
                    'geometry_shape' => GeometryShape::fromJson($data['geo_shape']),
                    'geometry_point' => $geoPoint,
                ];

                $processedHashes[$uniqueHash] = true;

                RentData::create($data);

                $this->importedCount++;
            } catch (\Exception $e) {
                $this->failedCount++;

                logger()->error("Failed to import rent data row {$rowNumber}", [
                    'error' => $e->getMessage(),
                    'row' => $row,
                ]);
            }
        }

        fclose($file);
        $this->info("✅ Rent data: {$this->importedCount} imported, {$this->skippedCount} skipped, {$this->failedCount} failed");
    }

    private function resetCounters(): void
    {
        $this->importedCount = 0;
        $this->skippedCount = 0;
        $this->failedCount = 0;
    }

    private function displaySummary(float $duration): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('📊 Import Summary');
        $this->info('═══════════════════════════════════════');
        $this->info("⏱️  Duration: {$duration}s");
        $this->info("✅ Total imported: {$this->importedCount}");
        $this->info("⏭️  Total skipped: {$this->skippedCount}");
        $this->info("❌ Total failed: {$this->failedCount}");
        $this->info('═══════════════════════════════════════');
    }

    private function getRentCsvFilePath(): string
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
}
