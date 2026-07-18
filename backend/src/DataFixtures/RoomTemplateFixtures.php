<?php

namespace App\DataFixtures;

use App\Entity\Module;
use App\Entity\RoomTemplate;
use App\Entity\TemplateModule;
use App\Service\DemoDataSeeder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RoomTemplateFixtures extends Fixture implements DependentFixtureInterface
{
    // Source canonique : App\Service\DemoDataSeeder::TEMPLATES (réutilisée aussi par
    // le seeder de démo prod-safe, voir app:demo:seed).
    public const TEMPLATES = DemoDataSeeder::TEMPLATES;

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
