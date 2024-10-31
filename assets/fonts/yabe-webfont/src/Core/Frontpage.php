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
namespace Yabe\Webfont\Core;

use Yabe\Webfont\Utils\Config;
use Yabe\Webfont\Utils\Debug;
use _YabeWebfont\YABE_WEBFONT;
/**
 * Serve the font on the frontpage.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
final class Frontpage
{
    public function __construct()
    {
        /**
         * @see wp-includes\default-filters.php for the priority.
         */
        \add_action('wp_head', fn() => $this->append_header(), 4);
        \add_action('wp_enqueue_scripts', fn() => $this->disable_user_google_fonts(), 1000001);
    }
    public static function enqueue_css_cache()
    {
        if (\defined('_YabeWebfont\\YABE_WEBFONT_CSS_CACHE_WAS_LOADED')) {
            return;
        }
        Debug::stopwatch()->start(\sprintf('%s::%s', self::class, __FUNCTION__));
        if (\file_exists(\Yabe\Webfont\Core\Cache::get_cache_path(\Yabe\Webfont\Core\Cache::CSS_CACHE_FILE))) {
            $handle = YABE_WEBFONT::WP_OPTION . '-cache';
            $version = (string) \filemtime(\Yabe\Webfont\Core\Cache::get_cache_path(\Yabe\Webfont\Core\Cache::CSS_CACHE_FILE));
            if (Config::get('cache.inline_print', \false)) {
                $css = \file_get_contents(\Yabe\Webfont\Core\Cache::get_cache_path(\Yabe\Webfont\Core\Cache::CSS_CACHE_FILE));
                if ($css !== \false) {
                    echo \sprintf("<style id=\"%s-css\">\n%s\n</style>", $handle, $css);
                }
            } else {
                \wp_register_style($handle, \Yabe\Webfont\Core\Cache::get_cache_url(\Yabe\Webfont\Core\Cache::CSS_CACHE_FILE), [], $version);
                \do_action('a!yabe/webfont/core/frontpage:before_print_style');
                \wp_print_styles($handle);
            }
        }
        \define('_YabeWebfont\\YABE_WEBFONT_CSS_CACHE_WAS_LOADED', \true);
        Debug::stopwatch()->stop(\sprintf('%s::%s', self::class, __FUNCTION__));
    }
    /**
     * Append the header to the frontpage.
     */
    private function append_header()
    {
        $this->preload();
        self::enqueue_css_cache();
    }
    /**
     * Preload the fonts file on the frontpage.
     */
    private function preload()
    {
        if (\defined('_YabeWebfont\\YABE_WEBFONT_PRELOAD_HTML_WAS_LOADED')) {
            return;
        }
        Debug::stopwatch()->start(\sprintf('%s::%s', self::class, __FUNCTION__));
        if (\file_exists(\Yabe\Webfont\Core\Cache::get_cache_path(\Yabe\Webfont\Core\Cache::PRELOAD_HTML_FILE))) {
            $preload_html = \file_get_contents(\Yabe\Webfont\Core\Cache::get_cache_path(\Yabe\Webfont\Core\Cache::PRELOAD_HTML_FILE));
            if ($preload_html !== \false) {
                echo $preload_html;
            }
        }
        \define('_YabeWebfont\\YABE_WEBFONT_PRELOAD_HTML_WAS_LOADED', \true);
        Debug::stopwatch()->stop(\sprintf('%s::%s', self::class, __FUNCTION__));
    }
    /**
     * Scan and disable Google Fonts API that loaded manually by the theme or plugin through `wp_enqueue_style` function.
     */
    private function disable_user_google_fonts()
    {
        $is_disable = Config::get('misc.disable_user_google_fonts', \false);
        if (!$is_disable) {
            return;
        }
        global $wp_styles;
        foreach ($wp_styles->queue as $q) {
            if ($wp_styles->registered[$q]->src && \strpos($wp_styles->registered[$q]->src, 'fonts.googleapis.com') !== \false) {
                \wp_dequeue_style($q);
                \wp_deregister_style($q);
            }
        }
    }
}
