<?php

declare (strict_types=1);
namespace _YabeWebfont\Rosua\Migrations;

/**
 * The Configuration class is responsible for defining migration configuration information.
 */
final class Configuration
{
    private string $tableName;
    private string $namespace;
    private string $directory;
    private string $basePath;
    public function __construct(array $cfgs = [])
    {
        $cfgs = \array_merge(['tableName' => 'rosua_migrations', 'namespace' => 'RosuaMigrations', 'directory' => 'migrations', 'basePath' => \dirname(__DIR__)], $cfgs);
        $this->tableName = $cfgs['tableName'];
        $this->namespace = $cfgs['namespace'];
        $this->directory = $cfgs['directory'];
        $this->basePath = $cfgs['basePath'];
    }
    public function setTableName(string $tableName) : self
    {
        $this->tableName = $tableName;
        return $this;
    }
    public function getTableName() : string
    {
        return $this->tableName;
    }
    public function setNamespace(string $namespace) : self
    {
        $this->namespace = $namespace;
        return $this;
    }
    public function getNamespace() : string
    {
        return $this->namespace;
    }
    public function setDirectory(string $directory) : self
    {
        $this->directory = $directory;
        return $this;
    }
    public function getDirectory() : string
    {
        return $this->directory;
    }
    public function setBasePath(string $basePath) : self
    {
        $this->basePath = $basePath;
        return $this;
    }
    public function getBasePath() : string
    {
        return $this->basePath;
    }
}
