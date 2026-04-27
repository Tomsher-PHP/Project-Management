# Project-Management

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Installation & Setup

Follow these steps to get your local development environment up and running.

### 1. Install Dependencies
Clone the repository and install the required PHP and JavaScript packages. 

# Create the database manually (MySQL)
# Example:
# CREATE DATABASE your_database_name;

```bash
# Install PHP dependencies
composer install

# Install Activitylog package
composer require spatie/laravel-activitylog

# Run fresh migrations (⚠️ will reset database)
php artisan migrate

# Seed base data
php artisan db:seed

# Seed dummy/test data (optional)
php artisan db:seed DummyDataSeeder

# Start Laravel development server
php artisan serve

# Start Vite development server
npm install
npm run dev

# Create storage system link for uploaded files
php artisan storage:link