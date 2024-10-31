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
namespace Yabe\Webfont\Builder\GeneratePress;

use Yabe\Webfont\Builder\BuilderInterface;
use Yabe\Webfont\Utils\Font;
/**
 * GeneratePress and GenerateBlocks integration.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Main implements BuilderInterface
{
    public function __construct()
    {
        /**
         * Disable GeneratePress's built-in Google Fonts.
         */
        \add_filter('generate_font_manager_show_google_fonts', static fn() => \false, 1000001, 0);
        /**
         * Disable GenerateBlocks's built-in Google Fonts.
         */
        \add_filter('generateblocks_option_defaults', fn($value) => $this->filter_generateblocks_option_defaults($value), 1000001, 1);
        \add_filter('option_generateblocks', fn($value, $option) => $this->filter_option_generateblocks($value), 1000001, 2);
        /**
         * Add custom font to GeneratePress.
         * Append the data on the pre_option, and rewrite the data on the option and pre_update_option.
         */
        \add_filter('generate_option_defaults', fn($opt) => $this->filter_generate_option_defaults($opt), 1000001);
        /**
         * @deprecated version 2.0.11
         * @see https://github.com/tomusborne/generatepress/blob/e7fbf5693bfe4325a41cae988e3eda16550d4025/inc/defaults.php#L412
         */
        // add_filter('generate_typography_default_fonts', static fn ($fonts) => array_merge($fonts, array_column(Font::get_fonts(), 'family')), 1_000_001);
        /**
         * @deprecated version 2.0.46
         */
        // add_filter('pre_option_generate_settings', fn ($value, $option) => $this->generate_settings($value), 1_000_001, 2);
        // add_filter('option_generate_settings', fn ($value, $option) => $this->generate_settings($value), 1_000_001, 2);
        // add_filter('pre_update_option_generate_settings', fn ($value, $old_value, $option) => $this->generate_settings($value), 1_000_001, 3);
        /**
         * Add custom font to GenerateBlocks.
         *
         * @see https://github.com/tomusborne/generateblocks/issues/937
         */
        \add_filter('generateblocks_typography_font_family_list', fn($fonts) => $this->generateblocks_typography_font_family_list($fonts), 1000001);
    }
    public function get_name() : string
    {
        return 'generate-press';
    }
    public function generate_settings($opt)
    {
        $fonts = Font::get_fonts();
        if (!\is_array($opt)) {
            $opt = [];
        }
        if (!\array_key_exists('font_manager', $opt) || !\is_array($opt['font_manager'])) {
            $opt['font_manager'] = [];
        }
        foreach ($fonts as $font) {
            if (!\in_array($font['family'], \array_column($opt['font_manager'], 'fontFamily'), \true)) {
                $opt['font_manager'][] = ['fontFamily' => $font['family'], 'googleFont' => \false, 'googleFontApi' => 0];
            }
        }
        return $opt;
    }
    public function filter_generateblocks_option_defaults($opt)
    {
        if (\is_array($opt)) {
            $opt['disable_google_fonts'] = \true;
        }
        return $opt;
    }
    public function filter_option_generateblocks($opt)
    {
        if (\is_array($opt)) {
            $opt['disable_google_fonts'] = \true;
        }
        return $opt;
    }
    public function generateblocks_typography_font_family_list($gb_fonts)
    {
        $fonts = Font::get_fonts();
        $yabe_fonts = \array_map(static fn($f) => ['label' => $f['title'], 'value' => $f['family']], $fonts);
        return \array_merge([['label' => 'Yabe Webfont', 'options' => $yabe_fonts]], \is_array($gb_fonts) ? $gb_fonts : \iterator_to_array($gb_fonts));
    }
    public function filter_generate_option_defaults($opt)
    {
        $fonts = Font::get_fonts();
        if (!\array_key_exists('font_manager', $opt) || !\is_array($opt['font_manager'])) {
            $opt['font_manager'] = [];
        }
        foreach ($fonts as $font) {
            if (!\in_array($font['family'], \array_column($opt['font_manager'], 'fontFamily'), \true)) {
                $opt['font_manager'][] = ['fontFamily' => $font['family'], 'googleFont' => \false, 'googleFontApi' => 0];
            }
        }
        return $opt;
    }
}
