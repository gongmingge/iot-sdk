<?php

namespace IotSpace;

use IotSpace\Exception\IotException;
use IotSpace\Ys\BaseClient;
use IotSpace\Ys\DeviceClient;
use IotSpace\Ys\DoorClient;
use IotSpace\Ys\PersonClient;
use IotSpace\Ys\SaasClient;
use IotSpace\Ys\TokenClient;


/**
 * 萤石云SDK
 *
 * @method static TokenClient TokenClient()
 * @method static PersonClient PersonClient()
 * @method static DoorClient DoorClient()
 * @method static DeviceClient DeviceClient()
 * @method static SaasClient SaasClient()
 *
 * @package IotSpace
 */
class YsCloud
{
    private function __construct()
    {

    }

    /**
     * Dynamically pass methods to the application.
     *
     * @param string $name
     * @param array $config
     *
     * @return mixed
     */
    public static function __callStatic($name, array $config)
    {
        /**
         * @var BaseClient
         */
        $class = "IotSpace\\Ys\\{$name}";

        if (class_exists($class)) {
            if (empty($config[0])) {
                throw new IotException("config missed");
            }
            return new $class($config[0]);
        }

        throw new IotException("{$name} Not Found.");
    }
}
