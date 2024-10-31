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
namespace Yabe\Webfont\Builder\Gutenberg;

use WP_Theme_JSON_Data;
use Yabe\Webfont\Builder\BuilderInterface;
use Yabe\Webfont\Core\Cache;
use Yabe\Webfont\Core\Frontpage;
use Yabe\Webfont\Utils\Config;
use Yabe\Webfont\Utils\Font;
use _YabeWebfont\YABE_WEBFONT;
/**
 * Gutenberg integration.
 *
 * @see https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-json/
 * @see https://developer.wordpress.org/block-editor/reference-guides/theme-json-reference/theme-json-living/
 * @see https://developer.wordpress.org/themes/advanced-topics/theme-json/
 * @see https://make.wordpress.org/core/2022/10/10/filters-for-theme-json-data/
 * @see https://make.wordpress.org/core/2021/09/28/implementing-a-webfonts-api-in-wordpress-core/
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Main implements BuilderInterface
{
    public function __construct()
    {
        \add_filter('wp_theme_json_data_theme', fn($theme_json) => $this->filter_theme_json_data_theme($theme_json), 1000001);
        \add_filter('wp_theme_json_data_user', fn($theme_json) => $this->filter_theme_json_data_user($theme_json), 1000001);
        \add_filter('f!yabe/webfont/core/cache:build_css.append_content', fn($css, $rows) => $this->filter_append_build_css_content($css, $rows), 1000001, 2);
        \add_filter('f!yabe/webfont/core/cache:build_css.append_content', fn($css, $rows) => $this->filter_ensure_block_editor($css, $rows), 1000001, 2);
        // add_action('enqueue_block_editor_assets', fn () => $this->enqueue_block_editor_assets(), 1_000_001);
        // add_action('after_setup_theme', fn () => $this->after_setup_theme(), 1_000_001);
        \add_action('enqueue_block_assets', fn() => $this->enqueue_block_assets(), 1000001);
    }
    public function get_name() : string
    {
        return 'gutenberg';
    }
    /**
     * @see https://make.wordpress.org/core/2022/10/10/filters-for-theme-json-data/
     * @param WP_Theme_JSON_Data $theme_json
     * @return WP_Theme_JSON_Data
     */
    public function filter_theme_json_data_user($theme_json)
    {
        $theme_json_data = $theme_json->get_data();
        if (!isset($theme_json_data['settings']['typography'])) {
            return $theme_json;
        }
        $theme_json_font_families = $theme_json_data['settings']['typography']['fontFamilies'] ?? [];
        $fonts = Font::get_fonts();
        foreach ($fonts as $font) {
            /**
             * @see https://www.w3.org/TR/CSS22/syndata.html#value-def-identifier
             */
            $theme_json_font_families[] = ['name' => \sprintf('[Yabe] %s', $font['title']), 'slug' => Font::slugify($font['family']), 'fontFamily' => Font::css_variable($font['family'])];
        }
        $new_data = ['version' => 2, 'settings' => ['typography' => ['fontFamilies' => $theme_json_font_families]]];
        return $theme_json->update_with($new_data);
    }
    /**
     * @see https://make.wordpress.org/core/2022/10/10/filters-for-theme-json-data/
     * @param WP_Theme_JSON_Data $theme_json
     * @return WP_Theme_JSON_Data
     */
    public function filter_theme_json_data_theme($theme_json)
    {
        $theme_json_data = $theme_json->get_data();
        $theme_json_font_families = $theme_json_data['settings']['typography']['fontFamilies']['theme'] ?? [];
        $fonts = Font::get_fonts();
        foreach ($fonts as $font) {
            /**
             * @see https://www.w3.org/TR/CSS22/syndata.html#value-def-identifier
             */
            $theme_json_font_families[] = ['name' => \sprintf('[Yabe] %s', $font['title']), 'slug' => Font::slugify($font['family']), 'fontFamily' => Font::css_variable($font['family'])];
        }
        $new_data = ['version' => 2, 'settings' => ['typography' => ['fontFamilies' => ['theme' => $theme_json_font_families]]]];
        return $theme_json->update_with($new_data);
    }
    public function enqueue_block_editor_assets()
    {
        $screen = \get_current_screen();
        if (\is_admin() && $screen->is_block_editor()) {
            \add_action('admin_head', static function () {
                \add_filter('f!yabe/webfont/api/setting/option:index_options', static function ($options) {
                    Config::propertyAccessor()->setValue($options, 'cache.inline_print', \false);
                    return $options;
                }, 1000001);
                Frontpage::enqueue_css_cache();
            }, 1000001);
        }
    }
    public function after_setup_theme()
    {
        // Add support for editor styles.
        \add_theme_support('editor-styles');
        \add_editor_style(Cache::get_cache_url(Cache::CSS_CACHE_FILE));
    }
    public function enqueue_block_assets()
    {
        if (\is_admin() && \file_exists(Cache::get_cache_path(Cache::CSS_CACHE_FILE))) {
            $handle = YABE_WEBFONT::WP_OPTION . '-cache';
            $version = (string) \filemtime(Cache::get_cache_path(Cache::CSS_CACHE_FILE));
            \wp_enqueue_style($handle, Cache::get_cache_url(Cache::CSS_CACHE_FILE), [], $version);
        }
    }
    /**
     * ensure the css file is loaded to the block editor, template editor, or site editor
     */
    public function filter_ensure_block_editor($css, $rows) : string
    {
        return $css . ".wp-block, .editor-styles-wrapper { } \n\n";
    }
    /**
     * Support for a non block-based theme.
     */
    public function filter_append_build_css_content($css, $rows)
    {
        if (\function_exists('wp_is_block_theme') && \wp_is_block_theme()) {
            return $css;
        }
        return $css . self::non_block_based_theme_support_classes();
    }
    public static function non_block_based_theme_support_classes() : string
    {
        $inline_css = '';
        $fonts = Font::get_fonts();
        foreach ($fonts as $font) {
            $inline_css .= \sprintf(".has-%s-font-family {\n", Font::slugify($font['family']));
            $inline_css .= \sprintf("\tfont-family: %s !important;\n", Font::css_variable($font['family']));
            $inline_css .= "}\n\n";
        }
        return $inline_css;
    }
}
