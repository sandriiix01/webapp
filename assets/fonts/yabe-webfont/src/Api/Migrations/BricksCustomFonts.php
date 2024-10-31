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
namespace Yabe\Webfont\Api\Migrations;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use wpdb;
use Yabe\Webfont\Api\AbstractApi;
use Yabe\Webfont\Api\ApiInterface;
use Yabe\Webfont\Utils\Common;
use Yabe\Webfont\Utils\Upload;
class BricksCustomFonts extends AbstractApi implements ApiInterface
{
    public function __construct()
    {
        $hooks = ['a!yabe/webfont/api/font:migration', 'a!yabe/webfont/api/font:clean_up'];
        foreach ($hooks as $hook) {
            \add_action($hook, static function ($f) use($hook) {
                /**
                 * Listen to several font events and emit a wrapper event
                 *
                 * @param string $hook Hook name
                 * @param object|int $f Font ID or Font Object
                 */
                \do_action('a!yabe/webfont/api/font:fonts_event_async', $hook, $f);
            }, 10, 1);
        }
    }
    public function get_prefix() : string
    {
        return 'migrations/bricks-custom-fonts';
    }
    public function register_custom_endpoints() : void
    {
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/import-fonts', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->import_fonts($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/clean-up', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->clean_up($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
    }
    /**
     * @see Bricks\Custom_Fonts::get_custom_fonts()
     * @see Yabe\Webfont\Api\Font::import()
     */
    public function import_fonts(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        if (!\defined('BRICKS_DB_CUSTOM_FONTS')) {
            return new WP_REST_Response(['message' => 'Bricks theme not activated'], 404);
        }
        /** @var wpdb $wpdb */
        global $wpdb;
        $font_ids = \get_posts(['post_type' => \BRICKS_DB_CUSTOM_FONTS, 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => \true]);
        \add_filter('wp_check_filetype_and_ext', static fn($data, $file, $filename, $mimes) => Upload::disable_real_mime_check($data, $file, $filename, $mimes), 10, 4);
        \add_filter('upload_mimes', static fn($mime_types) => Upload::upload_mimes($mime_types, \true), 1000001);
        $font_mime_types = ['woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf'];
        foreach ($font_ids as $font_id) {
            $bricks_font_faces = \get_post_meta($font_id, \BRICKS_DB_CUSTOM_FONT_FACES, \true);
            if ($bricks_font_faces === \false || empty($bricks_font_faces)) {
                continue;
            }
            $post = \get_post($font_id);
            $font_faces = [];
            // $key: font-weight + variant (e.g.: 700italic)
            foreach ($bricks_font_faces as $key => $bricks_font_face) {
                $font_weight = \filter_var($key, \FILTER_SANITIZE_NUMBER_INT);
                $font_style = \str_replace($font_weight, '', $key);
                $font_face = ['id' => Common::random_slug(10), 'weight' => $font_weight, 'width' => '', 'style' => empty($font_style) ? 'normal' : $font_style, 'display' => '', 'selector' => '', 'comment' => '', 'unicodeRange' => '', 'preload' => \false, 'files' => []];
                foreach ($bricks_font_face as $key_format => $bricks_font_face_file) {
                    $file_name = \sanitize_title_with_dashes(\sprintf('%s-%s-%s-%s-%s', $post->post_title, $font_weight, empty($font_style) ? 'normal' : $font_style, Common::random_slug(5), \time())) . '.' . $key_format;
                    $old_attachment_url = \wp_get_attachment_url($bricks_font_face_file);
                    if ($old_attachment_url === \false) {
                        continue;
                    }
                    $attachment_id = Upload::remote_upload_media($old_attachment_url, $file_name, $font_mime_types[$key_format]);
                    $font_face['files'][] = ['uid' => Common::random_slug(10), 'attachment_id' => $attachment_id, 'attachment_url' => \wp_get_attachment_url($attachment_id), 'extension' => $key_format, 'mime' => $font_mime_types[$key_format], 'filesize' => \filesize(\get_attached_file($attachment_id)), 'name' => \substr($file_name, 0, \strrpos($file_name, '.'))];
                }
                $font_faces[] = $font_face;
            }
            $metadata = ['preload' => \false, 'selector' => '', 'display' => 'auto'];
            $wpdb->insert(\sprintf('%syabe_webfont_fonts', $wpdb->prefix), ['type' => 'custom', 'title' => $post->post_title, 'slug' => Common::random_slug(10), 'family' => $post->post_title, 'status' => \true, 'metadata' => \json_encode($metadata, \JSON_THROW_ON_ERROR), 'font_faces' => \json_encode($font_faces, \JSON_THROW_ON_ERROR)], ['%s', '%s', '%s', '%s', '%d', '%s', '%s']);
            $id = $wpdb->insert_id;
            \do_action('a!yabe/webfont/api/font:migration', $id);
        }
        return new WP_REST_Response([]);
    }
    public function clean_up(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        if (!\defined('BRICKS_DB_CUSTOM_FONTS')) {
            return new WP_REST_Response(['message' => 'Bricks theme not activated'], 404);
        }
        $font_ids = \get_posts(['post_type' => \BRICKS_DB_CUSTOM_FONTS, 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => \true]);
        foreach ($font_ids as $font_id) {
            \wp_trash_post($font_id);
        }
        \do_action('a!yabe/webfont/api/font:clean_up');
        return new WP_REST_Response([]);
    }
    private function permission_callback(WP_REST_Request $wprestRequest) : bool
    {
        return \wp_verify_nonce($wprestRequest->get_header('X-WP-Nonce'), 'wp_rest') && \current_user_can('manage_options');
    }
}
