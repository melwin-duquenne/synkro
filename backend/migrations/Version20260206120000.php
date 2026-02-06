<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove role column from user_room_permission table (roles are now global on User entity)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_room_permission DROP COLUMN role');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_room_permission ADD role VARCHAR(20) DEFAULT \'viewer\' NOT NULL');
    }
}
