<?php

namespace App\DataFixtures;

use App\Entity\Module;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ModuleFixtures extends Fixture
{
    public const MODULES = [
        [
            'code' => 'editor',
            'name' => 'Éditeur',
            'description' => 'Éditeur de texte collaboratif en temps réel avec TipTap et Yjs'
        ],
        [
            'code' => 'whiteboard',
            'name' => 'Tableau blanc',
            'description' => 'Espace de dessin collaboratif pour brainstorming et schémas'
        ],
        [
            'code' => 'chat',
            'name' => 'Chat',
            'description' => 'Messagerie instantanée en temps réel'
        ],
        [
            'code' => 'video',
            'name' => 'Visioconférence',
            'description' => 'Appels audio et vidéo via WebRTC'
        ],
        [
            'code' => 'files',
            'name' => 'Fichiers',
            'description' => 'Partage et gestion de fichiers'
        ],
        [
            'code' => 'tasks',
            'name' => 'Tâches',
            'description' => 'Tableau Kanban pour la gestion des tâches'
        ],
        [
            'code' => 'calendar',
            'name' => 'Calendrier',
            'description' => 'Agenda partagé avec réunions, absences et rappels'
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::MODULES as $moduleData) {
            $module = new Module();
            $module->setCode($moduleData['code']);
            $module->setName($moduleData['name']);
            $module->setDescription($moduleData['description']);

            $manager->persist($module);
            $this->addReference('module_' . $moduleData['code'], $module);
        }

        $manager->flush();
    }
}
