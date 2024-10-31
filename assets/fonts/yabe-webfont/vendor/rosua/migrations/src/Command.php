<?php

declare (strict_types=1);
namespace _YabeWebfont\Rosua\Migrations;

use WP_CLI_Command;
use function WP_CLI\Utils\format_items;
/**
 * Rosua Migration Command
 */
class Command extends WP_CLI_Command
{
    private Migrator $migrator;
    public function __construct($migrator)
    {
        $this->migrator = $migrator;
    }
    /**
     * @var string
     */
    private const TABLE_UP_TEMPLATE = <<<'TEMPLATE'
$sql = "CREATE TABLE `{$wpdb->prefix}<tableName>` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`id`)
) {$this->collation()}";

dbDelta($sql);
TEMPLATE;
    /**
     * @var string
     */
    private const TABLE_DOWN_TEMPLATE = <<<'TEMPLATE'
$sql = "DROP TABLE IF EXISTS `{$wpdb->prefix}<tableName>`";

$wpdb->query($sql);
TEMPLATE;
    private ?string $tableUpTemplate = null;
    private ?string $tableDownTemplate = null;
    /**
     * Generate a new migration
     *
     * ## OPTIONS
     *
     * [--table-name=<table-name>]
     * : The table name of the migration. If not provided, a blank migration will be generated.
     *
     * ## EXAMPLES
     *
     *    wp rosua-migrations generate
     *    wp rosua-migrations generate --table-name=queue
     *
     * @when wp_loaded
     */
    public function generate($args, $assoc_args)
    {
        $tableName = $assoc_args['table-name'] ?? null;
        $replacements = ['<tableName>' => $tableName];
        $up = null;
        $down = null;
        if ($tableName) {
            $up = \strtr($this->getTableUpTemplate(), $replacements);
            $up = \preg_replace('#^ +$#m', '', $up);
            $down = \strtr($this->getTableDownTemplate(), $replacements);
            $down = \preg_replace('#^ +$#m', '', $down);
        }
        $this->migrator->generate($up, $down);
    }
    public function list($args, $assoc_args)
    {
        $migrations = $this->migrator->list();
        format_items('table', $migrations, ['version', 'executed_at', 'execution_time', 'executed']);
    }
    private function getTableUpTemplate() : string
    {
        if ($this->tableUpTemplate === null) {
            $this->tableUpTemplate = self::TABLE_UP_TEMPLATE;
        }
        return $this->tableUpTemplate;
    }
    private function getTableDownTemplate() : string
    {
        if ($this->tableDownTemplate === null) {
            $this->tableDownTemplate = self::TABLE_DOWN_TEMPLATE;
        }
        return $this->tableDownTemplate;
    }
}
