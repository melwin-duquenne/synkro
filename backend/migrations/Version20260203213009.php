<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203213009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE module_room ADD display_order INT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE module_room SET display_order = id WHERE display_order = 0');
        $this->addSql('ALTER TABLE room ALTER layout_type DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE module_room DROP display_order');
        $this->addSql('ALTER TABLE room ALTER layout_type SET DEFAULT \'tabs\'');
    }
}
