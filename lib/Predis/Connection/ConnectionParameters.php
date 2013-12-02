<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use InvalidArgumentException;

/**
 * Handles parsing and validation of connection parameters.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionParameters implements ConnectionParametersInterface
{
    private $parameters;

    private static $defaults = array(
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 5.0,
    );

    private static $casters = array(
        'port' => 'self::castInteger',
        'async_connect' => 'self::castBoolean',
        'persistent' => 'self::castBoolean',
        'timeout' => 'self::castFloat',
        'read_write_timeout' => 'self::castFloat',
    );

    /**
     * @param string|array Connection parameters in the form of an URI string or a named array.
     */
    public function __construct($parameters = null)
    {
        if (is_string($parameters)) {
            $parameters = self::parse($parameters);
        }

        $this->parameters = $this->filter($parameters ?: array()) + $this->getDefaults();
    }

    /**
     * Returns some default parameters with their values.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return self::$defaults;
    }

    /**
     * Returns cast functions for user-supplied parameter values.
     *
     * @return array
     */
    protected function getValueCasters()
    {
        return self::$casters;
    }

    /**
     * Parses an URI string returning an array of connection parameters.
     *
     * @param string $uri URI string.
     * @return array
     */
    public static function parse($uri)
    {
        if (stripos($uri, 'unix') === 0) {
            // Hack to support URIs for UNIX sockets with minimal effort.
            $uri = str_ireplace('unix:///', 'unix://localhost/', $uri);
        }

        if (!($parsed = parse_url($uri)) || !isset($parsed['host'])) {
            throw new InvalidArgumentException("Invalid parameters URI: $uri");
        }

        if (isset($parsed['query'])) {
            foreach (explode('&', $parsed['query']) as $kv) {
                $kv = explode('=', $kv, 2);
                if (isset($kv[0], $kv[1])) {
                    $parsed[$kv[0]] = $kv[1];
                }
            }

            unset($parsed['query']);
        }

        return $parsed;
    }

    /**
     * Validates and converts each value of the connection parameters array.
     *
     * @param array $parameters Connection parameters.
     * @return array
     */
    private function filter(array $parameters)
    {
        if ($parameters) {
            $casters = array_intersect_key($this->getValueCasters(), $parameters);

            foreach ($casters as $parameter => $caster) {
                $parameters[$parameter] = call_user_func($caster, $parameters[$parameter]);
            }
        }

        return $parameters;
    }

    /**
     * Validates value as boolean.
     *
     * @param mixed $value Input value.
     * @return boolean
     */
    private static function castBoolean($value)
    {
        return (bool) $value;
    }

    /**
     * Validates value as float.
     *
     * @param mixed $value Input value.
     * @return float
     */
    private static function castFloat($value)
    {
        return (float) $value;
    }

    /**
     * Validates value as integer.
     *
     * @param mixed $value Input value.
     * @return int
     */
    private static function castInteger($value)
    {
        return (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($parameter)
    {
        if (isset($this->parameters[$parameter])) {
            return $this->parameters[$parameter];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($parameter)
    {
        return isset($this->parameters[$parameter]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array('parameters');
    }
}
