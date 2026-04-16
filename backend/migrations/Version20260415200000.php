<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove active entreprise_id from user table (enterprise context now comes from URL slug)';
    }

    public function up(Schema $schema): void
    {
        // Supprimer la FK et la colonne entreprise_id de la table user
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT IF EXISTS fk_8d93d649a4aeafea');
        $this->addSql('DROP INDEX IF EXISTS idx_8d93d649a4aeafea');
        $this->addSql('ALTER TABLE "user" DROP COLUMN IF EXISTS entreprise_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD COLUMN entreprise_id INT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d649a4aeafea FOREIGN KEY (entreprise_id) REFERENCES entreprise(id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_8d93d649a4aeafea ON "user" (entreprise_id)');
    }
}
