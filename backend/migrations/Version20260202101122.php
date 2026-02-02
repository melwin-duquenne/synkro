<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202101122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD reset_password_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD reset_password_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD delete_account_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD delete_account_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP reset_password_token');
        $this->addSql('ALTER TABLE "user" DROP reset_password_expires_at');
        $this->addSql('ALTER TABLE "user" DROP delete_account_token');
        $this->addSql('ALTER TABLE "user" DROP delete_account_expires_at');
    }
}
