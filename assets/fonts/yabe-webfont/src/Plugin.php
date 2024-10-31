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
namespace Yabe\Webfont;

use _YabeWebfont\EDD_SL\PluginUpdater;
use Exception;
use Yabe\Webfont\Admin\AdminPage;
use Yabe\Webfont\Api\Router as ApiRouter;
use Yabe\Webfont\Builder\Integration as BuilderIntegration;
use Yabe\Webfont\Core\Cache;
use Yabe\Webfont\Core\Runtime;
use Yabe\Webfont\Utils\Common;
use Yabe\Webfont\Utils\Debug;
use Yabe\Webfont\Utils\Notice;
use _YabeWebfont\YABE_WEBFONT;
/**
 * Manage the plugin lifecycle and provides a single point of entry to the plugin.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
final class Plugin
{
    /**
     * Easy Digital Downloads Software Licensing integration wrapper.
     * Pro version only.
     *
     * @var PluginUpdater|null
     */
    public $plugin_updater = null;
    /**
     * Stores the instance, implementing a Singleton pattern.
     */
    private static self $instance;
    /**
     * The Singleton's constructor should always be private to prevent direct
     * construction calls with the `new` operator.
     */
    private function __construct()
    {
    }
    /**
     * Singletons should not be cloneable.
     */
    private function __clone()
    {
    }
    /**
     * Singletons should not be restorable from strings.
     *
     * @throws Exception Cannot unserialize a singleton.
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a singleton.');
    }
    /**
     * This is the static method that controls the access to the singleton
     * instance. On the first run, it creates a singleton object and places it
     * into the static property. On subsequent runs, it returns the client existing
     * object stored in the static property.
     */
    public static function get_instance() : self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function boot_debug()
    {
        if (\WP_DEBUG === \false) {
            return;
        }
        // if (class_exists(\Sentry\SentrySdk::class)) {
        // }
        // when php exits, call the shutdown function.
        \register_shutdown_function(static fn() => Debug::shutdown());
    }
    public function boot_migration()
    {
        \do_action('a!yabe/webfont/plugin:boot_migration.start');
        /** @var wpdb $wpdb */
        global $wpdb;
        $wpdb->yabe_webfont_prefix = YABE_WEBFONT::DB_TABLE_PREFIX;
        new \Yabe\Webfont\Migration();
        \do_action('a!yabe/webfont/plugin:boot_migration.end');
    }
    /**
     * Boot to the Plugin.
     */
    public function boot() : void
    {
        \do_action('a!yabe/webfont/plugins:boot_start');
        $this->boot_debug();
        $this->boot_migration();
        // (de)activation hooks.
        \register_activation_hook(YABE_WEBFONT::FILE, function () : void {
            $this->activate_plugin();
        });
        \register_deactivation_hook(YABE_WEBFONT::FILE, function () : void {
            $this->deactivate_plugin();
        });
        // upgrade hooks.
        \add_action('upgrader_process_complete', function ($upgrader, $options) : void {
            if ($options['action'] === 'update' && $options['type'] === 'plugin') {
                foreach ($options['plugins'] as $plugin) {
                    if ($plugin === \plugin_basename(YABE_WEBFONT::FILE)) {
                        $this->upgrade_plugin();
                    }
                }
            }
        }, 10, 2);
        new Cache();
        new Runtime();
        new BuilderIntegration();
        new ApiRouter();
        $this->maybe_update_plugin();
        // admin hooks.
        if (\is_admin()) {
            \add_filter('plugin_action_links_' . \plugin_basename(YABE_WEBFONT::FILE), fn($links) => $this->plugin_action_links($links));
            \add_action('plugins_loaded', function () : void {
                $this->plugins_loaded_admin();
            }, 100);
            new AdminPage();
            \do_action('a!yabe/webfont/plugins:boot_admin');
        }
        \do_action('a!yabe/webfont/plugins:boot_end');
    }
    /**
     * Handle the plugin's activation
     */
    public function activate_plugin() : void
    {
        \do_action('a!yabe/webfont/plugins:activate_plugin_start');
        \update_option(YABE_WEBFONT::WP_OPTION . '_version', YABE_WEBFONT::VERSION);
        if (\class_exists(PluginUpdater::class)) {
            $this->maybe_embedded_license();
            $this->maybe_update_plugin()->clear_cache();
        }
        \delete_transient('yabe_webfont_scanned_apis_' . YABE_WEBFONT::VERSION);
        \do_action('a!yabe/webfont/plugins:activate_plugin_end');
    }
    /**
     * Handle plugin's deactivation by (maybe) cleaning up after ourselves.
     */
    public function deactivate_plugin() : void
    {
        \do_action('a!yabe/webfont/plugins:deactivate_plugin_start');
        // TODO: Add deactivation logic here.
        \do_action('a!yabe/webfont/plugins:deactivate_plugin_end');
    }
    /**
     * Handle the plugin's upgrade
     */
    public function upgrade_plugin() : void
    {
        \do_action('a!yabe/webfont/plugins:upgrade_plugin_start');
        // TODO: Add upgrade logic here.
        \do_action('a!yabe/webfont/plugins:upgrade_plugin_end');
    }
    /**
     * Warm up the plugin for admin.
     */
    public function plugins_loaded_admin() : void
    {
        \add_action('admin_notices', static function () {
            $messages = Notice::get_lists();
            if ($messages && \is_array($messages)) {
                foreach ($messages as $message) {
                    echo \sprintf('<div class="notice notice-%s is-dismissible %s">%s</div>', \esc_attr($message['status']), YABE_WEBFONT::WP_OPTION, \esc_html($message['message']));
                }
            }
        }, 100);
    }
    /**
     * Add plugin action links.
     *
     * @param array<string> $links
     * @return array<string>
     */
    public function plugin_action_links(array $links) : array
    {
        $base_url = AdminPage::get_page_url();
        \array_unshift($links, \sprintf('<a href="%s">%s</a>', \esc_url(\sprintf('%s#/settings', $base_url)), \esc_html__('Settings', 'yabe-webfont')));
        \array_unshift($links, \sprintf('<a href="%s">%s</a>', \esc_url(\sprintf('%s#/fonts/index', $base_url)), \esc_html__('Fonts', 'yabe-webfont')));
        if (!\class_exists(PluginUpdater::class)) {
            \array_unshift($links, \sprintf('<a href="%s" style="color:#067b34;font-weight:600;" target="_blank">%s</a>', \esc_url(Common::plugin_data('PluginURI') . '?utm_source=WordPress&utm_campaign=liteplugin&utm_medium=plugin-action-links&utm_content=Upgrade#pricing'), \esc_html__('Upgrade to Pro', 'yabe-webfont')));
        }
        return $links;
    }
    /**
     * Initialize the plugin updater.
     * Pro version only.
     *
     * @return PluginUpdater
     */
    public function maybe_update_plugin()
    {
        if (!\class_exists(PluginUpdater::class)) {
            return null;
        }
        if ($this->plugin_updater instanceof \_YabeWebfont\EDD_SL\PluginUpdater) {
            return $this->plugin_updater;
        }
        $license = \get_option(YABE_WEBFONT::WP_OPTION . '_license', ['key' => '', 'opt_in_pre_release' => \false]);
        $this->plugin_updater = new PluginUpdater(YABE_WEBFONT::WP_OPTION, ['version' => YABE_WEBFONT::VERSION, 'license' => $license['key'] ? \trim($license['key']) : \false, 'beta' => $license['opt_in_pre_release'], 'plugin_file' => YABE_WEBFONT::FILE, 'item_id' => YABE_WEBFONT::EDD_STORE['item_id'], 'store_url' => YABE_WEBFONT::EDD_STORE['store_url'], 'author' => YABE_WEBFONT::EDD_STORE['author']]);
        return $this->plugin_updater;
    }
    /**
     * Check if the plugin distributed with an embedded license and activate the license.
     * Pro version only.
     */
    private function maybe_embedded_license() : void
    {
        $license_file = \dirname(YABE_WEBFONT::FILE) . '/license-data.php';
        if (!\file_exists($license_file)) {
            return;
        }
        require_once $license_file;
        $const_name = 'ROSUA_EMBEDDED_LICENSE_KEY_' . YABE_WEBFONT::EDD_STORE['item_id'];
        if (!\defined($const_name)) {
            return;
        }
        $license_key = \constant($const_name);
        \update_option(YABE_WEBFONT::WP_OPTION . '_license', ['key' => $license_key, 'opt_in_pre_release' => \false]);
        \unlink($license_file);
        // activate the license.
        $this->maybe_update_plugin()->activate($license_key);
    }
}
