<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unique constraint on entreprise.domain to allow multiple entreprises with same email domain';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_d19fa60a7a91e0b');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_d19fa60a7a91e0b ON entreprise (domain)');
    }
}
