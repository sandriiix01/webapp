<?php

/**
 * Rosua Migrations
 *
 * @wordpress-plugin
 * Plugin Name:         Rosua Migrations
 * Plugin URI:          https://rosua.org
 * Description:         Database Migrations tool for WordPress plugins
 * Version:             0.0.1-DEV
 * Requires at least:   6.0
 * Requires PHP:        7.4
 * Author:              Rosua
 * Author URI:          https://rosua.org
 * Donate link:         https://github.com/sponsors/suabahasa
 * Text Domain:         rosua-migrations
 * Domain Path:         /languages
 *
 * @package             Rosua
 * @author              Joshua <id@rosua.org>
 */
declare (strict_types=1);
namespace _YabeWebfont;

use _YabeWebfont\Rosua\Migrations\Migrator;
\defined('ABSPATH') || exit;
\define('_YabeWebfont\\ROSUA_MIGRATIONS_FILE', __FILE__);
require_once __DIR__ . '/vendor/autoload.php';
$migrator = new Migrator(['tableName' => 'rosua_migrations', 'namespace' => 'RosuaMigrations', 'directory' => 'migrations', 'basePath' => __DIR__]);
\register_activation_hook(\_YabeWebfont\ROSUA_MIGRATIONS_FILE, function () use($migrator) {
    $migrator->install();
});
$migrator->boot();
