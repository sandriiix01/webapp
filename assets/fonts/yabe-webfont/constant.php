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
namespace _YabeWebfont;

/**
 * Plugin constants.
 *
 * @since 2.0.0
 */
class YABE_WEBFONT
{
    /**
     * @var string
     */
    public const FILE = __DIR__ . '/yabe-webfont.php';
    /**
     * @var string
     */
    public const VERSION = '1.0.69';
    /**
     * @var int
     */
    public const VERSION_ID = 10069;
    /**
     * @var int
     */
    public const MAJOR_VERSION = 1;
    /**
     * @var int
     */
    public const MINOR_VERSION = 0;
    /**
     * @var int
     */
    public const RELEASE_VERSION = 69;
    /**
     * @var string
     */
    public const EXTRA_VERSION = '';
    /**
     * @var string
     */
    public const WP_OPTION = 'yabe_webfont';
    /**
     * @var string
     */
    public const DB_TABLE_PREFIX = 'yabe_webfont';
    /**
     * The text domain should use the literal string 'yabe-webfont' as the text domain.
     * This constant is used for reference only and should not be used as the actual text domain.
     * 
     * @var string
     */
    public const TEXT_DOMAIN = 'yabe-webfont';
    /**
     * @var array
     */
    public const EDD_STORE = ['store_url' => 'https://rosua.org', 'item_id' => 18, 'author' => 'idrosua'];
    /**
     * @var string
     */
    public const REST_NAMESPACE = 'yabe-webfont/v1';
    /**
     * @var string
     */
    public const HOSTED_WAKUFONT = 'https://wakufont-hosted.rosua.org';
    /**
     * @var string
     */
    public const PLUGIN_URI = 'https://webfont.yabe.land';
}
/**
 * Plugin constants.
 *
 * @since 2.0.0
 */
\class_alias('_YabeWebfont\\YABE_WEBFONT', 'YABE_WEBFONT', \false);
