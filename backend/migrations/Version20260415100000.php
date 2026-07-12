<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add slug column to entreprise table and generate slugs for existing records';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne nullable
        $this->addSql('ALTER TABLE entreprise ADD COLUMN slug VARCHAR(255) NULL');

        // Générer les slugs pour les données existantes
        $this->addSql("
            DO \$\$
            DECLARE
                rec RECORD;
                base_slug TEXT;
                candidate TEXT;
                counter INT;
                translated TEXT;
            BEGIN
                FOR rec IN SELECT id, name FROM entreprise ORDER BY id LOOP
                    -- Translitération basique des accents + lowercase
                    translated := lower(
                        translate(
                            rec.name,
                            'àáâãäåèéêëìíîïòóôõöùúûüýÿçñÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇÑ',
                            'aaaaaaeeeeiiiioooooouuuuyyçnaaaaaaaaaaaaaaaaaaaaaaaaaacn'
                        )
                    );
                    -- Remplacer les caractères non alphanumériques par des tirets
                    base_slug := regexp_replace(translated, '[^a-z0-9]+', '-', 'g');
                    -- Supprimer les tirets en début/fin
                    base_slug := trim(both '-' from base_slug);

                    IF base_slug = '' THEN
                        base_slug := 'entreprise';
                    END IF;

                    candidate := base_slug;
                    counter := 2;

                    WHILE EXISTS (
                        SELECT 1 FROM entreprise WHERE slug = candidate AND id != rec.id
                    ) LOOP
                        candidate := base_slug || '-' || counter;
                        counter := counter + 1;
                    END LOOP;

                    UPDATE entreprise SET slug = candidate WHERE id = rec.id;
                END LOOP;
            END \$\$;
        ");

        // Appliquer la contrainte NOT NULL et l'index unique
        $this->addSql('ALTER TABLE entreprise ALTER COLUMN slug SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_entreprise_slug ON entreprise (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_entreprise_slug');
        $this->addSql('ALTER TABLE entreprise DROP COLUMN slug');
    }
}
