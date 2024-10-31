<?php

/**
 * Yabe Webfont
 *
 * @wordpress-plugin
 * Plugin Name:         Yabe Webfont
 * Plugin URI:          https://webfont.yabe.land
 * Description:         Self-host Google Fonts and the dedicated custom fonts manager for WordPress with seamless visual/page builders integration.
 * Version:             1.0.69
 * Requires at least:   6.0
 * Requires PHP:        7.4
 * Author:              Rosua
 * Author URI:          https://rosua.org
 * Donate link:         https://ko-fi.com/Q5Q75XSF7
 * Text Domain:         yabe-webfont
 * Domain Path:         /languages
 * License:             GPL-3.0-or-later
 *
 * @package             Yabe
 * @author              Joshua Gugun Siagian <suabahasa@gmail.com>
 */
declare (strict_types=1);
namespace _YabeWebfont;

\defined('ABSPATH') || exit;
if (\file_exists(__DIR__ . '/vendor/scoper-autoload.php')) {
    require_once __DIR__ . '/vendor/scoper-autoload.php';
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}
\Yabe\Webfont\Plugin::get_instance()->boot();
