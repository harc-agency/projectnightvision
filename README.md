<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>


## Project Night Vision
### Dream analysis and interpretation

Project Night Vision is a web application that allows users to record their dreams and receive an analysis of the dream's meaning. The application uses a database of dream symbols and their meanings to provide the user with an interpretation of their dream. The user can also view their dream history and track patterns in their dreams over time.

## Features

- User authentication
- Dream recording
- Dream analysis
- Dream history
- Dream symbol database

## Installation

1. Clone the repository
2. Install dependencies
```bash
git clone git@github.com:harc-agency/projectnightvision.git

cd projectnightvision

composer install
npm install

cp .env.example .env

# run laradock
docker compose up -d nginx mysql phpmyadmin

# http://localhost - web server
# http://localhost:8081 - phpmyadmin
 
```


