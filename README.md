## Credits
To help setting up this project, I used [the following repo](https://github.com/guham/symfony-docker) 

1. Clone this repository
    ```bash
    $ git clone git@github.com:alexgeron/my-little-poppy.git
    ```
2. Copy the symfony/.env.dist file to symfony/.env
    ```bash
    $ cp symfony/.env.dist symfony/.env
    ```
3. Build & run containers with `docker-compose` 
    ```bash
    $ docker-compose -f docker-compose.yaml -f docker-compose.mongodb.yaml build
    ```
    then
    ```bash
    $ docker-compose -f docker-compose.yaml -f docker-compose.mongodb.yaml up -d
    ```

4. Composer install

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

[my-little-poppy.localhost](http://my-little-poppy.localhost:8080)

## Commands

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
