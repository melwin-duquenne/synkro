<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416300000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI fields to entreprise table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entreprise ADD ai_enabled BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE entreprise ADD ai_provider VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE entreprise ADD ai_api_key TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entreprise DROP COLUMN ai_enabled');
        $this->addSql('ALTER TABLE entreprise DROP COLUMN ai_provider');
        $this->addSql('ALTER TABLE entreprise DROP COLUMN ai_api_key');
    }
}
