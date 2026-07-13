<?php

namespace App\Command;

use App\Service\DemoDataSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Peuple (ou retire) le jeu de données de démonstration (soutenance) sur un
 * environnement déployé (staging/prod), sans purger ni toucher aux données
 * existantes : l'opération est additive et idempotente.
 *
 * Utilisable en APP_ENV=prod : ne dépend que de l'ORM et du hasher (pas du
 * bundle de fixtures, absent en prod — voir App\Service\DemoDataSeeder).
 */
#[AsCommand(
    name: 'app:demo:seed',
    description: 'Peuple la base avec le jeu de données de démo (soutenance), de façon additive et idempotente. --remove pour le retirer.'
)]
class DemoSeedCommand extends Command
{
    public function __construct(private readonly DemoDataSeeder $seeder)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Retire uniquement les données de démo au lieu de les créer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('remove')) {
            return $this->executeRemove($io);
        }

        return $this->executeSeed($io);
    }

    private function executeSeed(SymfonyStyle $io): int
    {
        $io->title('Peuplement de la démo Synkro');

        $result = $this->seeder->seed();

        $io->section('Résumé');
        $io->writeln(sprintf('Statut : <info>%s</info>', $result['status']));

        $rows = [];
        foreach ($result['created'] as $label => $count) {
            $rows[] = [$label, $count];
        }
        $io->table(['Élément', 'Créés'], $rows);

        if ($result['status'] === 'déjà présent') {
            $io->note($result['skipped']['raison'] ?? 'Le jeu de démo est déjà présent, aucune donnée créée.');
        } else {
            $io->success('Jeu de données de démo créé.');
        }

        $io->section('Comptes de démonstration');
        $io->table(
            ['Rôle', 'E-mail', 'Mot de passe'],
            [
                ['Admin', 'admin@demo.synkro.ovh', DemoDataSeeder::PASSWORD],
                ['Utilisateur', 'user@demo.synkro.ovh', DemoDataSeeder::PASSWORD],
                ['Invité', 'guest@demo.synkro.ovh', DemoDataSeeder::PASSWORD],
            ]
        );

        return Command::SUCCESS;
    }

    private function executeRemove(SymfonyStyle $io): int
    {
        $io->title('Suppression de la démo Synkro');

        $result = $this->seeder->remove();

        $io->section('Résumé');
        $io->writeln(sprintf('Statut : <info>%s</info>', $result['status']));

        $rows = [];
        foreach ($result['removed'] as $label => $count) {
            $rows[] = [$label, $count];
        }
        $io->table(['Élément', 'Supprimés'], $rows);

        $io->success('Données de démo retirées (les Modules et RoomTemplates partagés sont conservés).');

        return Command::SUCCESS;
    }
}
