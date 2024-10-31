<?php

declare (strict_types=1);
namespace Yabe\Webfont\Migrations;

use _YabeWebfont\Rosua\Migrations\AbstractMigration;
use wpdb;
/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221121114557 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }
    public function up() : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /** @var wpdb $wpdb */
        global $wpdb;
        $sql[] = "CREATE TABLE `{$wpdb->prefix}yabe_webfont_fonts` (\n            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n            `type` VARCHAR(50) NOT NULL DEFAULT 'custom',\n            `status` INT(1) NOT NULL DEFAULT 0,\n            `title` VARCHAR(255) NOT NULL,\n            `slug` VARCHAR(255) NOT NULL,\n            `family` VARCHAR(255),\n            `metadata` TEXT,\n            `font_faces` TEXT,\n            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            `deleted_at` DATETIME DEFAULT NULL,\n            PRIMARY KEY (`id`)\n        ) {$this->collation()};";
        // $sql[] = "CREATE TRIGGER lastUpdateTrigger BEFORE
        // UPDATE ON `{$wpdb->prefix}yabe_webfont_fonts` FOR EACH ROW
        // BEGIN IF (
        //         NEW.title <> OLD.title
        //         || NEW.family <> OLD.family
        //         || NEW.metadata <> OLD.metadata
        //         || NEW.font_faces <> OLD.font_faces
        //     ) THEN
        //     SET
        //         NEW.updated_at = CURRENT_TIMESTAMP();
        // END IF;
        // END;";
        \dbDelta($sql);
    }
    public function down() : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        /** @var wpdb $wpdb */
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS `{$wpdb->prefix}yabe_webfont_fonts`";
        $wpdb->query($sql);
    }
}
