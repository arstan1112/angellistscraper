## Requirement 

Docker, chromium-browser


## Installation and start docker

In docker directory run

**docker-compose build**

to build images

then run

**docker-compose up**

to start docker containers


## Chromium container

Run

**docker exec -it docker_chromium_1 bash**

to start terminal inside Chromium container

Then run 

**/usr/bin/chromium-browser --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222** 

to start Chromium browser

Then run 

**exit**


## PHP container and start scraper

Run

**docker exec -it docker_php_1 bash**

to start terminal inside php container

Then in /var/www/angel directory run

**composer install**

when finished, start scraper by running

**vendor/bin/behat --config config/behat.yaml features/angellist.feature**







