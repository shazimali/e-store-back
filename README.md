## docker-compose-laravel-api

A pretty simplified Docker Compose workflow of Laravel API starter Kit will provide you with the tools for making API's that everyone will love, API Authentication & Permissions are already provided with Laravel sacntum. 

## Usage

To get started, make sure you have [Docker installed](https://docs.docker.com/docker-for-mac/install/) on your system, and then clone this repository.

Create new .env file and copy all code from .env.example to it, then fill the variables with following values you can you change variables values as per your suit.

```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST= mysqldb // database container name
DB_PORT=3306
DB_DATABASE=laravel_database
DB_USERNAME=root
DB_PASSWORD=root

```

```bash
docker-compose --env-file .env  up -d
```
Once docker all container run successfully you will see below text in yor terminal

Container phpmyadmin     Started                                                                      
Container mysql_db       Started                                                                   Container app-docker     Started


For goto project container, you need to run the below command
`docker-compose exec -it app-docker bash`

once in come into app-docker container please run these below commands

```
composer update
php artisan migrate
php artisan  db:seed
```

Run `composer update` for getting vendor files 
Run `php artisan migrate` for run all migrations
Run `php artisan db:seed` and you should have a new user with the roles and permissions set up

## Tests

Navigate to the project root and run `vendor/bin/phpunit` after installing all the composer dependencies and after the .env file was created.

## Project & PhpMyAdmin Links 

project on http://localhost:8000/
phpMyAdmin on http://localhost:8001/