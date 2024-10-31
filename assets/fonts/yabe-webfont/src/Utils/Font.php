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

use _YabeWebfont\YABE_WEBFONT;
/**
 * Font utility functions for the plugin.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Font
{
    public static function get_fonts() : array
    {
        $fonts = \wp_cache_get('get_fonts', YABE_WEBFONT::WP_OPTION);
        if ($fonts === \false) {
            /** @var wpdb $wpdb */
            global $wpdb;
            $fonts = [];
            $sql = "\n                SELECT * FROM {$wpdb->prefix}yabe_webfont_fonts \n                WHERE status = 1\n                    AND deleted_at IS NULL\n                ORDER BY title ASC\n            ";
            $result = $wpdb->get_results($sql);
            foreach ($result as $row) {
                $f = ['title' => $row->title, 'family' => $row->family, 'type' => $row->type, 'slug' => $row->slug, 'css' => ['slug' => self::slugify($row->family), 'custom_property' => self::css_custom_property($row->family), 'variable' => self::css_variable($row->family)], 'variants' => [], 'fallback_family' => null];
                try {
                    $font_faces = \json_decode($row->font_faces, null, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    $font_faces = \json_decode(\gzuncompress(\base64_decode($row->font_faces)), null, 512, \JSON_THROW_ON_ERROR);
                }
                foreach ($font_faces as $font_face) {
                    $f['variants'][] = ['weight' => $font_face->weight, 'style' => $font_face->style];
                }
                $selectorParts = [];
                try {
                    $metadata = \json_decode($row->metadata, null, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    $metadata = \json_decode(\gzuncompress(\base64_decode($row->metadata)), null, 512, \JSON_THROW_ON_ERROR);
                }
                // if property selector is exists
                if (\property_exists($metadata, 'selector') && $metadata->selector) {
                    $selectorParts = \explode('|', $metadata->selector);
                    $selectorParts = \array_map('trim', $selectorParts);
                    $selectorParts = \array_filter($selectorParts);
                    $f['fallback_family'] = $selectorParts[1] ?? null;
                }
                $fonts[] = $f;
            }
            \wp_cache_set('get_fonts', $fonts, YABE_WEBFONT::WP_OPTION);
        }
        return $fonts;
    }
    /**
     * @param string $value font family name
     * @return string css custom property wrapped with variable function. e.g. `var(--ywf--family-open-sans)` for `Open Sans`
     */
    public static function css_variable(string $value) : string
    {
        return \sprintf('var(%s)', self::css_custom_property($value));
    }
    /**
     * @param string $value font family name
     * @return string css custom property. e.g. `--ywf--family-open-sans` for `Open Sans`
     */
    public static function css_custom_property(string $value) : string
    {
        return \sprintf('--ywf--family-%s', self::slugify($value));
    }
    /**
     * @param string $value font family name
     * @return string slugified string. e.g. `open-sans` for `Open Sans`
     */
    public static function slugify(string $value) : string
    {
        return \preg_replace('#[^a-zA-Z0-9\\-_]+#', '-', \strtolower($value));
    }
}
