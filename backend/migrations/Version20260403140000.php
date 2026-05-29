<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make entreprise.domain nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entreprise ALTER COLUMN domain DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE entreprise SET domain = \'\' WHERE domain IS NULL');
        $this->addSql('ALTER TABLE entreprise ALTER COLUMN domain SET NOT NULL');
    }
}
