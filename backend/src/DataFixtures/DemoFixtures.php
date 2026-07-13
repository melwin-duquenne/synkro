<?php

namespace App\DataFixtures;

use App\Service\DemoDataSeeder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Jeu de données de démonstration (soutenance) : deux entreprises,
 * plusieurs utilisateurs, équipes, rooms peuplées selon leurs modules,
 * permissions (RBAC) et une invitation en attente.
 *
 * La construction du jeu de données est déléguée à App\Service\DemoDataSeeder,
 * qui est aussi utilisable en production (via la commande app:demo:seed) car il
 * ne dépend pas du bundle de fixtures. Le framework de fixtures purge déjà la
 * base avant load(), donc seed() y agit toujours en "création propre".
 *
 * Mot de passe commun à tous les comptes : Demo1234!
 */
class DemoFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly DemoDataSeeder $seeder)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->seeder->seed();
    }

    public function getDependencies(): array
    {
        return [
            ModuleFixtures::class,
            RoomTemplateFixtures::class,
        ];
    }
}
