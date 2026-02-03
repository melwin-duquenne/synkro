<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203133258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_resource ADD is_folder BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE file_resource ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file_resource ALTER file_path DROP NOT NULL');
        $this->addSql('ALTER TABLE file_resource ALTER mime_type DROP NOT NULL');
        $this->addSql('ALTER TABLE file_resource ADD CONSTRAINT FK_DC17B8B727ACA70 FOREIGN KEY (parent_id) REFERENCES file_resource (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX idx_file_parent ON file_resource (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_resource DROP CONSTRAINT FK_DC17B8B727ACA70');
        $this->addSql('DROP INDEX idx_file_parent');
        $this->addSql('ALTER TABLE file_resource DROP is_folder');
        $this->addSql('ALTER TABLE file_resource DROP parent_id');
        $this->addSql('ALTER TABLE file_resource ALTER file_path SET NOT NULL');
        $this->addSql('ALTER TABLE file_resource ALTER mime_type SET NOT NULL');
    }
}
