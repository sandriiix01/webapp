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
namespace Yabe\Webfont\Builder\Pinegrow;

use Yabe\Webfont\Admin\AdminPage;
use Yabe\Webfont\Builder\BuilderInterface;
use Yabe\Webfont\Core\Cache;
use Yabe\Webfont\Utils\Font;
use _YabeWebfont\YABE_WEBFONT;
/**
 * Pinegrow integration.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Main implements BuilderInterface
{
    public function __construct()
    {
        \add_action('admin_menu', static fn() => AdminPage::add_redirect_submenu_page('pinegrow-projects'), 1000001);
        \add_action('load-toplevel_page_pinegrow-projects', fn() => $this->init_hooks());
    }
    public function get_name() : string
    {
        return 'pinegrow';
    }
    public function init_hooks() : void
    {
        \add_action('admin_enqueue_scripts', fn() => $this->enqueue_scripts(), 1000001);
    }
    public function enqueue_scripts()
    {
        \wp_enqueue_script('yabe-webfont-for-pinegrow', \plugin_dir_url(__FILE__) . 'assets/script/pinegrow.js', [], YABE_WEBFONT::VERSION, \true);
        \wp_localize_script('yabe-webfont-for-pinegrow', 'yabeWebfontPinegrow', ['stylesheet_url' => Cache::get_cache_url(Cache::CSS_CACHE_FILE), 'font_families' => \array_map(static fn($f) => ['name' => $f['title'], 'key' => $f['css']['variable'], 'family' => $f['family']], Font::get_fonts())]);
    }
}
