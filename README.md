# Rent Range Insights

A Laravel-based REST API for querying Paris rental data based on the French rent control law (`encadrement des loyers`). Built with clean architecture principles using KISS, Value Objects, and CQRS-style separation between write and read operations.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Architecture](#architecture)
- [Database Schema](#database-schema)
- [Importing Data](#importing-data)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Performance](#performance)
- [Future Enhancements](#future-enhancements)

---

## Overview

Since July 1, 2019, Paris has implemented mandatory rent control regulations that cap maximum rents based on location, room count, construction period, and furnishing status. This API provides programmatic access to these rent ranges for property platforms, real estate agencies, and tenants.

### Key Features

- ✅ **Geospatial Queries**: MySQL spatial indexes for coordinate-based lookups
- ✅ **Postal Code Search**: Fast cached lookups for Paris districts
- ✅ **Multi-Filter Support**: Combine location, rooms, construction period, and furnishing
- ✅ **Clean Architecture**: Value Objects, CQRS pattern, domain-driven design
- ✅ **Type Safety**: Strict PHP typing with immutable Value Objects
- ✅ **Import Commands**: Automated CSV/Excel data import

---

## Installation

### Prerequisites

- PHP 8.2 or higher
- MySQL 8.0 or higher (with spatial support)
- Composer 2.x

### Setup Steps

1. **Clone the repository:**

```bash
git clone https://github.com/aabadawy/rent-range-insights.git
cd rent-range-insights
```

2. **Configure environment:**

```bash
cp .env.example .env
```

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rent_insights
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

3. **Install dependencies:**

```bash
composer install
```

4. **Generate application key:**

```bash
php artisan key:generate
```

5. **Create database:**

```bash
mysql -u root -p -e "CREATE DATABASE rent_insights CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

6. **Run migrations:**

```bash
php artisan migrate
```

7. **Import data:**

```bash
# Import districts (administrative boundaries)
php artisan data:import --districts

# Import rental units (rent control data)
php artisan data:import --units
```

8. **Start the server:**

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### Verify Installation

```bash
curl -X GET "http://localhost:8000/api/rent-insights?postal_code=75014" \
     -H "Accept: application/json"
```

---

## Architecture

### Design Principles

#### 1. **KISS (Keep It Simple, Stupid)**

- Simple, readable code without over-engineering
- Direct database queries without unnecessary abstractions
- Pragmatic solutions over complex patterns

#### 2. **Value Objects (VOs)**

Domain primitives encapsulating validation, computation, and formatting:

- **`Money`**: Represents monetary values with currency
- **`GeometryPoint`**: Single coordinate (latitude, longitude)
- **`GeometryShape`**: Polygon boundaries for districts
- **`Coordinates`**: Immutable coordinate pairs with validation

**Example:**

```php
$price = Money::fromCents(2300, 'EUR'); // €23.00/m²
$point = GeometryPoint::fromLatLng(48.8566, 2.3522);
$coords = Coordinates::fromString("48.8566,2.3522");
```

**Benefits:**
- ✅ Type safety: Invalid data cannot exist
- ✅ Immutability: Prevents accidental modification
- ✅ Encapsulation: Logic stays with data
- ✅ Reusability: Used across commands and queries

#### 3. **CQRS (Command Query Responsibility Segregation)**

**Commands** (Write Operations):
- Handle data imports and side effects
- Examples: `ImportDistrictsCommand`, `ImportRentDataCommand`
- Modify database state
- No return values

**Queries** (Read Operations):
- Handle read-only operations
- Examples: `RentInsightsQuery`, `DistrictLookupQuery`
- Never modify state
- Return structured data

**Benefits:**
- ✅ Clear separation of concerns
- ✅ Easier testing and optimization
- ✅ Independent scaling of reads and writes

### Request Flow

```
HTTP Request
    ↓
RentInsightsRequest (validation)
    ↓
RentInsightsController (thin layer)
    ↓
RentInsightsQueryHandler
    ↓
Repository → MySQL (spatial query)
    ↓
Value Objects (Money, GeometryPoint)
    ↓
JSON Response
```

### Directory Structure

```
app/
├── Console/
│   └── Commands/
│       ├── ImportDistrictsCommand.php    # Import district data
│       └── ImportRentDataCommand.php     # Import rent control data
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── RentInsightsController.php
│   └── Requests/
│       └── RentInsightsRequest.php       # Validation rules
├── Models/
│   ├── District.php                      # District Eloquent model
│   └── Unit.php                          # Rental unit model
├── Queries/
│   └── RentInsightsQueryHandler.php      # Read logic
├── ValueObjects/
│   ├── GeometryPoint.php                 # Single point
│   ├── GeometryShape.php                 # Polygon
│   └── Money.php                         # Monetary value
```

---

## Database Schema

### `districts` Table

Stores Paris district administrative and geographic information.

| Column | Type | Description | Indexed |
|--------|------|-------------|---------|
| `id` | bigint | Primary key | ✓ |
| `district_section_number` | varchar | Unique section identifier | ✓ (unique) |
| `district_number` | int | District number | ✓ |
| `insee_code` | varchar | INSEE code | ✓ |
| `district_name` | varchar | District name | |
| `borough_code` | varchar | Borough code | |
| `latitude` | decimal(10,8) | Center latitude | ✓ |
| `longitude` | decimal(11,8) | Center longitude | ✓ |
| `postal_code` | varchar(5) | Postal code | ✓ |
| `created_at` | timestamp | Creation time | |
| `updated_at` | timestamp | Update time | |

### `units` Table (Rent Data)

Contains rent control data with spatial geometry.

| Column | Type | Description | Indexed |
|--------|------|-------------|---------|
| `id` | bigint | Primary key | ✓ |
| `district_number` | int | Foreign key to districts | ✓ |
| `district_name` | varchar | District name | |
| `number_of_rooms` | int | Number of rooms (1-6) | ✓ |
| `construction_period` | smallint | Period code (1-4) | ✓ |
| `rental_type` | boolean | 1=furnished, 0=unfurnished | ✓ |
| `reference_rent` | bigint | Average rent (cents/m²) | |
| `maximum_rent` | bigint | Legal maximum (cents/m²) | ✓ |
| `minimum_rent` | bigint | Reference minimum (cents/m²) | ✓ |
| `year` | year | Data year | ✓ |
| `geometry_shape` | geometry | District boundary polygon (SRID 4326) | ✓ (spatial) |
| `unit_md5` | binary | a unique key combining the unit data to prevent duplicates | ✓ |
| `latitude` | decimal(10,8) | Center latitude | ✓ |
| `longitude` | decimal(11,8) | Center longitude | ✓ |
| `created_at` | timestamp | Creation time | |
| `updated_at` | timestamp | Update time | |

**Composite Index:** `units_search_idx` on `(district_number, number_of_rooms, construction_period, rental_type)`

### Construction Period Codes

| Code | Period | Description |
|------|--------|-------------|
| `1` | `before_1946` | Built before 1946 |
| `2` | `1946_1970` | Built 1946-1970 |
| `3` | `1971_1990` | Built 1971-1990 |
| `4` | `after_1990` | Built after 1990 |

---

## Importing Data

### Import Districts

```bash
php artisan data:import --districts
```

Imports district administrative boundaries from `Dataset/districts.csv`

**CSV Format:**
```csv
district_section_number,district_number,insee_code,district_name,borough_code,latitude,longitude,postal_code
750000029,29,7510801,Champs-Élysées,8,48.8670,2.3086,75008
```

### Import Rental Units

```bash
php artisan data:import --units
```

Imports rent control data from `Dataset/rent_data.csv`

**CSV Format:**
```csv
district_number,district_name,number_of_rooms,construction_period,rental_type,reference_rent,maximum_rent,minimum_rent,year,latitude,longitude,geo_shape
50,Gare,1,4,0,2300,2760,1610,2019,48.8275,2.3723,"{\"type\":\"Polygon\",\"coordinates\":[...]}"
```

**Features:**
- ✅ Validates data before import
- ✅ Parses GeoJSON polygons
- ✅ Progress indicators
- ✅ Error logging

**Options:**
```bash
# Import with custom batch size
php artisan data:import --units --batch=500

# Dry run (preview without importing)
php artisan data:import --units --dry-run

# Truncate existing data
php artisan data:import --units --fresh
```

---

## API Documentation

### Endpoint: Get Rent Insights

**GET** `/api/rent-insights`

Returns minimum, average, and maximum rent for a location with optional filters.

#### Query Parameters

**Location** (postal_code or coordinates should be filled):

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `postal_code` | string | conditional | Paris postal code (75001-75020) | `75014` |
| `coordinates` | string | conditional | Comma-separated lat,lng | `48.8566,2.3522` |
| `latitude` | float | conditional | Latitude (requires longitude) | `48.8566` |
| `longitude` | float | conditional | Longitude (requires latitude) | `2.3522` |

#### Examples


##### Example 1: Full Query with Filters & postal_code

```bash
curl -X GET "http://localhost:8000/api/rent-insights?postal_code=75001&construction_period=1946_1970&number_of_rooms=2&furnished=true" \
     -H "Accept: application/json"
```

##### Example 3: Full Query with Filters & Coordinates

```bash
curl -X GET "http://localhost:8000/api/rent-insights?coordinates=48.8566,2.3522&construction_period=1946_1970&number_of_rooms=2&furnished=true" \
     -H "Accept: application/json"
```

#### Success Response (200 OK)

```json
{
  "data": {
    "max_rent": 1000,
    "min_rent": 10,
    "average_rent": 757,
    "units_count": 3
  }
}
```

**Field Descriptions:**
- `max_rent`: Maximum legal rent in €/m²/month
- `min_rent`: Minimum reference rent in €/m²/month
- `average_rent`: Average reference rent in €/m²/month
- `units_count`: Number of matching rental units

#### Error Responses

##### Validation Error (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "postal_code": [
      "The postal code field is required when coordinates is not present."
    ]
  }
}
```

##### No Data Found (200)

```json
{
  "data": {
    "max_rent": null,
    "min_rent": null,
    "average_rent": null,
    "units_count": 0
  }
}
```

---

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=RentInsightsApiTest
```

### Test Structure

```
tests/
├── Feature/
│   └── ImportCommandsTest.php
|   └── RentInsightsController.php
```

### Example Test

```php
public function test_returns_rent_insights_for_postal_code(): void
{
    // Arrange
    Unit::factory()->create([
        'district_number' => 14,
        'postal_code' => '75014',
        'reference_rent' => 2300, // €23.00/m²
        'maximum_rent' => 2760,   // €27.60/m²
        'minimum_rent' => 1610,   // €16.10/m²
    ]);
    
    // Act
    $response = $this->getJson('/api/rent-insights?postal_code=75014');
    
    // Assert
    $response->assertOk()
        ->assertJson([
            'data' => [
                'max_rent' => 27.6,
                'min_rent' => 16.1,
                'average_rent' => 23.0,
                'units_count' => 1,
            ]
        ]);
}
```

---

## Performance

### Query Optimization

| Query Type | Avg Time | Strategy |
|------------|----------|----------|
| Postal code | 0.5-1ms | Index + cache |
| Coordinates | 3-8ms | Spatial index |
| Filtered | 5-15ms | Composite index |

### Key Optimizations

1. **Spatial Index**: Critical for coordinate queries
```sql
CREATE SPATIAL INDEX spatial_idx ON units(geometry_shape);
```

2. **Composite Index**: Optimizes filtered searches
```sql
CREATE INDEX rent_search_index ON units(
    district_number, number_of_rooms, 
    construction_period, rental_type
);
```

---

## Future Enhancements

### Short-term (Next Release)

- [ ] **API Response Caching**: Cache rent insights for 1 hour to reduce database load
- [ ] **Rate Limiting**: Implement throttling (60 requests/minute per IP)
- [ ] Unit tests for VOs and Query classes
