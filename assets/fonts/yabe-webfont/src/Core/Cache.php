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

use Yabe\Webfont\Utils\Common;
use Yabe\Webfont\Utils\Config;
use Yabe\Webfont\Utils\Font;
use Yabe\Webfont\Utils\Notice;
use Yabe\Webfont\Utils\Upload;
use _YabeWebfont\YABE_WEBFONT;
/**
 * Manage the cache of fonts for the frontpage.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Cache
{
    /**
     * @var string
     */
    public const CSS_CACHE_FILE = 'fonts.css';
    /**
     * @var string
     */
    public const PRELOAD_HTML_FILE = 'preload.html';
    /**
     * @var string
     */
    public const CACHE_DIR = '/yabe-webfont/cache/';
    /**
     * @var string
     */
    public static $typekit_embed = 'css';
    public function __construct()
    {
        \add_filter('cron_schedules', fn($schedules) => $this->filter_cron_schedules($schedules));
        \add_action('a!yabe/webfont/core/cache:build_cache', fn() => $this->build_cache());
        // listen to fonts event for cache build (async/scheduled)
        \add_action('a!yabe/webfont/api/font:fonts_event_async', fn() => $this->schedule_cache(), 10, 1);
        // listen to fonts event for cache build (sync)
        \add_action('a!yabe/webfont/api/font:fonts_event', fn() => $this->build_cache(), 10, 1);
        // listen to theme switch for cache build (async/scheduled)
        \add_action('switch_theme', fn() => $this->schedule_cache(), 1000001);
        // listen to Config change for cache build (async/scheduled)
        \add_action('f!yabe/webfont/api/setting/option:after_store', fn() => $this->schedule_cache(), 10, 1);
        // listen to plugin upgrade for cache build (async/scheduled)
        \add_action('a!yabe/webfont/plugins:upgrade_plugin_end', fn() => $this->schedule_cache(), 10, 1);
    }
    public function filter_cron_schedules($schedules)
    {
        if (!\array_key_exists('minutely', $schedules)) {
            $schedules['minutely'] = ['interval' => \MINUTE_IN_SECONDS, 'display' => \__('Once Minutely', 'yabe-webfont')];
        }
        return $schedules;
    }
    public function schedule_cache()
    {
        if (!\wp_next_scheduled('a!yabe/webfont/core/cache:build_cache')) {
            \wp_schedule_single_event(\time() + 10, 'a!yabe/webfont/core/cache:build_cache');
        }
    }
    public static function get_cache_path(string $file_path = '') : string
    {
        return \wp_upload_dir()['basedir'] . self::CACHE_DIR . $file_path;
    }
    public static function get_cache_url(string $file_path = '') : string
    {
        return \wp_upload_dir()['baseurl'] . self::CACHE_DIR . $file_path;
    }
    public function build_cache()
    {
        $css = self::build_css();
        $payload = \sprintf("/*\n! %s v%s | %s\n*/\n\n%s", Common::plugin_data('Name'), YABE_WEBFONT::VERSION, \date('Y-m-d H:i:s', \time()), $css);
        try {
            Common::save_file($payload, self::get_cache_path(self::CSS_CACHE_FILE));
        } catch (\Throwable $throwable) {
            Notice::error(\sprintf('Failed to build Fonts CSS cache: %s', $throwable->getMessage()));
        }
        $preload_html = self::build_preload();
        try {
            Common::save_file($preload_html, self::get_cache_path(self::PRELOAD_HTML_FILE));
        } catch (\Throwable $throwable) {
            Notice::error(\sprintf('Failed to build Fonts Preload HTML cache: %s', $throwable->getMessage()));
        }
        $this->purge_cache_plugin();
    }
    public static function build_css() : string
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        $css = '';
        $sql = "\n            SELECT * FROM {$wpdb->prefix}yabe_webfont_fonts \n            WHERE status = 1\n                AND deleted_at IS NULL\n        ";
        $result = $wpdb->get_results($sql);
        if (empty($result)) {
            return $css;
        }
        $format_precedence = ['woff2' => 1, 'woff' => 2, 'ttf' => 3, 'otf' => 4, 'eot' => 5];
        // Adobe Fonts
        $project_id = Config::get('adobe_fonts.project_id', null);
        if ($project_id !== null) {
            // check if the $result array contain item.type = 'adobe-fonts'
            $any_adobe_fonts = \array_search('adobe-fonts', \array_column($result, 'type'), \true);
            if ($any_adobe_fonts !== \false) {
                $css .= self::get_kit_css($project_id);
            }
        }
        foreach ($result as $row) {
            try {
                $metadata = \json_decode($row->metadata, null, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $metadata = \json_decode(\gzuncompress(\base64_decode($row->metadata)), null, 512, \JSON_THROW_ON_ERROR);
            }
            try {
                $font_faces = \json_decode($row->font_faces, null, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $font_faces = \json_decode(\gzuncompress(\base64_decode($row->font_faces)), null, 512, \JSON_THROW_ON_ERROR);
            }
            $font_faces = Upload::refresh_font_faces_attachment_url($font_faces);
            foreach ($font_faces as $font_face) {
                if ($font_face->comment) {
                    $css .= "/* {$font_face->comment} */\n";
                }
                $css .= "@font-face {\n";
                $css .= "\tfont-family: '{$row->family}';\n";
                $css .= "\tfont-style: {$font_face->style};\n";
                $wght = $font_face->weight ?: '400';
                $wdth = $font_face->width ?: '100%';
                $css .= "\tfont-weight: {$wght};\n";
                $css .= "\tfont-stretch: {$wdth};\n";
                $display = $font_face->display ?: $metadata->display;
                $css .= "\tfont-display: {$display};\n";
                if ($font_face->files !== []) {
                    \usort($font_face->files, static fn($a, $b) => $format_precedence[$a->extension] <=> $format_precedence[$b->extension]);
                    $css .= "\tsrc: ";
                    $files = \array_map(static fn($f) => \sprintf("url('%s') format(\"%s\")", $f->attachment_url, Upload::mime_keyword($f->extension)), $font_face->files);
                    $css .= \implode(",\n\t\t", $files);
                    $css .= ";\n";
                }
                if ($font_face->unicodeRange) {
                    $css .= "\tunicode-range: {$font_face->unicodeRange};\n";
                }
                $css .= "}\n\n";
            }
        }
        // CSS custom properties (variables)
        $css .= ":root {\n";
        foreach ($result as $row) {
            try {
                $metadata = \json_decode($row->metadata, null, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $metadata = \json_decode(\gzuncompress(\base64_decode($row->metadata)), null, 512, \JSON_THROW_ON_ERROR);
            }
            $selectorParts = [];
            $fallbackFamily = '';
            // if property selector is exists
            if (\property_exists($metadata, 'selector') && $metadata->selector) {
                $selectorParts = \explode('|', $metadata->selector);
                $selectorParts = \array_map('trim', $selectorParts);
                $selectorParts = \array_filter($selectorParts);
                $fallbackFamily = isset($selectorParts[1]) ? ', ' . $selectorParts[1] : '';
            }
            $value = \sprintf("'%s'%s", $row->family, $fallbackFamily);
            $name = Font::css_custom_property($row->family);
            $css .= "\t{$name}: {$value};\n";
        }
        $css .= "}\n\n";
        foreach ($result as $row) {
            try {
                $metadata = \json_decode($row->metadata, null, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $metadata = \json_decode(\gzuncompress(\base64_decode($row->metadata)), null, 512, \JSON_THROW_ON_ERROR);
            }
            try {
                $font_faces = \json_decode($row->font_faces, null, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $font_faces = \json_decode(\gzuncompress(\base64_decode($row->font_faces)), null, 512, \JSON_THROW_ON_ERROR);
            }
            $font_faces = Upload::refresh_font_faces_attachment_url($font_faces);
            $selectorParts = [];
            if (\property_exists($metadata, 'selector') && $metadata->selector) {
                $selectorParts = \explode('|', $metadata->selector);
                $selectorParts = \array_map('trim', $selectorParts);
                $selectorParts = \array_filter($selectorParts);
                if (isset($selectorParts[0]) && $selectorParts[0]) {
                    $css .= \sprintf("%s {\n\tfont-family: %s;\n}\n\n", $selectorParts[0], Font::css_variable($row->family));
                }
            }
            foreach ($font_faces as $font_face) {
                if ($font_face->selector) {
                    $css .= "{$font_face->selector} {\n";
                    $css .= \sprintf("\tfont-family: %s;\n", Font::css_variable($row->family));
                    $css .= "\tfont-style: {$font_face->style};\n";
                    $css .= "\tfont-weight: {$font_face->weight};\n";
                    $css .= "}\n\n";
                }
            }
        }
        /**
         * @param string $css The CSS content
         * @param array $result The result of the SQL query
         * @return string The CSS content
         */
        $css = \apply_filters('f!yabe/webfont/core/cache:build_css.append_content', $css, $result);
        if (\defined('WP_DEBUG') && \WP_DEBUG) {
            // replace tabs with 2 spaces
            $css = \preg_replace('#\\t#', '  ', $css);
        } else {
            // remove new lines and tabs
            $css = \preg_replace('#\\n#', '', $css);
            $css = \preg_replace('#\\t#', '', $css);
        }
        return $css;
    }
    public static function build_preload() : string
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        $html = '';
        $sql = "\n            SELECT metadata, font_faces, type FROM {$wpdb->prefix}yabe_webfont_fonts\n            WHERE status = 1\n                AND deleted_at IS NULL\n        ";
        $result = $wpdb->get_results($sql);
        if (empty($result)) {
            return $html;
        }
        $preload_files = [];
        foreach ($result as $row) {
            try {
                $metadata = \json_decode($row->metadata, null, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $metadata = \json_decode(\gzuncompress(\base64_decode($row->metadata)), null, 512, \JSON_THROW_ON_ERROR);
            }
            try {
                $font_faces = \json_decode($row->font_faces, null, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $font_faces = \json_decode(\gzuncompress(\base64_decode($row->font_faces)), null, 512, \JSON_THROW_ON_ERROR);
            }
            $font_faces = Upload::refresh_font_faces_attachment_url($font_faces);
            foreach ($font_faces as $font_face) {
                if ($metadata->preload || \property_exists($font_face, 'preload') && $font_face->preload) {
                    foreach ($font_face->files as $file) {
                        if ($file->mime !== 'font/woff2') {
                            continue;
                        }
                        $preload_files[] = ['href' => $file->attachment_url, 'type' => $file->mime];
                    }
                }
            }
        }
        $preload_files = \array_unique($preload_files, \SORT_REGULAR);
        foreach ($preload_files as $preload_file) {
            $html .= \sprintf('<link rel="preload" href="%s" as="font" type="%s" crossorigin>' . \PHP_EOL, $preload_file['href'], $preload_file['type']);
        }
        // Adobe Fonts
        if (self::$typekit_embed === 'js') {
            $project_id = Config::get('adobe_fonts.project_id', null);
            if ($project_id !== null) {
                // check if the $result array contain item.type = 'adobe-fonts'
                $any_adobe_fonts = \array_search('adobe-fonts', \array_column($result, 'type'), \true);
                if ($any_adobe_fonts !== \false) {
                    $html .= self::get_kit_js($project_id);
                }
            }
        }
        return $html;
    }
    public static function get_kit_css($kit_id) : string
    {
        $css = '';
        $response = \wp_remote_get(\sprintf('https://use.typekit.net/%s.css', $kit_id));
        if (\is_wp_error($response) || \wp_remote_retrieve_response_code($response) !== 200) {
            // The kit is only available in JS
            if (\wp_remote_retrieve_response_code($response) === 412) {
                self::$typekit_embed = 'js';
            }
            return $css;
        }
        $body = \wp_remote_retrieve_body($response);
        if (\is_wp_error($body)) {
            return $css;
        }
        return $css . ($body . "\n\n");
    }
    public static function get_kit_js($kit_id) : string
    {
        $js = '';
        $response = \wp_remote_get(\sprintf('https://use.typekit.net/%s.js', $kit_id));
        if (\is_wp_error($response) || \wp_remote_retrieve_response_code($response) !== 200) {
            return $js;
        }
        $body = \wp_remote_retrieve_body($response);
        if (\is_wp_error($body)) {
            return $js;
        }
        $js .= '<script type="text/javascript">';
        $js .= $body;
        $js .= "\n\n";
        $js .= \sprintf('try{Typekit.load({kitId:"%s",scriptTimeout:3E3,async:true});}catch(e){}', $kit_id);
        $js .= '</script>';
        return "\n\n" . $js . "\n\n";
    }
    /**
     * Clear the cache from various cache plugins.
     */
    private function purge_cache_plugin()
    {
        /**
         * WordPress Object Cache
         * @see https://developer.wordpress.org/reference/classes/wp_object_cache/
         */
        \wp_cache_flush();
        /**
         * WP Rocket
         * @see https://docs.wp-rocket.me/article/92-rocketcleandomain
         */
        if (\function_exists('rocket_clean_domain')) {
            \rocket_clean_domain();
        }
        /**
         * WP Super Cache
         * @see https://github.com/Automattic/wp-super-cache/blob/a0872032b1b3fc6847f490eadfabf74c12ad0135/wp-cache-phase2.php#L3013
         */
        if (\function_exists('wp_cache_clear_cache')) {
            \wp_cache_clear_cache();
        }
        /**
         * W3 Total Cache
         * @see https://github.com/BoldGrid/w3-total-cache/blob/3a094493064ea60d727b3389dee813639860ef49/w3-total-cache-api.php#L259
         */
        if (\function_exists('w3tc_flush_all')) {
            \w3tc_flush_all();
        }
        /**
         * WP Fastest Cache
         * @see https://www.wpfastestcache.com/tutorial/delete-the-cache-by-calling-the-function/
         */
        if (\function_exists('wpfc_clear_all_cache')) {
            \wpfc_clear_all_cache(\true);
        }
        /**
         * LiteSpeed Cache
         * @see https://docs.litespeedtech.com/lscache/lscwp/api/#purge-all-existing-caches
         */
        \do_action('litespeed_purge_all');
    }
}
