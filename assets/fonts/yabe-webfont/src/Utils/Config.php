<?php

/*
 * This file is part of the Yabe package.
 *
 * (c) Joshua Gugun Siagian <suabahasa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare (strict_types=1);
namespace Yabe\Webfont\Utils;

use Exception;
use _YabeWebfont\Symfony\Component\PropertyAccess\Exception\AccessException;
use _YabeWebfont\Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use _YabeWebfont\Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use _YabeWebfont\Symfony\Component\PropertyAccess\PropertyAccess;
use _YabeWebfont\YABE_WEBFONT;
/**
 * Accessor for the plugin config.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Config
{
    /**
     * Stores the instance of PropertyAccessor, implementing a Singleton pattern.
     */
    private static ?\_YabeWebfont\Symfony\Component\PropertyAccess\PropertyAccessorInterface $propertyAccessor = null;
    public static function propertyAccessor()
    {
        if (!isset(self::$propertyAccessor)) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        }
        return self::$propertyAccessor;
    }
    /**
     * Gets a value at the end of the property path of the config.
     *
     * @param string $path The property path to read
     * @param mixed $defaultValue The value to return if the property path does not exist
     *
     * @return mixed The value at the end of the property path
     */
    public static function get($path, $defaultValue = null)
    {
        $options = \json_decode(\get_option(YABE_WEBFONT::WP_OPTION . '_options', '{}'), null, 512, \JSON_THROW_ON_ERROR);
        $options = \apply_filters('f!yabe/webfont/api/setting/option:index_options', $options);
        try {
            return self::propertyAccessor()->getValue($options, $path);
        } catch (Exception $exception) {
            return $defaultValue;
        }
    }
    /**
     * Sets a value at the end of the property path of the config.
     *
     * @param string $path The property path to modify
     * @param mixed $value The value to set at the end of the property path
     *
     * @throws InvalidArgumentException If the property path is invalid
     * @throws AccessException          If a property/index does not exist or is not public
     * @throws UnexpectedTypeException  If a value within the path is neither object nor array
     */
    public static function set($path, $value)
    {
        $options = \json_decode(\get_option(YABE_WEBFONT::WP_OPTION . '_options', '{}'), null, 512, \JSON_THROW_ON_ERROR);
        $options = \apply_filters('f!yabe/webfont/api/setting/option:index_options', $options);
        self::propertyAccessor()->setValue($options, $path, $value);
        \update_option(YABE_WEBFONT::WP_OPTION . '_options', \json_encode($options, \JSON_THROW_ON_ERROR));
    }
}
