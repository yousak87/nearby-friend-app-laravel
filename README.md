# Laravel User Management API

## Project Overview
This Laravel-based API provides comprehensive user management with social features including:
- User registration and authentication
- Profile management
- Follow/unfollow system
- Search by username
- Nearby friends based on geolocation
- Paginated user listings

## System Requirements
- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Node.js (optional for frontend)
- laravel v10

## Installation Guide

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/laravel-user-api.git
cd laravel-user-api
```

### 2. Install Dependencies
```sh
composer install
```

### 3. Configure Environment
Edit the .env file in the root directory with your database credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
PDB_PORT=[3306]
DB_DATABASE=nearby_friends_app
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Initialize Database
```
//create table
php artisan migrate

//insert sample data to users table
php artisan db:seed --class=UserSeeder

//insert sample data to relationships table
php artisan db:seed --class=RelationshipSeeder
```

### 5. Start Development Server
```
php artisan serve
```



## API Endpoints
APIs will be available at: http://localhost:8000/api.
<br>
please use the posman colletion to test the running service

## Testing
```
php artisan test
```

Test coverage includes:
- User registration and authentication
- CRUD operations
- Follow/unfollow functionality
- Search features
- Nearby friends calculation

## Logging
the log file can be found under <b>storage/logs/laravel.log </b>

## Postman Collection
Import `RydePHP.postman_collection.json` under this project root folder for pre-configured API requests

## License
MIT License - See LICENSE file for details
