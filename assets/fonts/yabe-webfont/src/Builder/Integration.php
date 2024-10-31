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
namespace Yabe\Webfont\Builder;

use ReflectionClass;
use _YabeWebfont\Symfony\Component\Finder\Finder;
use _YabeWebfont\YABE_WEBFONT;
class Integration
{
    /**
     * List of Builders services.
     *
     * @var BuilderInterface[]
     */
    private array $builders = [];
    public function __construct()
    {
        $this->scan_builders();
        $this->register_builders();
    }
    public function scan_builders()
    {
        // Get cached Builders
        $transient_name = 'yabe_webfont_scanned_builders_' . YABE_WEBFONT::VERSION;
        /** @var BuilderInterface[]|false $cached */
        $cached = \get_transient($transient_name);
        if ($cached !== \false) {
            $this->builders = $cached;
            return;
        }
        $finder = new Finder();
        $finder->files()->in(__DIR__)->name('*.php');
        /**
         * Add additional places to scan for Builders integration.
         *
         * @param Finder $finder The Finder instance.
         */
        \do_action('a!yabe/webfont/builder/integration:before_scan', $finder);
        foreach ($finder as $file) {
            $builder_file = $file->getPathname();
            if (!\is_readable($builder_file)) {
                continue;
            }
            require_once $builder_file;
        }
        // Find any Builders integration that extends BuilderInterface class
        $declared_classes = \get_declared_classes();
        foreach ($declared_classes as $declared_class) {
            if (!\class_exists($declared_class)) {
                continue;
            }
            $reflector = new ReflectionClass($declared_class);
            if (!$reflector->isSubclassOf(\Yabe\Webfont\Builder\BuilderInterface::class)) {
                continue;
            }
            // Get Builders integration detail and push to Integration::$builders to be register later
            /** @var BuilderInterface $builder */
            $builder = $reflector->newInstanceWithoutConstructor();
            $this->builders[$builder->get_name()] = ['name' => $builder->get_name(), 'file_path' => $reflector->getFileName(), 'class_name' => $reflector->getName()];
        }
        // Cache the Builders
        \set_transient($transient_name, $this->builders, \DAY_IN_SECONDS);
    }
    /**
     * Register Builders.
     */
    public function register_builders() : void
    {
        /**
         * Filter the Builders before register.
         *
         * @param BuilderInterface[] $builders
         * @return BuilderInterface[]
         */
        /** @var BuilderInterface[] $builders */
        $builders = \apply_filters('f!yabe/webfont/builder/integration:register_builders', $this->builders);
        foreach ($builders as $builder) {
            // Create new instance of Builder class
            /** @var BuilderInterface $builderInstance */
            $builderInstance = new $builder['class_name']();
            $this->builders[$builder['name']]['instance'] = $builderInstance;
        }
    }
}
