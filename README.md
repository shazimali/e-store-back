# Everyday E-Store Backend API Platform

A robust, enterprise-grade e-commerce backend platform engineered with **Laravel 9**, **PHP 8.2**, **Redis**, and **Docker**. This platform serves as the central API gateway for stock tracking, branches, orders, and regulatory digital compliance.

---

## ⚡ Key Architectural Features

### 1. Resilient Event-Driven Compliance Pipeline (FBR Integration)
* **Chained Reversals & Forwarding**: Integrates with the Pakistan Federal Board of Revenue (FBR) Digital Invoicing system. If an invoice is updated or cancelled, event listeners dynamically trigger transaction reversals (Credit Notes) and chain subsequent forwardings (Debit/Sale notes) through background queues to guarantee zero compliance mismatch.
* **Smart Retry & Backoff Configuration**: Leverages Redis/Horizon queue workers with custom retry rules. Connection/timeout failures are treated as transient and retried using **exponential backoff** (`[30s, 60s, 120s, 300s, 600s]`), while validation/payload errors are classified as permanent, stopping useless retries and logging failure payloads for administrative review.

### 2. Transactional Ledger-Based Inventory Logs
* **Immutable Double-Entry Audits**: Avoids naive mutable counter columns (e.g. `qty = qty - 1`) which cause race conditions and lose history.
* **Dynamic Stock Aggregations**: Computes active inventory balances per branch by aggregating records across `deliverables`, `returns`, and `invoiced` log tables inside strict database transactions (`DB::transaction`), keeping stock audit-ready.

### 3. High Performance & Analytical Query Optimization
* **Report Aggregation**: Dashboard metrics (sales, profit-loss ledger calculations, and branch statistics) are computed efficiently via optimized **SQL Stored Procedures** to keep database overhead minimal.
* **Eager-Loading & Caching**: Prevents N+1 query loops across nested relationships (Invoices ➔ Details ➔ Products ➔ Stores) and caches branch availability dropdowns using Redis to speed up client-side checkout interactions.

### 4. Interactive API Documentation (Swagger UI)
* Self-documenting API structures utilizing OpenAPI specifications.
* Exposes an interactive Swagger UI dashboard locally at `/api/documentation` to test endpoints (requires Sanctum Bearer Token authorization).

---

## 🛠️ Technology Stack
* **Framework**: Laravel 9.x
* **Language**: PHP 8.2
* **Queue & Cache**: Redis / Laravel Horizon
* **Database**: MySQL / PostgreSQL
* **Containerization**: Docker (Multi-stage compile optimization) / Docker Compose / Supervisor

---

## 🚀 Setup & Installation (Docker)

### Prerequisites
Make sure you have [Docker](https://docs.docker.com/) and `docker-compose` installed.

### 1. Environment Setup
Create a `.env` file from the example:
```bash
cp .env.example .env
```
Update your local `.env` variables, including your database settings, Redis connection details, and FBR endpoints.

### 2. Run Containers
Run Docker Compose:
```bash
docker-compose up -d
```
This builds and starts:
* `everyday_store_backend_app` (PHP 8.2 + Apache)
* `everyday_redis` (Queue & Cache driver)

### 3. Initialize Database & Cache
Log into your app container:
```bash
docker-compose exec -it everyday_store_backend_app bash
```
Once inside, install dependencies and run initial migrations/seeds:
```bash
# Install package dependencies
composer install

# Rebuild class-maps
composer dump-autoload

# Setup Database Tables & Seed Permissions/Admin roles
php artisan migrate --seed

# Compile Swagger API Documentation files
php artisan l5-swagger:generate
```

---

## 🧪 Testing Suite

Execute the repository and service feature tests:
```bash
vendor/bin/phpunit
```
The test suite covers:
* Ledger stock calculation accuracy.
* Caching invalidation behavior.
* Paginated repository list filters.

---

## 🔒 Security & Access Control
Authentication is managed via **Laravel Sanctum** token-based security. Client interfaces leverage **RBAC (Role-Based Access Control)** permissions (e.g., `invoice_create`, `invoice_edit`, `invoice_delete`) which are validated dynamically on the server side prior to executing business workflows.