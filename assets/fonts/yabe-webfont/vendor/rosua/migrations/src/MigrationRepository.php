<?php

declare (strict_types=1);
namespace _YabeWebfont\Rosua\Migrations;

use ReflectionClass;
use _YabeWebfont\Symfony\Component\Finder\Finder;
/**
 * The Repository class is responsible for storing and retrieving migrations.
 *
 * @internal
 */
class MigrationRepository
{
    public Configuration $configuration;
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }
    public function getMigrationFiles() : array
    {
        $finder = new Finder();
        $dir = \rtrim($this->configuration->getBasePath(), \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . $this->configuration->getDirectory();
        $finder->files()->in($dir)->name('Version*.php')->sortByName();
        $includedFiles = [];
        foreach ($finder as $file) {
            require_once $file->getRealPath();
            $includedFiles[] = $file->getRealPath();
        }
        $classes = $this->loadMigrationClasses($includedFiles, $this->configuration->getNamespace());
        $versions = [];
        foreach ($classes as $class) {
            $versions[] = $class->getName();
        }
        return $versions;
    }
    public function getMigrationsMetadata() : array
    {
        /** @var wpdb $wpdb */
        global $wpdb;
        $tableName = $wpdb->prefix . $this->configuration->getTableName();
        $sql = \sprintf('SELECT * FROM `%s`', $tableName);
        $results = $wpdb->get_results($sql, \ARRAY_A);
        if (!\is_array($results) || empty($results)) {
            return [];
        }
        $namespace = $this->configuration->getNamespace();
        return \array_filter($results, static fn($result) => \strncmp($result['version'], $namespace . '\\', \strlen($namespace) + 1) === 0);
    }
    public function getMigrationVersions() : array
    {
        $files = $this->getMigrationFiles();
        $metadatas = $this->getMigrationsMetadata();
        $versions = [];
        foreach ($files as $file) {
            $versions[$file] = ['version' => $file, 'executed' => \false, 'executed_at' => null, 'execution_time' => null];
        }
        foreach ($metadatas as $metadata) {
            $versions[$metadata['version']] = ['version' => $metadata['version'], 'executed' => \true, 'executed_at' => $metadata['executed_at'], 'execution_time' => $metadata['execution_time']];
        }
        return $versions;
    }
    /**
     * Look up all declared classes and find those classes contained
     * in the given `$files` array.
     *
     * @param string[]    $files     The set of files that were `required`
     * @param string|null $namespace If not null only classes in this namespace will be returned
     *
     * @return ReflectionClass<object>[] the classes in `$files`
     */
    protected function loadMigrationClasses(array $files, ?string $namespace = null) : array
    {
        $classes = [];
        foreach (\get_declared_classes() as $class) {
            $reflectionClass = new ReflectionClass($class);
            if (!\in_array($reflectionClass->getFileName(), $files, \true)) {
                continue;
            }
            if ($namespace !== null && !$this->isReflectionClassInNamespace($reflectionClass, $namespace)) {
                continue;
            }
            $classes[] = $reflectionClass;
        }
        return $classes;
    }
    /**
     * @param ReflectionClass<object> $reflectionClass
     */
    private function isReflectionClassInNamespace(ReflectionClass $reflectionClass, string $namespace) : bool
    {
        return \strncmp($reflectionClass->getName(), $namespace . '\\', \strlen($namespace) + 1) === 0;
    }
}
