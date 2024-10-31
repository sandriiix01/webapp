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
class DpluginsFontHero extends AbstractApi implements ApiInterface
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
        return 'migrations/font-hero-dplugins';
    }
    public function register_custom_endpoints() : void
    {
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/import-fonts', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->import_fonts($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
        \register_rest_route(self::API_NAMESPACE, $this->get_prefix() . '/clean-up', ['methods' => WP_REST_Server::CREATABLE, 'callback' => fn(\WP_REST_Request $wprestRequest): \WP_REST_Response => $this->clean_up($wprestRequest), 'permission_callback' => fn(\WP_REST_Request $wprestRequest): bool => $this->permission_callback($wprestRequest)]);
    }
    /**
     * @see Yabe\Webfont\Api\Font::import()
     */
    public function import_fonts(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        if (!\defined('DP_FH_BASE')) {
            return new WP_REST_Response(['message' => 'Font Hero plugin is not activated'], 404);
        }
        /** @var wpdb $wpdb */
        global $wpdb;
        \add_filter('wp_check_filetype_and_ext', static fn($data, $file, $filename, $mimes) => Upload::disable_real_mime_check($data, $file, $filename, $mimes), 10, 4);
        \add_filter('upload_mimes', static fn($mime_types) => Upload::upload_mimes($mime_types, \true), 1000001);
        $font_mime_types = ['woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf'];
        $dpfh_fonts = $wpdb->get_results(\sprintf('SELECT * FROM %sdp_fh_fonts', $wpdb->prefix));
        foreach ($dpfh_fonts as $dpfh_font) {
            $font_faces = [];
            $dpfh_font_faces = $wpdb->get_results($wpdb->prepare(\sprintf('SELECT * FROM %sdp_fh_font_faces WHERE font_id = %%d', $wpdb->prefix), $dpfh_font->id));
            foreach ($dpfh_font_faces as $dpfh_font_face) {
                $f_weight = \filter_var($dpfh_font_face->font_weight, \FILTER_SANITIZE_NUMBER_INT) ?: 100;
                $f_style = empty($dpfh_font_face->font_style) ? 'normal' : \sanitize_text_field($dpfh_font_face->font_style);
                $font_face = \array_filter($font_faces, static fn($ff) => $ff['weight'] === $f_weight && $ff['style'] === $f_style);
                if ($font_face !== []) {
                    $font_face = \array_shift($font_face);
                    $font_faces = \array_filter($font_faces, static fn($ff) => $ff['id'] !== $font_face['id']);
                } else {
                    $font_face = ['id' => Common::random_slug(10), 'weight' => $f_weight, 'width' => '', 'style' => $f_style, 'display' => empty($dpfh_font_face->font_display) ? '' : \sanitize_text_field($dpfh_font_face->font_display), 'selector' => '', 'comment' => '', 'unicodeRange' => '', 'preload' => $dpfh_font_face->font_preload === 'yes', 'files' => []];
                }
                if (!empty($dpfh_font_face->font_file)) {
                    $f_ext = \pathinfo($dpfh_font_face->font_file, \PATHINFO_EXTENSION);
                    $file_name = \sanitize_title_with_dashes(\sprintf('%s-%s-%s-%s-%s', \sanitize_text_field($dpfh_font->font_name), $f_weight, $f_style, Common::random_slug(5), \time())) . '.' . $f_ext;
                    $attachment_id = Upload::remote_upload_media($dpfh_font_face->font_file, $file_name, $font_mime_types[$f_ext]);
                    $font_face['files'][] = ['uid' => Common::random_slug(10), 'attachment_id' => $attachment_id, 'attachment_url' => \wp_get_attachment_url($attachment_id), 'extension' => $f_ext, 'mime' => $font_mime_types[$f_ext], 'filesize' => \filesize(\get_attached_file($attachment_id)), 'name' => \substr($file_name, 0, \strrpos($file_name, '.'))];
                }
                if (!empty($dpfh_font_face->font_file_2)) {
                    $f_ext = \pathinfo($dpfh_font_face->font_file_2, \PATHINFO_EXTENSION);
                    $file_name = \sanitize_title_with_dashes(\sprintf('%s-%s-%s-%s-%s', \sanitize_text_field($dpfh_font->font_name), $f_weight, $f_style, Common::random_slug(5), \time())) . '.' . $f_ext;
                    $attachment_id = Upload::remote_upload_media($dpfh_font_face->font_file_2, $file_name, $font_mime_types[$f_ext]);
                    $font_face['files'][] = ['uid' => Common::random_slug(10), 'attachment_id' => $attachment_id, 'attachment_url' => \wp_get_attachment_url($attachment_id), 'extension' => $f_ext, 'mime' => $font_mime_types[$f_ext], 'filesize' => \filesize(\get_attached_file($attachment_id)), 'name' => \substr($file_name, 0, \strrpos($file_name, '.'))];
                }
                if (!empty($dpfh_font_face->font_file_3)) {
                    $f_ext = \pathinfo($dpfh_font_face->font_file_3, \PATHINFO_EXTENSION);
                    $file_name = \sanitize_title_with_dashes(\sprintf('%s-%s-%s-%s-%s', \sanitize_text_field($dpfh_font->font_name), $f_weight, $f_style, Common::random_slug(5), \time())) . '.' . $f_ext;
                    $attachment_id = Upload::remote_upload_media($dpfh_font_face->font_file_3, $file_name, $font_mime_types[$f_ext]);
                    $font_face['files'][] = ['uid' => Common::random_slug(10), 'attachment_id' => $attachment_id, 'attachment_url' => \wp_get_attachment_url($attachment_id), 'extension' => $f_ext, 'mime' => $font_mime_types[$f_ext], 'filesize' => \filesize(\get_attached_file($attachment_id)), 'name' => \substr($file_name, 0, \strrpos($file_name, '.'))];
                }
                $font_faces[] = $font_face;
            }
            $metadata = ['preload' => \false, 'selector' => '', 'display' => 'auto'];
            $wpdb->insert(\sprintf('%syabe_webfont_fonts', $wpdb->prefix), ['type' => 'custom', 'title' => \sanitize_text_field($dpfh_font->font_name), 'slug' => Common::random_slug(10), 'family' => \sanitize_text_field($dpfh_font->font_name), 'status' => \true, 'metadata' => \json_encode($metadata, \JSON_THROW_ON_ERROR), 'font_faces' => \json_encode($font_faces, \JSON_THROW_ON_ERROR)], ['%s', '%s', '%s', '%s', '%d', '%s', '%s']);
            $id = $wpdb->insert_id;
            \do_action('a!yabe/webfont/api/font:migration', $id);
        }
        return new WP_REST_Response([]);
    }
    public function clean_up(WP_REST_Request $wprestRequest) : WP_REST_Response
    {
        if (!\defined('DP_FH_BASE')) {
            return new WP_REST_Response(['message' => 'Font Hero plugin is not activated'], 404);
        }
        /** @var wpdb $wpdb */
        global $wpdb;
        $wpdb->query(\sprintf('DELETE FROM %sdp_fh_fonts', $wpdb->prefix));
        $wpdb->query(\sprintf('DELETE FROM %sdp_fh_font_faces', $wpdb->prefix));
        \do_action('a!yabe/webfont/api/font:clean_up');
        return new WP_REST_Response([]);
    }
    private function permission_callback(WP_REST_Request $wprestRequest) : bool
    {
        return \wp_verify_nonce($wprestRequest->get_header('X-WP-Nonce'), 'wp_rest') && \current_user_can('manage_options');
    }
}
