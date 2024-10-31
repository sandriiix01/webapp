<?php

namespace _YabeWebfont;

use _YabeWebfont\Symplify\EasyCodingStandard\Config\ECSConfig;
use _YabeWebfont\Symplify\EasyCodingStandard\ValueObject\Set\SetList;
return static function (ECSConfig $ecsConfig) : void {
    $ecsConfig->paths([__DIR__ . '/plugin.php', __DIR__ . '/src']);
    $ecsConfig->sets([SetList::CLEAN_CODE, SetList::COMMON, SetList::PSR_12]);
};
