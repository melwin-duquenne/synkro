<?php

namespace App\Service;

use App\Entity\CalendarEvent;
use App\Entity\CalendarEventParticipant;
use App\Entity\Document;
use App\Entity\Entreprise;
use App\Entity\FileResource;
use App\Entity\Invitation;
use App\Entity\KanbanColumn;
use App\Entity\Message;
use App\Entity\Module;
use App\Entity\ModuleRoom;
use App\Entity\Room;
use App\Entity\RoomTemplate;
use App\Entity\Task;
use App\Entity\Team;
use App\Entity\TeamRoomPermission;
use App\Entity\TemplateModule;
use App\Entity\User;
use App\Entity\UserRoomPermission;
use App\Entity\Whiteboard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Construit/retire le jeu de données de démonstration (soutenance) de façon
 * ADDITIVE (jamais de purge) et IDEMPOTENTE (rejouable sans doublons).
 *
 * Contrairement à App\DataFixtures\DemoFixtures (qui dépend du bundle
 * doctrine/doctrine-fixtures-bundle, require-dev et absent en prod — voir
 * backend/Dockerfile qui supprime src/DataFixtures/), ce service ne dépend
 * que de l'ORM et du hasher de mot de passe : il est utilisable par une
 * commande console classique en environnement de production.
 *
 * Les définitions des modules/templates (MODULES / TEMPLATES) sont la
 * source canonique réutilisée par App\DataFixtures\ModuleFixtures et
 * App\DataFixtures\RoomTemplateFixtures pour éviter toute duplication.
 *
 * Mot de passe commun à tous les comptes de démo : Demo1234!
 */
class DemoDataSeeder
{
    public const PASSWORD = 'Demo1234!';

    public const MODULES = [
        [
            'code' => 'editor',
            'name' => 'Éditeur',
            'description' => 'Éditeur de texte collaboratif en temps réel avec TipTap et Yjs',
        ],
        [
            'code' => 'whiteboard',
            'name' => 'Tableau blanc',
            'description' => 'Espace de dessin collaboratif pour brainstorming et schémas',
        ],
        [
            'code' => 'chat',
            'name' => 'Chat',
            'description' => 'Messagerie instantanée en temps réel',
        ],
        [
            'code' => 'video',
            'name' => 'Visioconférence',
            'description' => 'Appels audio et vidéo via WebRTC',
        ],
        [
            'code' => 'files',
            'name' => 'Fichiers',
            'description' => 'Partage et gestion de fichiers',
        ],
        [
            'code' => 'tasks',
            'name' => 'Tâches',
            'description' => 'Tableau Kanban pour la gestion des tâches',
        ],
        [
            'code' => 'calendar',
            'name' => 'Calendrier',
            'description' => 'Agenda partagé avec réunions, absences et rappels',
        ],
    ];

    public const TEMPLATES = [
        [
            'name' => 'Brainstorm',
            'description' => 'Idéal pour les sessions de brainstorming avec tableau blanc et chat',
            'modules' => ['whiteboard', 'chat'],
        ],
        [
            'name' => 'Rédaction',
            'description' => 'Espace d\'écriture collaborative avec éditeur et chat',
            'modules' => ['editor', 'chat'],
        ],
        [
            'name' => 'Réunion',
            'description' => 'Pour les réunions d\'équipe avec visio, chat et calendrier',
            'modules' => ['video', 'chat', 'calendar'],
        ],
        [
            'name' => 'Projet',
            'description' => 'Gestion de projet complète avec documents, tâches et fichiers',
            'modules' => ['editor', 'tasks', 'files', 'chat'],
        ],
        [
            'name' => 'Complet',
            'description' => 'Accès à tous les modules disponibles',
            'modules' => ['editor', 'whiteboard', 'chat', 'video', 'files', 'tasks', 'calendar'],
        ],
    ];

    /** Domaines d'e-mail réservés à la démo (utilisés pour identifier les comptes à supprimer). */
    private const DEMO_EMAIL_DOMAINS = ['demo.synkro.ovh', 'zephyr.demo'];

    /** Slugs des entreprises de démo. */
    private const DEMO_ENTREPRISE_SLUGS = ['nexora', 'zephyr'];

    /** @var array<string, Module> */
    private array $moduleByCode = [];

    /** @var array<string, RoomTemplate> */
    private array $templateByName = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Construit le jeu de données de démo. N'INSÈRE que ce qui manque :
     * aucune purge, aucune suppression, aucune donnée existante touchée.
     *
     * @return array{status: string, created: array<string, int>, skipped: array<string, int>}
     */
    public function seed(): array
    {
        $created = [
            'modules' => 0,
            'templates' => 0,
            'entreprises' => 0,
            'users' => 0,
            'teams' => 0,
            'rooms' => 0,
            'messages' => 0,
            'calendar_events' => 0,
            'kanban_tasks' => 0,
            'files' => 0,
            'invitations' => 0,
        ];

        // 1. Modules & templates : toujours assurés en premier (prod peut être vide).
        $created['modules'] = $this->ensureModules();
        $created['templates'] = $this->ensureTemplates();
        $this->entityManager->flush();

        // 2. Démo déjà présente ? (idempotence)
        $existingAdmin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@demo.synkro.ovh']);

        if ($existingAdmin !== null) {
            return [
                'status' => 'déjà présent',
                'created' => $created,
                'skipped' => [
                    'raison' => 'un utilisateur admin@demo.synkro.ovh existe déjà, le jeu de démo est considéré comme déjà chargé',
                ],
            ];
        }

        $this->buildDemoData($created);
        $this->entityManager->flush();

        return [
            'status' => 'créé',
            'created' => $created,
            'skipped' => [],
        ];
    }

    /**
     * Supprime UNIQUEMENT la donnée de démo (entreprises Nexora + Zephyr et
     * tout ce qui leur appartient, utilisateurs @demo.synkro.ovh /
     * @zephyr.demo, équipes et invitation de démo). Ne touche jamais aux
     * Modules/RoomTemplates partagés, ni à aucune autre donnée.
     *
     * @return array{status: string, removed: array<string, int>}
     */
    public function remove(): array
    {
        $removed = [
            'entreprises' => 0,
            'users' => 0,
            'teams' => 0,
            'rooms' => 0,
            'invitations' => 0,
        ];

        $conn = $this->entityManager->getConnection();

        $entreprises = $this->entityManager->getRepository(Entreprise::class)
            ->createQueryBuilder('e')
            ->where('e.slug IN (:slugs)')
            ->setParameter('slugs', self::DEMO_ENTREPRISE_SLUGS)
            ->getQuery()
            ->getResult();

        if (empty($entreprises)) {
            return [
                'status' => 'aucune donnée de démo trouvée',
                'removed' => $removed,
            ];
        }

        foreach ($entreprises as $entreprise) {
            /** @var Entreprise $entreprise */
            $entrepriseId = $entreprise->getId();

            $roomIds = array_map(
                static fn (array $row): int => (int) $row['id'],
                $conn->fetchAllAssociative('SELECT id FROM room WHERE entreprise_id = ?', [$entrepriseId])
            );

            foreach ($roomIds as $roomId) {
                // Enfants des events (sécurité : la FK a déjà ON DELETE CASCADE).
                $conn->executeStatement(
                    'DELETE FROM calendar_event_participant WHERE event_id IN (SELECT id FROM calendar_event WHERE room_id = ?)',
                    [$roomId]
                );
                $conn->executeStatement('DELETE FROM calendar_event WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM task WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM kanban_column WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM message WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM file_resource WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM document WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM whiteboard WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM module_room WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM team_room_permission WHERE room_id = ?', [$roomId]);
                $conn->executeStatement('DELETE FROM user_room_permission WHERE room_id = ?', [$roomId]);
            }

            // Filet de sécurité : événements calendrier rattachés à l'entreprise mais sans room.
            $conn->executeStatement(
                'DELETE FROM calendar_event_participant WHERE event_id IN (SELECT id FROM calendar_event WHERE entreprise_id = ?)',
                [$entrepriseId]
            );
            $conn->executeStatement('DELETE FROM calendar_event WHERE entreprise_id = ?', [$entrepriseId]);

            $removed['rooms'] += $conn->executeStatement('DELETE FROM room WHERE entreprise_id = ?', [$entrepriseId]);
            $removed['invitations'] += $conn->executeStatement('DELETE FROM invitation WHERE entreprise_id = ?', [$entrepriseId]);
        }

        // Utilisateurs de démo (identifiés par domaine e-mail) : à supprimer avant les
        // équipes (FK user.team_id) et avant les entreprises (les rooms/créateurs
        // référencent déjà été nettoyés ci-dessus).
        $emailPatterns = array_map(static fn (string $domain): string => '%@' . $domain, self::DEMO_EMAIL_DOMAINS);
        foreach ($emailPatterns as $pattern) {
            $conn->executeStatement('DELETE FROM user_entreprise WHERE user_id IN (SELECT id FROM "user" WHERE email LIKE ?)', [$pattern]);
            $removed['users'] += $conn->executeStatement('DELETE FROM "user" WHERE email LIKE ?', [$pattern]);
        }

        foreach ($entreprises as $entreprise) {
            $entrepriseId = $entreprise->getId();
            $removed['teams'] += $conn->executeStatement('DELETE FROM team WHERE entreprise_id = ?', [$entrepriseId]);
            $conn->executeStatement('DELETE FROM user_entreprise WHERE entreprise_id = ?', [$entrepriseId]);
            $removed['entreprises'] += $conn->executeStatement('DELETE FROM entreprise WHERE id = ?', [$entrepriseId]);
        }

        // Le contexte Doctrine peut avoir des entités demo en cache (identity map) : on
        // vide pour éviter des lectures obsolètes lors d'un prochain seed() dans le même run.
        $this->entityManager->clear();

        return [
            'status' => 'supprimé',
            'removed' => $removed,
        ];
    }

    // =========================================================
    // Modules / Templates — assurés, jamais dupliqués
    // =========================================================

    private function ensureModules(): int
    {
        $repo = $this->entityManager->getRepository(Module::class);
        $createdCount = 0;

        foreach (self::MODULES as $moduleData) {
            $module = $repo->findOneBy(['code' => $moduleData['code']]);
            if ($module === null) {
                $module = new Module();
                $module->setCode($moduleData['code']);
                $module->setName($moduleData['name']);
                $module->setDescription($moduleData['description']);
                $this->entityManager->persist($module);
                $createdCount++;
            }
            $this->moduleByCode[$moduleData['code']] = $module;
        }

        return $createdCount;
    }

    private function ensureTemplates(): int
    {
        $repo = $this->entityManager->getRepository(RoomTemplate::class);
        $createdCount = 0;

        foreach (self::TEMPLATES as $templateData) {
            $template = $repo->findOneBy(['name' => $templateData['name']]);
            if ($template === null) {
                $template = new RoomTemplate();
                $template->setName($templateData['name']);
                $template->setDescription($templateData['description']);
                $template->setIsDefault(true);
                $this->entityManager->persist($template);

                foreach ($templateData['modules'] as $moduleCode) {
                    $templateModule = new TemplateModule();
                    $templateModule->setTemplate($template);
                    $templateModule->setModule($this->moduleByCode[$moduleCode]);
                    $this->entityManager->persist($templateModule);
                }

                $createdCount++;
            }
            $this->templateByName[$templateData['name']] = $template;
        }

        return $createdCount;
    }

    // =========================================================
    // Construction de la démo (portage de App\DataFixtures\DemoFixtures)
    // =========================================================

    private function buildDemoData(array &$created): void
    {
        // =========================================================
        // Entreprise A — Nexora (tenant principal de la démo)
        // =========================================================
        $nexora = new Entreprise();
        $nexora->setName('Nexora');
        $nexora->setSlug('nexora');
        $nexora->setDomain('demo.synkro.ovh');
        $this->entityManager->persist($nexora);
        $created['entreprises']++;

        $produit = new Team();
        $produit->setName('Produit');
        $produit->setEntreprise($nexora);
        $this->entityManager->persist($produit);
        $created['teams']++;

        $marketing = new Team();
        $marketing->setName('Marketing');
        $marketing->setEntreprise($nexora);
        $this->entityManager->persist($marketing);
        $created['teams']++;

        $admin = $this->createUser('admin@demo.synkro.ovh', 'Admin Démo', 'Admin', 'Démo', User::ROLE_ADMIN);
        $camille = $this->createUser('user@demo.synkro.ovh', 'Camille Laurent', 'Camille', 'Laurent', User::ROLE_USER);
        $theo = $this->createUser('guest@demo.synkro.ovh', 'Théo Invité', 'Théo', 'Invité', User::ROLE_USER);
        $sophie = $this->createUser('sophie.martin@demo.synkro.ovh', 'Sophie Martin', 'Sophie', 'Martin', User::ROLE_USER);
        $lucas = $this->createUser('lucas.bernard@demo.synkro.ovh', 'Lucas Bernard', 'Lucas', 'Bernard', User::ROLE_USER);
        $emma = $this->createUser('emma.petit@demo.synkro.ovh', 'Emma Petit', 'Emma', 'Petit', User::ROLE_USER);
        $hugo = $this->createUser('hugo.moreau@demo.synkro.ovh', 'Hugo Moreau', 'Hugo', 'Moreau', User::ROLE_USER);

        foreach ([$admin, $camille, $theo, $sophie, $lucas, $emma, $hugo] as $u) {
            $this->entityManager->persist($u);
            $created['users']++;
        }

        // Rôles au sein de l'entreprise Nexora (RBAC "entreprise")
        $admin->addToEntreprise($nexora, User::ROLE_OWNER);
        $camille->addToEntreprise($nexora, User::ROLE_EDITOR);
        $theo->addToEntreprise($nexora, User::ROLE_USER);
        $sophie->addToEntreprise($nexora, User::ROLE_EDITOR);
        $lucas->addToEntreprise($nexora, User::ROLE_EDITOR);
        $emma->addToEntreprise($nexora, User::ROLE_USER);
        $hugo->addToEntreprise($nexora, User::ROLE_USER);

        // Équipes
        $produit->addUser($camille);
        $produit->addUser($lucas);
        $produit->addUser($emma);
        $marketing->addUser($sophie);
        $marketing->addUser($hugo);
        $marketing->addUser($theo);

        $this->entityManager->flush();

        $now = new \DateTime();

        // ---------------------------------------------------------
        // Room 1 — Réunion : "Réunion hebdo Produit" (chat + calendrier)
        // ---------------------------------------------------------
        $reunion = $this->createRoom('Réunion hebdo Produit', $admin, $nexora, 'Réunion');
        $created['rooms']++;

        $this->addMessage($reunion, $camille, "Salut tout le monde, on est prêts pour le point hebdo ?", (clone $now)->modify('-1 day -2 hours'), $created);
        $this->addMessage($reunion, $lucas, "Oui, j'ai terminé l'intégration du header, je montrerai ça rapidement.", (clone $now)->modify('-1 day -1 hour -55 minutes'), $created);
        $this->addMessage($reunion, $admin, "Parfait. On commence dans 5 minutes, le lien visio est dans le module Visio de la room.", (clone $now)->modify('-1 day -1 hour -50 minutes'), $created);
        $this->addMessage($reunion, $emma, "Je serai un peu en retard (2-3 min), je finis une maquette.", (clone $now)->modify('-1 day -1 hour -48 minutes'), $created);
        $this->addMessage($reunion, $camille, "Pas de souci Emma, on t'attend.", (clone $now)->modify('-1 day -1 hour -47 minutes'), $created);

        $pointHebdo = new CalendarEvent();
        $pointHebdo->setRoom($reunion);
        $pointHebdo->setUser($admin);
        $pointHebdo->setEntreprise($nexora);
        $pointHebdo->setTitle('Point hebdo Produit');
        $pointHebdo->setDescription("Avancement sprint en cours, blocages, priorités de la semaine.");
        $pointHebdo->setEventType(CalendarEvent::TYPE_MEETING);
        $pointHebdo->setStartDate((clone $now)->modify('+2 days')->setTime(10, 0));
        $pointHebdo->setEndDate((clone $now)->modify('+2 days')->setTime(10, 30));
        $pointHebdo->setLocation('Visio - Room Réunion hebdo Produit');
        $pointHebdo->setColor('#6366F1');
        $this->entityManager->persist($pointHebdo);
        $created['calendar_events']++;
        $this->addParticipant($pointHebdo, $admin, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($pointHebdo, $camille, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($pointHebdo, $lucas, CalendarEventParticipant::STATUS_PENDING);
        $this->addParticipant($pointHebdo, $emma, CalendarEventParticipant::STATUS_ACCEPTED);

        $revueSprint = new CalendarEvent();
        $revueSprint->setRoom($reunion);
        $revueSprint->setUser($admin);
        $revueSprint->setEntreprise($nexora);
        $revueSprint->setTitle('Revue de sprint');
        $revueSprint->setDescription("Démo des fonctionnalités livrées + rétrospective.");
        $revueSprint->setEventType(CalendarEvent::TYPE_MEETING);
        $revueSprint->setStartDate((clone $now)->modify('+9 days')->setTime(14, 0));
        $revueSprint->setEndDate((clone $now)->modify('+9 days')->setTime(15, 0));
        $revueSprint->setLocation('Visio - Room Réunion hebdo Produit');
        $revueSprint->setColor('#6366F1');
        $this->entityManager->persist($revueSprint);
        $created['calendar_events']++;
        $this->addParticipant($revueSprint, $admin, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($revueSprint, $camille, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($revueSprint, $lucas, CalendarEventParticipant::STATUS_ACCEPTED);

        // ---------------------------------------------------------
        // Room 2 — Rédaction : "Rédaction - Plan marketing Q3" (document + chat)
        // ---------------------------------------------------------
        $redaction = $this->createRoom('Rédaction - Plan marketing Q3', $sophie, $nexora, 'Rédaction');
        $created['rooms']++;

        $doc = new Document();
        $doc->setRoom($redaction);
        $doc->setContentHtml(
            '<h1>Plan marketing Q3</h1>'
            . '<p>Objectif : augmenter la notoriété de Synkro auprès des équipes produit et accélérer '
            . 'l\'acquisition sur le segment PME.</p>'
            . '<h2>1. Axes de communication</h2>'
            . '<ul>'
            . '<li>Mettre en avant la collaboration temps réel (chat, tableau blanc, éditeur partagé).</li>'
            . '<li>Témoignages clients sur le gain de temps en réunion.</li>'
            . '<li>Comparatif face aux outils concurrents.</li>'
            . '</ul>'
            . '<h2>2. Calendrier</h2>'
            . '<p>Semaine 1-2 : refonte de la landing page. Semaine 3-4 : campagne emailing. '
            . 'Semaine 5-6 : webinar de lancement.</p>'
            . '<p><em>Sophie</em> : je propose qu\'on commence par la landing page, j\'ajoute une section ce soir.</p>'
            . '<p><em>Hugo</em> : ok pour moi, je m\'occupe des visuels réseaux sociaux en parallèle.</p>'
        );
        $this->entityManager->persist($doc);
        $redaction->setDocument($doc);

        $this->addMessage($redaction, $sophie, "J'ai commencé le plan marketing Q3 dans le document, n'hésitez pas à compléter.", (clone $now)->modify('-3 days'), $created);
        $this->addMessage($redaction, $hugo, "Top, je rajoute la partie visuels réseaux sociaux.", (clone $now)->modify('-3 days +10 minutes'), $created);
        $this->addMessage($redaction, $sophie, "Merci ! On vise une validation vendredi.", (clone $now)->modify('-3 days +25 minutes'), $created);

        // ---------------------------------------------------------
        // Room 3 — Brainstorm : "Brainstorm - Idées nouvelle fonctionnalité" (whiteboard + chat)
        // ---------------------------------------------------------
        $brainstorm = $this->createRoom('Brainstorm - Idées nouvelle fonctionnalité', $camille, $nexora, 'Brainstorm');
        $created['rooms']++;

        $whiteboard = new Whiteboard();
        $whiteboard->setRoom($brainstorm);
        $whiteboard->setStrokes([]);
        $this->entityManager->persist($whiteboard);
        $brainstorm->setWhiteboard($whiteboard);

        $this->addMessage($brainstorm, $camille, "Session brainstorm sur les prochaines fonctionnalités, allez-y avec le tableau blanc !", (clone $now)->modify('-5 days'), $created);
        $this->addMessage($brainstorm, $emma, "Je pose quelques idées de widgets pour le dashboard.", (clone $now)->modify('-5 days +5 minutes'), $created);
        $this->addMessage($brainstorm, $lucas, "On pourrait aussi ajouter des réactions emoji sur les messages du chat.", (clone $now)->modify('-5 days +12 minutes'), $created);
        $this->addMessage($brainstorm, $camille, "Bonnes idées, je regroupe tout ça dans le tableau blanc.", (clone $now)->modify('-5 days +20 minutes'), $created);

        // ---------------------------------------------------------
        // Room 4 — Projet (PRIVÉE) : "Projet - Refonte site vitrine" (kanban + fichiers + document + chat)
        // Démo RBAC : team Produit = editor, user@ = editor (rôle entreprise), guest@ = viewer (rôle entreprise)
        // ---------------------------------------------------------
        $projet = $this->createRoom('Projet - Refonte site vitrine', $admin, $nexora, 'Projet', Room::VISIBILITY_PRIVATE);
        $created['rooms']++;

        $teamPermission = new TeamRoomPermission();
        $teamPermission->setRoom($projet);
        $teamPermission->setTeam($produit);
        $teamPermission->setRole(TeamRoomPermission::ROLE_EDITOR);
        $this->entityManager->persist($teamPermission);

        // Accès explicite pour la démo RBAC (guest@ ne fait pas partie de l'équipe Produit)
        $camilleGrant = new UserRoomPermission();
        $camilleGrant->setRoom($projet);
        $camilleGrant->setUser($camille);
        $this->entityManager->persist($camilleGrant);

        $theoGrant = new UserRoomPermission();
        $theoGrant->setRoom($projet);
        $theoGrant->setUser($theo);
        $this->entityManager->persist($theoGrant);

        $colTodo = $this->createKanbanColumn($projet, 'À faire', 'bg-slate-500', 0);
        $colDoing = $this->createKanbanColumn($projet, 'En cours', 'bg-blue-500', 1);
        $colDone = $this->createKanbanColumn($projet, 'Terminé', 'bg-green-500', 2);

        $this->createTask($projet, $colDone, 'Rédiger le cahier des charges', "Cadrage des besoins et périmètre fonctionnel.", $camille, 5, 0, $created);
        $this->createTask($projet, $colDoing, "Maquettes UI de la page d'accueil", "Wireframes + maquettes haute-fidélité sur Figma.", $emma, 8, 0, $created);
        $this->createTask($projet, $colDoing, "Intégration du header responsive", "Header sticky avec menu mobile.", $lucas, 3, 1, $created);
        $this->createTask($projet, $colTodo, "Mettre en place le monitoring", "Uptime + alertes sur le futur site.", $hugo, 2, 0, $created);
        $this->createTask($projet, $colTodo, "Rédiger les tests d'acceptation", null, null, null, 1, $created);

        $projetDoc = new Document();
        $projetDoc->setRoom($projet);
        $projetDoc->setContentHtml(
            '<h1>Refonte du site vitrine</h1>'
            . '<p>Brief : nouveau site vitrine, design épuré, mise en avant des cas d\'usage clients.</p>'
            . '<p>Livraison visée : fin du sprint 4.</p>'
        );
        $this->entityManager->persist($projetDoc);
        $projet->setDocument($projetDoc);

        $this->createFileResource($projet, $camille, 'cahier-des-charges.pdf', '/demo/projet/cahier-des-charges.pdf', 'application/pdf', 245_760, $created);
        $this->createFileResource($projet, $emma, 'maquette-accueil.png', '/demo/projet/maquette-accueil.png', 'image/png', 1_887_432, $created);

        $this->addMessage($projet, $admin, "Room créée pour piloter la refonte du site vitrine. Accès restreint à l'équipe Produit.", (clone $now)->modify('-6 days'), $created);
        $this->addMessage($projet, $camille, "Cahier des charges déposé dans les fichiers, à valider avant vendredi.", (clone $now)->modify('-4 days'), $created);
        $this->addMessage($projet, $emma, "Première version des maquettes ajoutée également.", (clone $now)->modify('-2 days'), $created);

        // ---------------------------------------------------------
        // Room 5 — Complet : "Espace équipe Nexora" (un peu de chaque module)
        // ---------------------------------------------------------
        $complet = $this->createRoom('Espace équipe Nexora', $admin, $nexora, 'Complet');
        $created['rooms']++;

        $completDoc = new Document();
        $completDoc->setRoom($complet);
        $completDoc->setContentHtml('<h1>Notes d\'équipe</h1><p>Espace libre pour toute information transverse à partager avec l\'équipe Nexora.</p>');
        $this->entityManager->persist($completDoc);
        $complet->setDocument($completDoc);

        $completWhiteboard = new Whiteboard();
        $completWhiteboard->setRoom($complet);
        $completWhiteboard->setStrokes([]);
        $this->entityManager->persist($completWhiteboard);
        $complet->setWhiteboard($completWhiteboard);

        $colA = $this->createKanbanColumn($complet, 'À faire', 'bg-slate-500', 0);
        $colB = $this->createKanbanColumn($complet, 'En cours', 'bg-blue-500', 1);
        $colC = $this->createKanbanColumn($complet, 'Terminé', 'bg-green-500', 2);
        $this->createTask($complet, $colA, 'Organiser le pot de départ de Théo', null, $sophie, 1, 0, $created);
        $this->createTask($complet, $colB, 'Mettre à jour le trombinoscope', null, $hugo, 1, 0, $created);
        $this->createTask($complet, $colC, 'Renouveler les licences logicielles', null, $admin, 2, 0, $created);

        $this->createFileResource($complet, $admin, 'reglement-interieur.pdf', '/demo/complet/reglement-interieur.pdf', 'application/pdf', 98_304, $created);

        $cafe = new CalendarEvent();
        $cafe->setRoom($complet);
        $cafe->setUser($admin);
        $cafe->setEntreprise($nexora);
        $cafe->setTitle('Café virtuel équipe');
        $cafe->setDescription('Pause informelle, ouvert à tous.');
        $cafe->setEventType(CalendarEvent::TYPE_OTHER);
        $cafe->setStartDate((clone $now)->modify('+5 days')->setTime(16, 0));
        $cafe->setEndDate((clone $now)->modify('+5 days')->setTime(16, 30));
        $cafe->setColor('#F59E0B');
        $this->entityManager->persist($cafe);
        $created['calendar_events']++;
        $this->addParticipant($cafe, $admin, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($cafe, $camille, CalendarEventParticipant::STATUS_PENDING);
        $this->addParticipant($cafe, $sophie, CalendarEventParticipant::STATUS_ACCEPTED);

        $this->addMessage($complet, $admin, "Bienvenue dans l'espace d'équipe Nexora, tous les modules sont dispos ici !", (clone $now)->modify('-7 days'), $created);
        $this->addMessage($complet, $theo, "Merci pour l'accès, tout est très clair.", (clone $now)->modify('-7 days +30 minutes'), $created);

        // ---------------------------------------------------------
        // Invitation en attente
        // ---------------------------------------------------------
        $invitation = new Invitation();
        $invitation->setEmail('nouveau@exemple.fr');
        $invitation->setToken(bin2hex(random_bytes(16)));
        $invitation->setEntreprise($nexora);
        $invitation->setInvitedBy($admin);
        $invitation->setRole(User::ROLE_USER);
        $invitation->setExpiresAt((clone $now)->modify('+7 days'));
        $this->entityManager->persist($invitation);
        $created['invitations']++;

        // =========================================================
        // Entreprise B — Zephyr (démo d'isolation multi-tenant)
        // =========================================================
        $zephyr = new Entreprise();
        $zephyr->setName('Zephyr');
        $zephyr->setSlug('zephyr');
        $zephyr->setDomain('zephyr.demo');
        $this->entityManager->persist($zephyr);
        $created['entreprises']++;

        $zephyrTeam = new Team();
        $zephyrTeam->setName('Direction');
        $zephyrTeam->setEntreprise($zephyr);
        $this->entityManager->persist($zephyrTeam);
        $created['teams']++;

        $nadia = $this->createUser('dir@zephyr.demo', 'Nadia Girard', 'Nadia', 'Girard', User::ROLE_USER);
        $victor = $this->createUser('dev@zephyr.demo', 'Victor Dubois', 'Victor', 'Dubois', User::ROLE_USER);
        $this->entityManager->persist($nadia);
        $this->entityManager->persist($victor);
        $created['users'] += 2;

        $nadia->addToEntreprise($zephyr, User::ROLE_OWNER);
        $victor->addToEntreprise($zephyr, User::ROLE_EDITOR);

        $zephyrTeam->addUser($nadia);
        $zephyrTeam->addUser($victor);

        $this->entityManager->flush();

        $zephyrRoom = $this->createRoom('Kickoff Zephyr', $nadia, $zephyr, 'Réunion');
        $created['rooms']++;
        $this->addMessage($zephyrRoom, $nadia, "Bienvenue sur Synkro ! On centralise nos échanges ici.", (clone $now)->modify('-1 day'), $created);
        $this->addMessage($zephyrRoom, $victor, "Nickel, je regarde les modules disponibles.", (clone $now)->modify('-1 day +5 minutes'), $created);
    }

    private function createUser(
        string $email,
        string $displayName,
        string $firstName,
        string $lastName,
        string $role
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setDisplayName($displayName);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRole($role);
        $user->setCreatedAt(new \DateTime());
        $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));

        return $user;
    }

    private function findTemplate(string $name): RoomTemplate
    {
        if (isset($this->templateByName[$name])) {
            return $this->templateByName[$name];
        }

        $template = $this->entityManager->getRepository(RoomTemplate::class)->findOneBy(['name' => $name]);
        if (!$template) {
            throw new \RuntimeException(sprintf('RoomTemplate "%s" introuvable (ensureTemplates() aurait dû le créer).', $name));
        }

        return $this->templateByName[$name] = $template;
    }

    private function findModule(string $code): Module
    {
        if (isset($this->moduleByCode[$code])) {
            return $this->moduleByCode[$code];
        }

        $module = $this->entityManager->getRepository(Module::class)->findOneBy(['code' => $code]);
        if (!$module) {
            throw new \RuntimeException(sprintf('Module "%s" introuvable (ensureModules() aurait dû le créer).', $code));
        }

        return $this->moduleByCode[$code] = $module;
    }

    private function createRoom(
        string $name,
        User $creator,
        Entreprise $entreprise,
        string $templateName,
        string $visibility = Room::VISIBILITY_ENTERPRISE
    ): Room {
        $template = $this->findTemplate($templateName);

        $room = new Room();
        $room->setName($name);
        $room->setCreator($creator);
        $room->setEntreprise($entreprise);
        $room->setTemplate($template);
        $room->setVisibility($visibility);
        $this->entityManager->persist($room);

        $moduleCodes = [];
        foreach (self::TEMPLATES as $templateDefinition) {
            if ($templateDefinition['name'] === $templateName) {
                $moduleCodes = $templateDefinition['modules'];
                break;
            }
        }

        $order = 0;
        foreach ($moduleCodes as $code) {
            $module = $this->findModule($code);
            $moduleRoom = new ModuleRoom();
            $moduleRoom->setRoom($room);
            $moduleRoom->setModule($module);
            $moduleRoom->setDisplayOrder($order++);
            $this->entityManager->persist($moduleRoom);
        }

        // Le créateur dispose toujours d'un accès explicite (comme dans CreateRoomUseCase)
        $creatorPermission = new UserRoomPermission();
        $creatorPermission->setRoom($room);
        $creatorPermission->setUser($creator);
        $this->entityManager->persist($creatorPermission);

        return $room;
    }

    private function addMessage(Room $room, User $user, string $content, \DateTimeInterface $createdAt, array &$created): Message
    {
        $message = new Message();
        $message->setRoom($room);
        $message->setUser($user);
        $message->setContent($content);
        $message->setCreatedAt($createdAt);
        $this->entityManager->persist($message);
        $created['messages']++;

        return $message;
    }

    private function addParticipant(CalendarEvent $event, User $user, string $status): CalendarEventParticipant
    {
        $participant = new CalendarEventParticipant();
        $participant->setEvent($event);
        $participant->setUser($user);
        $participant->setStatus($status);
        $this->entityManager->persist($participant);
        $event->addParticipant($participant);

        return $participant;
    }

    private function createKanbanColumn(Room $room, string $name, string $color, int $position): KanbanColumn
    {
        $column = new KanbanColumn();
        $column->setRoom($room);
        $column->setName($name);
        $column->setColor($color);
        $column->setPosition($position);
        $this->entityManager->persist($column);

        return $column;
    }

    private function createTask(
        Room $room,
        KanbanColumn $column,
        string $title,
        ?string $description,
        ?User $assignedTo,
        ?int $estimation,
        int $position,
        array &$created
    ): Task {
        $task = new Task();
        $task->setRoom($room);
        $task->setColumn($column);
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setAssignedTo($assignedTo);
        $task->setEstimation($estimation);
        $task->setPosition($position);
        $this->entityManager->persist($task);
        $created['kanban_tasks']++;

        return $task;
    }

    private function createFileResource(
        Room $room,
        User $user,
        string $fileName,
        string $filePath,
        string $mimeType,
        int $size,
        array &$created
    ): FileResource {
        $file = new FileResource();
        $file->setRoom($room);
        $file->setUser($user);
        $file->setFileName($fileName);
        $file->setFilePath($filePath);
        $file->setMimeType($mimeType);
        $file->setSize($size);
        $this->entityManager->persist($file);
        $created['files']++;

        return $file;
    }
}
