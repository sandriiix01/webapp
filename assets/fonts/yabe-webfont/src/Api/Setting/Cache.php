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
namespace Yabe\Webfont\Api\Setting;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yabe\Webfont\Api\AbstractApi;
use Yabe\Webfont\Api\ApiInterface;
use Yabe\Webfont\Core\Cache as CoreCache;
class Cache extends AbstractApi implements ApiInterface
{
    public function __construct()
    {
    }
    public function get_prefix() : string
    {
        return 'setting/cache';
    }
    public function register_custom_endpoints() : void
    {
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/index', ['methods' => WP_REST_Server::READABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->index($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/generate', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->generate($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
    }
    public function index(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        $cache_path = CoreCache::get_cache_path(CoreCache::CSS_CACHE_FILE);
        $cache = ['last_generated' => '', 'pending_task' => \false, 'file_url' => ''];
        if (\file_exists($cache_path) && \is_readable($cache_path)) {
            $cache['file_url'] = CoreCache::get_cache_url(CoreCache::CSS_CACHE_FILE);
            $cache['last_generated'] = \filemtime($cache_path);
        }
        if (\wp_next_scheduled('a!yabe/webfont/core/cache:build_cache')) {
            $cache['pending_task'] = \true;
        }
        return new WP_REST_Response(['cache' => $cache]);
    }
    public function generate(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        \do_action('a!yabe/webfont/core/cache:build_cache');
        \wp_clear_scheduled_hook('a!yabe/webfont/core/cache:build_cache');
        return $this->index($wprestRequest);
    }
    private function permission_callback(WP_REST_Request $wprestRequest) : bool
    {
        return \wp_verify_nonce($wprestRequest->get_header('X-WP-Nonce'), 'wp_rest') && \current_user_can('manage_options');
    }
}
