<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed idempotent des données de référence (modules + templates de room).
 * Remplace le lancement manuel des DataFixtures : rejouable sans doublon,
 * appliqué automatiquement à tout environnement (prod + staging).
 */
final class Version20260710120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed idempotent des modules et templates de room (donnée de référence)';
    }

    public function up(Schema $schema): void
    {
        // ─── Modules (module.code a un index UNIQUE → ON CONFLICT) ──────────
        $this->addSql(<<<'SQL'
            INSERT INTO module (name, code, description) VALUES
              ('Éditeur', 'editor', 'Éditeur de texte collaboratif en temps réel avec TipTap et Yjs'),
              ('Tableau blanc', 'whiteboard', 'Espace de dessin collaboratif pour brainstorming et schémas'),
              ('Chat', 'chat', 'Messagerie instantanée en temps réel'),
              ('Visioconférence', 'video', 'Appels audio et vidéo via WebRTC'),
              ('Fichiers', 'files', 'Partage et gestion de fichiers'),
              ('Tâches', 'tasks', 'Tableau Kanban pour la gestion des tâches'),
              ('Calendrier', 'calendar', 'Agenda partagé avec réunions, absences et rappels')
            ON CONFLICT (code) DO NOTHING
        SQL);

        // ─── Templates (pas d'unique sur name → WHERE NOT EXISTS par ligne) ──
        $templates = [
            ['Brainstorm', 'Idéal pour les sessions de brainstorming avec tableau blanc et chat'],
            ['Rédaction', "Espace d'écriture collaborative avec éditeur et chat"],
            ['Réunion', "Pour les réunions d'équipe avec visio, chat et calendrier"],
            ['Projet', 'Gestion de projet complète avec documents, tâches et fichiers'],
            ['Complet', 'Accès à tous les modules disponibles'],
        ];
        foreach ($templates as [$name, $desc]) {
            $this->addSql(
                "INSERT INTO room_template (name, description, is_default)
                 SELECT :name, :desc, true
                 WHERE NOT EXISTS (SELECT 1 FROM room_template WHERE name = :name AND is_default = true)",
                ['name' => $name, 'desc' => $desc]
            );
        }

        // ─── Liens template ↔ modules (unique_template_module → ON CONFLICT) ─
        $links = [
            'Brainstorm' => ['whiteboard', 'chat'],
            'Rédaction'  => ['editor', 'chat'],
            'Réunion'    => ['video', 'chat', 'calendar'],
            'Projet'     => ['editor', 'tasks', 'files', 'chat'],
            'Complet'    => ['editor', 'whiteboard', 'chat', 'video', 'files', 'tasks', 'calendar'],
        ];
        foreach ($links as $templateName => $codes) {
            foreach ($codes as $code) {
                $this->addSql(
                    "INSERT INTO template_module (template_id, module_id)
                     SELECT rt.id, m.id
                     FROM room_template rt, module m
                     WHERE rt.name = :tpl AND rt.is_default = true AND m.code = :code
                     ON CONFLICT (template_id, module_id) DO NOTHING",
                    ['tpl' => $templateName, 'code' => $code]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Donnée de référence : non réversible sans risque (des rooms peuvent
        // référencer ces modules/templates). No-op volontaire.
    }
}
