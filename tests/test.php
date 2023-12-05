<?php
include_once __DIR__.'/../vendor/autoload.php';

/**
 * Class Redis
 *
 * @property string $host
 * @property string $password
 * @property string $port
 */
class Redis1 extends \Npc\Entity\Base
{

}

/**
 * Class Config
 * @property string $slave_id
 * @property Redis1 $redis
 */
class Config extends \Npc\Entity\Base
{

}

$conf1 = new Config([
    'slave_id' => 111,
    'databasesOnly' => [''],
    'redis' => [
        'host' => '123',
        'password' => '',
        'port' => 6379,
    ],
]);
var_dump($conf1->redis->host);