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

use _YabeWebfont\Symfony\Component\Stopwatch\Stopwatch;
/**
 * Debug tools for the plugin.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Debug
{
    /**
     * The stopwatch instance.
     */
    private static Stopwatch $stopwatch_instance;
    /**
     * Get the stopwatch instance.
     */
    public static function stopwatch() : Stopwatch
    {
        if (!isset(self::$stopwatch_instance)) {
            self::$stopwatch_instance = new Stopwatch(\true);
        }
        return self::$stopwatch_instance;
    }
    public static function shutdown()
    {
        if (isset(self::$stopwatch_instance)) {
            self::shutdown_stopwatch();
        }
    }
    public static function shutdown_stopwatch()
    {
        $log = '=== ' . \date('Y-m-d H:i:s', \time()) . ' ===' . \PHP_EOL;
        foreach (self::stopwatch()->getSectionEvents('__root__') as $stopwatchEvent) {
            $log .= (string) $stopwatchEvent . \PHP_EOL;
        }
        $log .= \PHP_EOL;
        $path = \wp_upload_dir()['basedir'] . '/yabe-webfont/debug/stopwatch.log';
        try {
            \Yabe\Webfont\Utils\Common::save_file($log, $path, \FILE_APPEND);
        } catch (\Throwable $throwable) {
        }
    }
}
