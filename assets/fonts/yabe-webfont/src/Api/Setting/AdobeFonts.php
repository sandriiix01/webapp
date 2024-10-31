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
use wpdb;
use Yabe\Webfont\Api\AbstractApi;
use Yabe\Webfont\Api\ApiInterface;
class AdobeFonts extends AbstractApi implements ApiInterface
{
    public function __construct()
    {
    }
    public function get_prefix() : string
    {
        return 'setting/adobe-fonts';
    }
    public function register_custom_endpoints() : void
    {
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/get-kits', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->get_kits($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/sync', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->sync($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/destroy', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->destroy($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
    }
    public function get_kits(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        $payload = $wprestRequest->get_json_params();
        $project_id = $payload['project_id'];
        $response = \wp_remote_get(\sprintf('https://typekit.com/api/v1/json/kits/%s/published', $project_id));
        if (\is_wp_error($response)) {
            return new WP_REST_Response(['message' => 'Failed to get kits'], 500);
        }
        $status_code = \wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new WP_REST_Response(['message' => 'Kits not found'], $status_code);
        }
        $body = \wp_remote_retrieve_body($response);
        $kits = \json_decode($body, \true, 512, \JSON_THROW_ON_ERROR);
        return new WP_REST_Response(['data' => $kits]);
    }
    public function sync(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        $payload = $wprestRequest->get_json_params();
        $kits = $payload['kit']['families'];
        $this->delete_fonts();
        foreach ($kits as $kit) {
            $wpdb->insert(\sprintf('%syabe_webfont_fonts', $wpdb->prefix), ['status' => 1, 'type' => 'adobe-fonts', 'title' => $kit['name'], 'slug' => $kit['id'], 'family' => $kit['slug'], 'metadata' => \json_encode($kit, \JSON_THROW_ON_ERROR), 'font_faces' => \json_encode([], \JSON_THROW_ON_ERROR)], ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);
        }
        \do_action('a!yabe/webfont/api/font:fonts_event');
        return new WP_REST_Response([]);
    }
    public function destroy(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        $this->delete_fonts();
        return new WP_REST_Response([]);
    }
    private function permission_callback(WP_REST_Request $wprestRequest) : bool
    {
        return \wp_verify_nonce($wprestRequest->get_header('X-WP-Nonce'), 'wp_rest') && \current_user_can('manage_options');
    }
    private function delete_fonts()
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        $wpdb->delete(\sprintf('%syabe_webfont_fonts', $wpdb->prefix), ['type' => 'adobe-fonts'], ['%s']);
    }
}
