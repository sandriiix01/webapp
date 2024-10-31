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
use Yabe\Webfont\Plugin;
use _YabeWebfont\YABE_WEBFONT;
class License extends AbstractApi implements ApiInterface
{
    public function __construct()
    {
    }
    public function get_prefix() : string
    {
        return 'setting/license';
    }
    public function register_custom_endpoints() : void
    {
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/index', ['methods' => WP_REST_Server::READABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->index($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/store', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->store($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
    }
    public function index(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        return new WP_REST_Response(['license' => $this->get_license()]);
    }
    private function permission_callback(WP_REST_Request $wprestRequest) : bool
    {
        return \wp_verify_nonce($wprestRequest->get_header('X-WP-Nonce'), 'wp_rest') && \current_user_can('manage_options');
    }
    private function store(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        $payload = $wprestRequest->get_json_params();
        $new_license_key = \sanitize_text_field($payload['license']);
        $old_license = \get_option(YABE_WEBFONT::WP_OPTION . '_license', ['key' => '', 'opt_in_pre_release' => \false]);
        $plugin_updater = Plugin::get_instance()->plugin_updater;
        $notice = [];
        if ($new_license_key !== $old_license['key']) {
            if ($new_license_key === '') {
                $plugin_updater->deactivate();
                $notice['success'] = 'Plugin license key de-activated successfully';
            } else {
                $response = $plugin_updater->activate($new_license_key);
                if (\is_wp_error($response) || \wp_remote_retrieve_response_code($response) !== 200) {
                    $notice['error'] = \is_wp_error($response) ? $response->get_error_message() : 'An error occurred, please try again.';
                } else {
                    $license_data = \json_decode(\wp_remote_retrieve_body($response), null, 512, \JSON_THROW_ON_ERROR);
                    if ($license_data->license !== 'valid') {
                        $notice['error'] = $plugin_updater->error_message($license_data->error);
                    } else {
                        $notice['success'] = 'Plugin license key activated successfully';
                    }
                }
            }
        }
        \update_option(YABE_WEBFONT::WP_OPTION . '_license', ['key' => $new_license_key, 'opt_in_pre_release' => \false]);
        return new WP_REST_Response(['license' => $this->get_license(), 'notice' => $notice]);
    }
    private function get_license() : array
    {
        $license = \get_option(YABE_WEBFONT::WP_OPTION . '_license', ['key' => '', 'opt_in_pre_release' => \false]);
        try {
            $license['is_activated'] = Plugin::get_instance()->plugin_updater->is_activated();
        } catch (\Throwable $throwable) {
            //throw $th;
            $license['is_activated'] = \false;
        }
        return $license;
    }
}
