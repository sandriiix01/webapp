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
namespace Yabe\Webfont\Core;

/**
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Runtime
{
    public function __construct()
    {
        new \Yabe\Webfont\Core\Frontpage();
    }
}
