## Requirement 

Docker, chromium-browser

##Process

All commands should run in ./docker directory

## Installation and start docker

First, to build images run

```bash
docker-compose build
```

then, to start docker containers run

```bash
docker-compose up -d
```


## Chromium container

Then run

```bash
docker exec -it docker_chromium_1 bash
```

and new terminal start inside Chromium container

Then, to start Chromium browser run 

```bash
/usr/bin/chromium-browser --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222
```


## PHP container and start scraper

In new terminal run

```bash
docker exec -it docker_php_1 bash
```

and terminal opens inside php container

Then in /var/www/angel directory run

```bash
composer install
```

then,


```bash
php bin/console doctrine:migrations:migrate
```

when finished, start scraper by running

```bash
vendor/bin/behat --config config/behat.yaml features/angellist.feature
```






