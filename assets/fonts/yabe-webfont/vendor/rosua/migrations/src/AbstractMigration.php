<?php

declare (strict_types=1);
namespace _YabeWebfont\Rosua\Migrations;

use _YabeWebfont\Rosua\Migrations\Exception\AbortMigration;
use _YabeWebfont\Rosua\Migrations\Exception\SkipMigration;
use wpdb;
use function sprintf;
/**
 * The AbstractMigration class is for end users to extend from when creating migrations. Extend this class
 * and implement the required up() and down() methods.
 */
abstract class AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }
    /**
     * @throws AbortMigration
     */
    public function abortIf(bool $condition, string $message = 'Unknown Reason') : void
    {
        if ($condition) {
            throw new AbortMigration($message);
        }
    }
    /**
     * @throws SkipMigration
     */
    public function skipIf(bool $condition, string $message = 'Unknown Reason') : void
    {
        if ($condition) {
            throw new SkipMigration($message);
        }
    }
    /**
     * @throws MigrationException
     */
    public function preUp() : void
    {
    }
    /**
     * @throws MigrationException
     */
    public function postUp() : void
    {
    }
    /**
     * @throws MigrationException
     */
    public function preDown() : void
    {
    }
    /**
     * @throws MigrationException
     */
    public function postDown() : void
    {
    }
    /**
     * @throws MigrationException
     */
    public abstract function up() : void;
    /**
     * @throws MigrationException
     */
    public function down() : void
    {
        $this->abortIf(\true, sprintf('No down() migration implemented for "%s"', static::class));
    }
    protected function collation()
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        if (!$wpdb->has_cap('collation')) {
            return '';
        }
        return $wpdb->get_charset_collate();
    }
}
