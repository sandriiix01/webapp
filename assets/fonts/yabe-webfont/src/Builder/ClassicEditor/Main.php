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
namespace Yabe\Webfont\Builder\ClassicEditor;

use Yabe\Webfont\Builder\BuilderInterface;
use Yabe\Webfont\Core\Cache;
use Yabe\Webfont\Utils\Font;
/**
 * Classic Editor integration.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Main implements BuilderInterface
{
    public function __construct()
    {
        \add_filter('mce_buttons_2', fn($buttons) => $this->filter_mce_buttons($buttons), 1000001);
        \add_filter('tiny_mce_before_init', fn($mceInit, $editor_id) => $this->filter_tiny_mce($mceInit, $editor_id), 1000001, 2);
        \add_filter('mce_css', fn($stylesheets) => $this->filter_mce_css($stylesheets), 1000001);
    }
    public function get_name() : string
    {
        return 'classic-editor';
    }
    public function filter_tiny_mce($mceInit, $editor_id)
    {
        $theme_advanced_fonts = '';
        $fonts = Font::get_fonts();
        foreach ($fonts as $font) {
            $theme_advanced_fonts .= \sprintf('[Yabe] %s=%s;', $font['title'], $font['family']);
        }
        $mceInit['font_formats'] = $theme_advanced_fonts . 'Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats';
        return $mceInit;
    }
    public function filter_mce_buttons($buttons)
    {
        return \array_merge($buttons, ['fontsizeselect', 'fontselect']);
    }
    public function filter_mce_css($stylesheets)
    {
        $css_url = Cache::get_cache_url(Cache::CSS_CACHE_FILE);
        if (\is_array($stylesheets)) {
            $stylesheets[] = $css_url;
        } else {
            $stylesheets .= ',' . $css_url;
        }
        return $stylesheets;
    }
}
