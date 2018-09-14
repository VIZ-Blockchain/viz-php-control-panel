[![VIZ](logo-square-160.png)](http://viz.world/)
# VIZ.World Control Panel
## Features
* Light VIZ PHP blockchain client
* Cache-layer in redis for prevent request DDOS
* JS client

## Installation
```
git clone https://github.com/VIZ-World/viz-world-control-panel.git
cd viz-world-control-panel
cp config-example.php config.php
nano config.php
```

## Dependencies
- [PHP 7](https://thishosting.rocks/install-php-on-ubuntu/)
- [MongoDB](https://docs.mongodb.com/manual/tutorial/install-mongodb-on-ubuntu/) ([how to create user](https://docs.mongodb.com/manual/reference/method/db.createUser/))
- [Redis](https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-redis-on-ubuntu-16-04)
- Nginx (look [nginx-example.conf](nginx-example.conf))
- [Parsedown](https://github.com/erusev/parsedown) (+[extra](https://github.com/erusev/parsedown-extra))
- [PHPRedis](https://github.com/phpredis/phpredis)
- JQuery
- [viz-world-js](https://github.com/VIZ-World/viz-world-js)