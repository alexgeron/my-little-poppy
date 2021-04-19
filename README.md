# My little Poppy

##  Requirements

- [Docker](https://docs.docker.com/engine/installation/) installed
- [Docker Compose](https://docs.docker.com/compose/install/) installed

## Installation

1. Clone this repository
    ```bash
    $ git clone git@github.com:alexgeron/my-little-poppy.git
    ```
2. Update the Docker `.env` file according to your needs. The `NGINX_HOST` environment variable allows you to use a custom server name

3. Add the server name in your system host file

4. Copy the `symfony/.env.dist` file to `symfony/.env`
    ```bash
    $ cp symfony/.env.dist symfony/.env
    ```

5. Build & run containers with `docker-compose` 
    ```bash
    $ docker-compose -f docker-compose.yaml -f docker-compose.mongodb.yaml build
    ```
    then
    ```bash
    $ docker-compose -f docker-compose.yaml -f docker-compose.mongodb.yaml up -d
    ```

6. Composer install

    first, configure permissions on `symfony/var` folder
    ```bash
    $ docker-compose exec app chown -R www-data:1000 var
    ```
    then
    ```bash
    $ docker-compose exec -u www-data app composer install
    ```

## Access the application

You can access the application both in HTTP and HTTPS:

[symfony-docker.localhost](http://symfony-docker.localhost)

**Note:** `symfony-docker.localhost` is the default server name. You can customize it in the `.env` file with `NGINX_HOST` variable.

## Commands

**Note:** `symfony` is the default value for the user, password and database name. You can customize them in the `.env` file.

```bash
# bash
$ docker-compose exec app /bin/bash

# Symfony console
$ docker-compose exec -u www-data app bin/console

# configure permissions, e.g. on `var/log` folder
$ docker-compose exec app chown -R www-data:1000 var/log

# MongoDB
# access with application account
$ docker-compose -f docker-stack.yaml exec mongodb mongo -u symfony -p symfony --authenticationDatabase symfony
```
