## Rent Range Insights

A Laravel-based service to import and query rental data, with a clean architecture approach using KISS, Value Objects, and a CQRS-style separation between write and read operations.

## Table of Contents
1. Installation
2. Architecture
3. Database Schema
4. Importing Data
5. API
6. Testing
7. Future Enhancements

___

## Installation

1. Clone the repository:

```bash
git clone https://github.com/aabadawy/rent-range-insights.git
cd rent-range-insights
```
2. Copy .env.example and configure your environment:
```bash
cp .env.example .env
```
3. Install PHP dependencies:
```bash
composer install
```
4. Generate application key:
```bash
php artisan key:generate
```
5. Run migrations:
```bash
php artisan migrate
```
6. Import rental and district data:
```bash
php artisan data:import --districts
php artisan data:import --units
```
7. Start the local server:
```bash
php artisan serve
```
