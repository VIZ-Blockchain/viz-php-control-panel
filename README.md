# VIZ.World ![](logo-symbol-anim.svg) Control Panel
## Features
* VIZ PHP blockchain client
* Waterfall for block analysys
* Plugins system for waterfall
* JS actions for most VIZ features
* Cache-layer in redis

## Installation
```
git clone https://github.com/VIZ-World/viz-world-control-panel.git
cd viz-world-control-panel
git config core.fileMode false
cp config-example.php config.php
nano config.php
```

## Dependencies
- [PHP 7](https://thishosting.rocks/install-php-on-ubuntu/)
- [PHP EXT secp256k1](https://github.com/On1x/secp256k1-php) need to check signatures
- [PHP EXT gpm](http://php.net/manual/en/book.gmp.php) for viz-keys class
- [MongoDB](https://docs.mongodb.com/manual/tutorial/install-mongodb-on-ubuntu/) ([how to create user](https://docs.mongodb.com/manual/reference/method/db.createUser/))
- [Redis](https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-redis-on-ubuntu-16-04)
- Nginx (look [nginx-example.conf](nginx-example.conf))
- [Parsedown](https://github.com/erusev/parsedown) (+[extra](https://github.com/erusev/parsedown-extra))
- [PHPRedis](https://github.com/phpredis/phpredis)
- JQuery
- [viz-world-js](https://github.com/VIZ-World/viz-world-js)