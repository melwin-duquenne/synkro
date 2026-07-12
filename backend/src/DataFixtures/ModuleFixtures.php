<?php

namespace App\DataFixtures;

use App\Entity\Module;
use App\Service\DemoDataSeeder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ModuleFixtures extends Fixture
{
    // Source canonique : App\Service\DemoDataSeeder::MODULES (réutilisée aussi par
    // le seeder de démo prod-safe, voir app:demo:seed).
    public const MODULES = DemoDataSeeder::MODULES;

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
