<?php

namespace App\DataFixtures;

use App\Entity\Module;
use App\Entity\RoomTemplate;
use App\Entity\TemplateModule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RoomTemplateFixtures extends Fixture implements DependentFixtureInterface
{
    public const TEMPLATES = [
        [
            'name' => 'Brainstorm',
            'description' => 'Idéal pour les sessions de brainstorming avec tableau blanc et chat',
            'modules' => ['whiteboard', 'chat']
        ],
        [
            'name' => 'Rédaction',
            'description' => 'Espace d\'écriture collaborative avec éditeur et chat',
            'modules' => ['editor', 'chat']
        ],
        [
            'name' => 'Réunion',
            'description' => 'Pour les réunions d\'équipe avec visio, chat et calendrier',
            'modules' => ['video', 'chat', 'calendar']
        ],
        [
            'name' => 'Projet',
            'description' => 'Gestion de projet complète avec documents, tâches et fichiers',
            'modules' => ['editor', 'tasks', 'files', 'chat']
        ],
        [
            'name' => 'Complet',
            'description' => 'Accès à tous les modules disponibles',
            'modules' => ['editor', 'whiteboard', 'chat', 'video', 'files', 'tasks', 'calendar']
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::TEMPLATES as $templateData) {
            $template = new RoomTemplate();
            $template->setName($templateData['name']);
            $template->setDescription($templateData['description']);
            $template->setIsDefault(true);

            $manager->persist($template);

            foreach ($templateData['modules'] as $moduleCode) {
                /** @var Module $module */
                $module = $this->getReference('module_' . $moduleCode, Module::class);

                $templateModule = new TemplateModule();
                $templateModule->setTemplate($template);
                $templateModule->setModule($module);

                $manager->persist($templateModule);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ModuleFixtures::class,
        ];
    }
}
