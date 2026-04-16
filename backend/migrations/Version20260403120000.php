<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_entreprise table for multi-enterprise membership with per-enterprise roles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_entreprise (
            user_id INT NOT NULL,
            entreprise_id INT NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT \'user\',
            PRIMARY KEY (user_id, entreprise_id)
        )');

        $this->addSql('CREATE INDEX IDX_USER_ENTREPRISE_USER ON user_entreprise (user_id)');
        $this->addSql('CREATE INDEX IDX_USER_ENTREPRISE_ENTREPRISE ON user_entreprise (entreprise_id)');

        $this->addSql('ALTER TABLE user_entreprise ADD CONSTRAINT FK_UE_USER
            FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE user_entreprise ADD CONSTRAINT FK_UE_ENTREPRISE
            FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add role field to invitation table
        $this->addSql('ALTER TABLE invitation ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT \'user\'');

        // Migrate existing user-entreprise relationships
        $this->addSql('INSERT INTO user_entreprise (user_id, entreprise_id, role)
            SELECT id, entreprise_id, role FROM "user" WHERE entreprise_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_entreprise DROP CONSTRAINT FK_UE_USER');
        $this->addSql('ALTER TABLE user_entreprise DROP CONSTRAINT FK_UE_ENTREPRISE');
        $this->addSql('DROP TABLE user_entreprise');
        $this->addSql('ALTER TABLE invitation DROP COLUMN role');
    }
}
