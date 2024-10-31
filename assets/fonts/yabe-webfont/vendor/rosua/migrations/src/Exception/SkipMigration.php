<?php

declare (strict_types=1);
namespace _YabeWebfont\Rosua\Migrations\Exception;

use RuntimeException;
final class SkipMigration extends RuntimeException implements ControlException
{
}
