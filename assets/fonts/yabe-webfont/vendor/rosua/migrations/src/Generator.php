<?php

declare (strict_types=1);
namespace _YabeWebfont\Rosua\Migrations;

use DateTimeImmutable;
use DateTimeZone;
/**
 * The Generator class is responsible for generating a migration class.
 *
 * @internal
 */
class Generator
{
    /**
     * @var string
     */
    public const VERSION_FORMAT = 'YmdHis';
    /**
     * @var string
     */
    private const MIGRATION_TEMPLATE = <<<'TEMPLATE'
<?php

declare(strict_types=1);

namespace <namespace>;

use Rosua\Migrations\AbstractMigration;
use wpdb;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class <className> extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        /** @var wpdb $wpdb */
        global $wpdb;

<up>
    }

    public function down(): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        /** @var wpdb $wpdb */
        global $wpdb;

<down>
    }<override>
}

TEMPLATE;
    public Configuration $configuration;
    private ?string $template = null;
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }
    public function generateMigration(?string $up = null, ?string $down = null) : string
    {
        $namespace = $this->configuration->getNamespace();
        $className = 'Version' . $this->generateVersionNumber();
        $fqcn = $namespace . '\\' . $className;
        $replacements = ['<namespace>' => $namespace, '<className>' => $className, '<up>' => $up !== null ? '        ' . \implode("\n        ", \explode("\n", $up)) : null, '<down>' => $down !== null ? '        ' . \implode("\n        ", \explode("\n", $down)) : null, '<override>' => ''];
        $code = \strtr($this->getTemplate(), $replacements);
        $code = \preg_replace('#^ +$#m', '', $code);
        $dir = \rtrim($this->configuration->getBasePath(), \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . $this->configuration->getDirectory();
        $path = $dir . \DIRECTORY_SEPARATOR . $className . '.php';
        if (!\file_exists($dir)) {
            \wp_mkdir_p($dir);
        }
        \file_put_contents($path, $code);
        return $path;
    }
    private function generateVersionNumber() : string
    {
        $dateTimeImmutable = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        return $dateTimeImmutable->format(self::VERSION_FORMAT);
    }
    private function getTemplate() : string
    {
        if ($this->template === null) {
            $this->template = self::MIGRATION_TEMPLATE;
        }
        return $this->template;
    }
}
