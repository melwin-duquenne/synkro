<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202092516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD avatar_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER INDEX uniq_4e5d972654177093 RENAME TO UNIQ_16AD83054177093');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP avatar_path');
        $this->addSql('ALTER INDEX uniq_16ad83054177093 RENAME TO uniq_4e5d972654177093');
    }
}
