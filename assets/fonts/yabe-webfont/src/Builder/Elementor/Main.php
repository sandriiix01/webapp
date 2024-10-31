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
namespace Yabe\Webfont\Builder\Elementor;

use Yabe\Webfont\Admin\AdminPage;
use Yabe\Webfont\Builder\BuilderInterface;
use Yabe\Webfont\Utils\Font;
/**
 * Elementor integration.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Main implements BuilderInterface
{
    public function __construct()
    {
        /**
         * Disable Elementor's built-in Google Fonts.
         */
        \add_filter('pre_option_elementor_google_font', static fn($pre_option, $option, $default) => '0', 1000001, 3);
        \add_filter('pre_update_option_elementor_google_font', static fn($value, $old_value, $option) => '0', 1000001, 3);
        \add_filter('elementor/fonts/groups', static fn($groups) => \array_merge(['yabe-webfont' => 'Yabe Webfont'], $groups), 1000001);
        \add_filter('elementor/fonts/additional_fonts', fn($fonts) => $this->filter_elementor_fonts($fonts), 1000001);
        \add_action('admin_menu', static fn() => AdminPage::add_redirect_submenu_page('elementor'), 1000001);
    }
    public function get_name() : string
    {
        return 'elementor';
    }
    public function filter_elementor_fonts($elementor_fonts)
    {
        $fonts = Font::get_fonts();
        foreach ($fonts as $font) {
            $elementor_fonts[$font['family']] = 'yabe-webfont';
        }
        return $elementor_fonts;
    }
}
