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
namespace Yabe\Webfont\Utils;

use Exception;
use Throwable;
use WP_Error;
/**
 * Upload utility functions for the plugin.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Upload
{
    /**
     * Add the font mime types to the allowed upload mimes.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/CSS/@font-face#description
     * @see https://developer.wordpress.org/reference/hooks/upload_mimes/
     */
    public static function upload_mimes(array $mimes, bool $manual_upload = \false) : array
    {
        if (!$manual_upload && (!\current_user_can('manage_options') || !isset($_POST['yabe_webfont_font_upload']))) {
            return $mimes;
        }
        $exts = ['woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'eot' => 'font/eot'];
        foreach ($exts as $ext => $ext_mime) {
            if (!isset($mimes[$ext])) {
                $mimes[$ext] = $ext_mime;
            }
        }
        return $mimes;
    }
    /**
     * Disable real MIME check (introduced in WordPress 4.7.1)
     *
     * @see https://wordpress.stackexchange.com/a/252296/44794
     * @see https://developer.wordpress.org/reference/hooks/wp_check_filetype_and_ext/
     */
    public static function disable_real_mime_check(array $data, string $file, string $filename, $mimes)
    {
        $filetype = \wp_check_filetype($filename, $mimes);
        return ['ext' => $filetype['ext'], 'type' => $filetype['type'], 'proper_filename' => $data['proper_filename']];
    }
    /**
     * Remote upload file to WordPress media library.
     * The implementation is based on the https://rudrastyh.com/wordpress/how-to-add-images-to-media-library-from-uploaded-files-programmatically.html#upload-image-from-url
     *
     * @param string $file_url URL of the remote file
     * @param string $file_name Name of the remote file to be stored in the media library
     * @param string $mime_type Mime type of the remote file
     * @return int|WP_Error|false Attachment ID on success, WP_Error or false on failure
     * @throws Exception
     */
    public static function remote_upload_media(string $file_url, string $file_name, string $mime_type)
    {
        require_once \ABSPATH . 'wp-admin/includes/file.php';
        $file_url = \apply_filters('f!yabe/webfont/utils/upload:remote_upload_media.file_url', $file_url);
        $temp_file = \download_url($file_url);
        if (\is_wp_error($temp_file)) {
            return $temp_file;
        }
        $file = ['name' => $file_name, 'type' => $mime_type, 'tmp_name' => $temp_file, 'size' => \filesize($temp_file)];
        // changing the directory
        \add_filter('upload_dir', [self::class, 'wpse_custom_upload_dir']);
        $sideload = \wp_handle_sideload($file, ['test_form' => \false, 'test_size' => \false]);
        if (!empty($sideload['error'])) {
            // you may return error message if you want
            return \false;
        }
        // it is time to add our uploaded image into WordPress media library
        $attachment_id = \wp_insert_attachment(['guid' => $sideload['url'], 'post_mime_type' => $sideload['type'], 'post_title' => \basename($sideload['file']), 'post_content' => '', 'post_status' => 'inherit'], $sideload['file']);
        // remove so it doesn't apply to all uploads
        \remove_filter('upload_dir', [self::class, 'wpse_custom_upload_dir']);
        if (\is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        if (!$attachment_id) {
            return \false;
        }
        try {
            if (\file_exists($temp_file)) {
                \unlink($temp_file);
            }
        } catch (Throwable $throwable) {
            throw $throwable;
        }
        return $attachment_id;
    }
    /**
     * Remote upload file to WordPress media library.
     * The implementation is based on the https://rudrastyh.com/wordpress/how-to-add-images-to-media-library-from-uploaded-files-programmatically.html#upload-image-from-url
     *
     * @param string $binary binary-safe string containing the file
     * @param string $file_name Name of the remote file to be stored in the media library
     * @param string $mime_type Mime type of the remote file
     * @return int|WP_Error|false Attachment ID on success, WP_Error or false on failure
     * @throws Exception
     */
    public static function binary_upload_media(string $binary, string $file_name, string $mime_type)
    {
        require_once \ABSPATH . 'wp-admin/includes/file.php';
        $temp_file = \wp_tempnam($file_name);
        if (!$temp_file) {
            return \false;
        }
        $handle = \fopen($temp_file, 'wb');
        if (!$handle) {
            return \false;
        }
        \fwrite($handle, $binary);
        \fclose($handle);
        $file = ['name' => $file_name, 'type' => $mime_type, 'tmp_name' => $temp_file, 'size' => \filesize($temp_file)];
        $sideload = \wp_handle_sideload($file, ['test_form' => \false, 'test_size' => \false]);
        if (!empty($sideload['error'])) {
            // you may return error message if you want
            return \false;
        }
        // it is time to add our uploaded image into WordPress media library
        $attachment_id = \wp_insert_attachment(['guid' => $sideload['url'], 'post_mime_type' => $sideload['type'], 'post_title' => \basename($sideload['file']), 'post_content' => '', 'post_status' => 'inherit'], $sideload['file']);
        if (\is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        if (!$attachment_id) {
            return \false;
        }
        try {
            if (\file_exists($temp_file)) {
                \unlink($temp_file);
            }
        } catch (Throwable $throwable) {
            throw $throwable;
        }
        return $attachment_id;
    }
    /**
     * @see https://developer.mozilla.org/en-US/docs/Web/CSS/@font-face/src#font_formats
     * @param string $mime file extension or mime type
     */
    public static function mime_keyword(string $mime) : string
    {
        switch ($mime) {
            case 'woff2':
            case 'font/woff2':
                return 'woff2';
            case 'woff':
            case 'font/woff':
                return 'woff';
            case 'ttf':
            case 'font/ttf':
                return 'truetype';
            case 'otf':
            case 'font/otf':
                return 'opentype';
            case 'eot':
            case 'font/eot':
                return 'embedded-opentype';
            default:
                return 'woff2';
        }
    }
    /**
     * Get the new attachment url of a font face.
     */
    public static function refresh_font_faces_attachment_url(array $font_faces) : array
    {
        foreach ($font_faces as $i => $font_face) {
            foreach ($font_face->files as $j => $file) {
                $attachment_url = \wp_get_attachment_url($file->attachment_id);
                if ($attachment_url) {
                    $parsed = \parse_url($attachment_url);
                    $font_faces[$i]->files[$j]->attachment_url = $parsed['path'];
                }
            }
        }
        return $font_faces;
    }
    /**
     * Get the new attachment url of a Google Fonts.
     */
    public static function refresh_google_fonts_attachment_url(array $font_files) : array
    {
        foreach ($font_files as $i => $font_file) {
            if (\property_exists($font_file, 'file')) {
                $attachment_url = \wp_get_attachment_url($font_file->file->attachment_id);
                if ($attachment_url) {
                    $parsed = \parse_url($attachment_url);
                    $font_files[$i]->file->attachment_url = $parsed['path'];
                }
            }
        }
        return $font_files;
    }
    public static function wpse_custom_upload_dir($dir_data)
    {
        $custom_dir = 'yabe-webfont/fonts';
        $dir_data['path'] = $dir_data['basedir'] . '/' . $custom_dir;
        $dir_data['subdir'] = '/' . $custom_dir;
        $dir_data['url'] = $dir_data['baseurl'] . '/' . $custom_dir;
        return $dir_data;
    }
}
