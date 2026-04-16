<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416400000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AI platform mode and token quota fields to entreprise';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE entreprise ADD ai_mode VARCHAR(20) NOT NULL DEFAULT 'byok'");
        $this->addSql('ALTER TABLE entreprise ADD ai_tokens_used INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE entreprise ADD ai_tokens_limit INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE entreprise ADD ai_tokens_reset_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entreprise DROP COLUMN ai_mode');
        $this->addSql('ALTER TABLE entreprise DROP COLUMN ai_tokens_used');
        $this->addSql('ALTER TABLE entreprise DROP COLUMN ai_tokens_limit');
        $this->addSql('ALTER TABLE entreprise DROP COLUMN ai_tokens_reset_at');
    }
}
