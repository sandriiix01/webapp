<?php

declare (strict_types=1);
namespace _YabeWebfont\Rosua\Migrations;

use wpdb;
class Migrator
{
    private Configuration $configuration;
    private string $commandNamespace;
    public function __construct(?array $configs = [])
    {
        $this->setConfig($configs);
    }
    public function setConfig(array $configs) : void
    {
        $configs = \array_merge(['tableName' => 'rosua_migrations', 'namespace' => 'RosuaMigrations', 'directory' => 'migrations', 'basePath' => \dirname(__DIR__), 'commandNamespace' => 'migrations'], $configs);
        $this->configuration = new Configuration(['tableName' => $configs['tableName'], 'namespace' => $configs['namespace'], 'directory' => $configs['directory'], 'basePath' => $configs['basePath']]);
        $this->commandNamespace = $configs['commandNamespace'];
    }
    public function boot() : void
    {
        require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
        $this->registerCommands();
    }
    public function registerCommands() : void
    {
        if (!\class_exists('WP_CLI')) {
            return;
        }
        \WP_CLI::add_command($this->commandNamespace, new Command($this));
    }
    public function install() : void
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        $tableName = $wpdb->prefix . $this->configuration->getTableName();
        $find_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($tableName)));
        if ($find_table === $tableName) {
            return;
        }
        $collation = $wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : '';
        $sql = "CREATE TABLE `{$tableName}` (\n            `version` VARCHAR(191) NOT NULL,\n            `executed_at` DATETIME DEFAULT NULL,\n            `execution_time` INT DEFAULT NULL,\n            PRIMARY KEY (`version`)\n        ) {$collation};";
        \dbDelta($sql);
    }
    public function generate(?string $up = null, ?string $down = null)
    {
        $generator = new Generator($this->configuration);
        $generator->generateMigration($up, $down);
    }
    public function list()
    {
        $migrationRepository = new MigrationRepository($this->configuration);
        return $migrationRepository->getMigrationVersions();
    }
    public function execute() : array
    {
        $list = $this->list();
        $executed = [];
        foreach ($list as $version) {
            if ($version['executed']) {
                continue;
            }
            $start_time = \microtime(\true);
            /** @var AbstractMigration $m */
            $m = new $version['version']();
            $m->up();
            $end_time = \microtime(\true);
            $executed[] = ['version' => $version['version'], 'executed_at' => \date('Y-m-d H:i:s'), 'execution_time' => ($end_time - $start_time) * 1000];
        }
        if (empty($executed)) {
            return [];
        }
        /** @var wpdb $wpdb */
        global $wpdb;
        $tableName = $wpdb->prefix . $this->configuration->getTableName();
        $wpdb->query(\sprintf('LOCK TABLES `%s` WRITE', $tableName));
        foreach ($executed as $version) {
            $wpdb->insert($tableName, ['version' => $version['version'], 'executed_at' => $version['executed_at'], 'execution_time' => $version['execution_time']]);
        }
        $wpdb->query('UNLOCK TABLES');
        return $executed;
    }
}
