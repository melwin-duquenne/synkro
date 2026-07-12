<?php

namespace App\DataFixtures;

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
use App\Entity\User;
use App\Entity\UserRoomPermission;
use App\Entity\Whiteboard;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de démonstration (soutenance) : deux entreprises,
 * plusieurs utilisateurs, équipes, rooms peuplées selon leurs modules,
 * permissions (RBAC) et une invitation en attente.
 *
 * Mot de passe commun à tous les comptes : Demo1234!
 */
class DemoFixtures extends Fixture implements DependentFixtureInterface
{
    private const PASSWORD = 'Demo1234!';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // =========================================================
        // Entreprise A — Nexora (tenant principal de la démo)
        // =========================================================
        $nexora = new Entreprise();
        $nexora->setName('Nexora');
        $nexora->setSlug('nexora');
        $nexora->setDomain('demo.synkro.ovh');
        $manager->persist($nexora);

        $produit = new Team();
        $produit->setName('Produit');
        $produit->setEntreprise($nexora);
        $manager->persist($produit);

        $marketing = new Team();
        $marketing->setName('Marketing');
        $marketing->setEntreprise($nexora);
        $manager->persist($marketing);

        $admin = $this->createUser($manager, 'admin@demo.synkro.ovh', 'Admin Démo', 'Admin', 'Démo', User::ROLE_ADMIN);
        $camille = $this->createUser($manager, 'user@demo.synkro.ovh', 'Camille Laurent', 'Camille', 'Laurent', User::ROLE_USER);
        $theo = $this->createUser($manager, 'guest@demo.synkro.ovh', 'Théo Invité', 'Théo', 'Invité', User::ROLE_USER);
        $sophie = $this->createUser($manager, 'sophie.martin@demo.synkro.ovh', 'Sophie Martin', 'Sophie', 'Martin', User::ROLE_USER);
        $lucas = $this->createUser($manager, 'lucas.bernard@demo.synkro.ovh', 'Lucas Bernard', 'Lucas', 'Bernard', User::ROLE_USER);
        $emma = $this->createUser($manager, 'emma.petit@demo.synkro.ovh', 'Emma Petit', 'Emma', 'Petit', User::ROLE_USER);
        $hugo = $this->createUser($manager, 'hugo.moreau@demo.synkro.ovh', 'Hugo Moreau', 'Hugo', 'Moreau', User::ROLE_USER);

        foreach ([$admin, $camille, $theo, $sophie, $lucas, $emma, $hugo] as $u) {
            $manager->persist($u);
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

        $manager->flush();

        $now = new \DateTime();

        // ---------------------------------------------------------
        // Room 1 — Réunion : "Réunion hebdo Produit" (chat + calendrier)
        // ---------------------------------------------------------
        $reunion = $this->createRoom($manager, 'Réunion hebdo Produit', $admin, $nexora, 'Réunion');

        $this->addMessage($manager, $reunion, $camille, "Salut tout le monde, on est prêts pour le point hebdo ?", (clone $now)->modify('-1 day -2 hours'));
        $this->addMessage($manager, $reunion, $lucas, "Oui, j'ai terminé l'intégration du header, je montrerai ça rapidement.", (clone $now)->modify('-1 day -1 hour -55 minutes'));
        $this->addMessage($manager, $reunion, $admin, "Parfait. On commence dans 5 minutes, le lien visio est dans le module Visio de la room.", (clone $now)->modify('-1 day -1 hour -50 minutes'));
        $this->addMessage($manager, $reunion, $emma, "Je serai un peu en retard (2-3 min), je finis une maquette.", (clone $now)->modify('-1 day -1 hour -48 minutes'));
        $this->addMessage($manager, $reunion, $camille, "Pas de souci Emma, on t'attend.", (clone $now)->modify('-1 day -1 hour -47 minutes'));

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
        $manager->persist($pointHebdo);
        $this->addParticipant($manager, $pointHebdo, $admin, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($manager, $pointHebdo, $camille, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($manager, $pointHebdo, $lucas, CalendarEventParticipant::STATUS_PENDING);
        $this->addParticipant($manager, $pointHebdo, $emma, CalendarEventParticipant::STATUS_ACCEPTED);

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
        $manager->persist($revueSprint);
        $this->addParticipant($manager, $revueSprint, $admin, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($manager, $revueSprint, $camille, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($manager, $revueSprint, $lucas, CalendarEventParticipant::STATUS_ACCEPTED);

        // ---------------------------------------------------------
        // Room 2 — Rédaction : "Rédaction - Plan marketing Q3" (document + chat)
        // ---------------------------------------------------------
        $redaction = $this->createRoom($manager, 'Rédaction - Plan marketing Q3', $sophie, $nexora, 'Rédaction');

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
        $manager->persist($doc);
        $redaction->setDocument($doc);

        $this->addMessage($manager, $redaction, $sophie, "J'ai commencé le plan marketing Q3 dans le document, n'hésitez pas à compléter.", (clone $now)->modify('-3 days'));
        $this->addMessage($manager, $redaction, $hugo, "Top, je rajoute la partie visuels réseaux sociaux.", (clone $now)->modify('-3 days +10 minutes'));
        $this->addMessage($manager, $redaction, $sophie, "Merci ! On vise une validation vendredi.", (clone $now)->modify('-3 days +25 minutes'));

        // ---------------------------------------------------------
        // Room 3 — Brainstorm : "Brainstorm - Idées nouvelle fonctionnalité" (whiteboard + chat)
        // ---------------------------------------------------------
        $brainstorm = $this->createRoom($manager, 'Brainstorm - Idées nouvelle fonctionnalité', $camille, $nexora, 'Brainstorm');

        $whiteboard = new Whiteboard();
        $whiteboard->setRoom($brainstorm);
        $whiteboard->setStrokes([]);
        $manager->persist($whiteboard);
        $brainstorm->setWhiteboard($whiteboard);

        $this->addMessage($manager, $brainstorm, $camille, "Session brainstorm sur les prochaines fonctionnalités, allez-y avec le tableau blanc !", (clone $now)->modify('-5 days'));
        $this->addMessage($manager, $brainstorm, $emma, "Je pose quelques idées de widgets pour le dashboard.", (clone $now)->modify('-5 days +5 minutes'));
        $this->addMessage($manager, $brainstorm, $lucas, "On pourrait aussi ajouter des réactions emoji sur les messages du chat.", (clone $now)->modify('-5 days +12 minutes'));
        $this->addMessage($manager, $brainstorm, $camille, "Bonnes idées, je regroupe tout ça dans le tableau blanc.", (clone $now)->modify('-5 days +20 minutes'));

        // ---------------------------------------------------------
        // Room 4 — Projet (PRIVÉE) : "Projet - Refonte site vitrine" (kanban + fichiers + document + chat)
        // Démo RBAC : team Produit = editor, user@ = editor (rôle entreprise), guest@ = viewer (rôle entreprise)
        // ---------------------------------------------------------
        $projet = $this->createRoom($manager, 'Projet - Refonte site vitrine', $admin, $nexora, 'Projet', Room::VISIBILITY_PRIVATE);

        $teamPermission = new TeamRoomPermission();
        $teamPermission->setRoom($projet);
        $teamPermission->setTeam($produit);
        $teamPermission->setRole(TeamRoomPermission::ROLE_EDITOR);
        $manager->persist($teamPermission);

        // Accès explicite pour la démo RBAC (guest@ ne fait pas partie de l'équipe Produit)
        $camilleGrant = new UserRoomPermission();
        $camilleGrant->setRoom($projet);
        $camilleGrant->setUser($camille);
        $manager->persist($camilleGrant);

        $theoGrant = new UserRoomPermission();
        $theoGrant->setRoom($projet);
        $theoGrant->setUser($theo);
        $manager->persist($theoGrant);

        $colTodo = $this->createKanbanColumn($manager, $projet, 'À faire', 'bg-slate-500', 0);
        $colDoing = $this->createKanbanColumn($manager, $projet, 'En cours', 'bg-blue-500', 1);
        $colDone = $this->createKanbanColumn($manager, $projet, 'Terminé', 'bg-green-500', 2);

        $this->createTask($manager, $projet, $colDone, 'Rédiger le cahier des charges', "Cadrage des besoins et périmètre fonctionnel.", $camille, 5, 0);
        $this->createTask($manager, $projet, $colDoing, "Maquettes UI de la page d'accueil", "Wireframes + maquettes haute-fidélité sur Figma.", $emma, 8, 0);
        $this->createTask($manager, $projet, $colDoing, "Intégration du header responsive", "Header sticky avec menu mobile.", $lucas, 3, 1);
        $this->createTask($manager, $projet, $colTodo, "Mettre en place le monitoring", "Uptime + alertes sur le futur site.", $hugo, 2, 0);
        $this->createTask($manager, $projet, $colTodo, "Rédiger les tests d'acceptation", null, null, null, 1);

        $projetDoc = new Document();
        $projetDoc->setRoom($projet);
        $projetDoc->setContentHtml(
            '<h1>Refonte du site vitrine</h1>'
            . '<p>Brief : nouveau site vitrine, design épuré, mise en avant des cas d\'usage clients.</p>'
            . '<p>Livraison visée : fin du sprint 4.</p>'
        );
        $manager->persist($projetDoc);
        $projet->setDocument($projetDoc);

        $this->createFileResource($manager, $projet, $camille, 'cahier-des-charges.pdf', '/demo/projet/cahier-des-charges.pdf', 'application/pdf', 245_760);
        $this->createFileResource($manager, $projet, $emma, 'maquette-accueil.png', '/demo/projet/maquette-accueil.png', 'image/png', 1_887_432);

        $this->addMessage($manager, $projet, $admin, "Room créée pour piloter la refonte du site vitrine. Accès restreint à l'équipe Produit.", (clone $now)->modify('-6 days'));
        $this->addMessage($manager, $projet, $camille, "Cahier des charges déposé dans les fichiers, à valider avant vendredi.", (clone $now)->modify('-4 days'));
        $this->addMessage($manager, $projet, $emma, "Première version des maquettes ajoutée également.", (clone $now)->modify('-2 days'));

        // ---------------------------------------------------------
        // Room 5 — Complet : "Espace équipe Nexora" (un peu de chaque module)
        // ---------------------------------------------------------
        $complet = $this->createRoom($manager, 'Espace équipe Nexora', $admin, $nexora, 'Complet');

        $completDoc = new Document();
        $completDoc->setRoom($complet);
        $completDoc->setContentHtml('<h1>Notes d\'équipe</h1><p>Espace libre pour toute information transverse à partager avec l\'équipe Nexora.</p>');
        $manager->persist($completDoc);
        $complet->setDocument($completDoc);

        $completWhiteboard = new Whiteboard();
        $completWhiteboard->setRoom($complet);
        $completWhiteboard->setStrokes([]);
        $manager->persist($completWhiteboard);
        $complet->setWhiteboard($completWhiteboard);

        $colA = $this->createKanbanColumn($manager, $complet, 'À faire', 'bg-slate-500', 0);
        $colB = $this->createKanbanColumn($manager, $complet, 'En cours', 'bg-blue-500', 1);
        $colC = $this->createKanbanColumn($manager, $complet, 'Terminé', 'bg-green-500', 2);
        $this->createTask($manager, $complet, $colA, 'Organiser le pot de départ de Théo', null, $sophie, 1, 0);
        $this->createTask($manager, $complet, $colB, 'Mettre à jour le trombinoscope', null, $hugo, 1, 0);
        $this->createTask($manager, $complet, $colC, 'Renouveler les licences logicielles', null, $admin, 2, 0);

        $this->createFileResource($manager, $complet, $admin, 'reglement-interieur.pdf', '/demo/complet/reglement-interieur.pdf', 'application/pdf', 98_304);

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
        $manager->persist($cafe);
        $this->addParticipant($manager, $cafe, $admin, CalendarEventParticipant::STATUS_ACCEPTED);
        $this->addParticipant($manager, $cafe, $camille, CalendarEventParticipant::STATUS_PENDING);
        $this->addParticipant($manager, $cafe, $sophie, CalendarEventParticipant::STATUS_ACCEPTED);

        $this->addMessage($manager, $complet, $admin, "Bienvenue dans l'espace d'équipe Nexora, tous les modules sont dispos ici !", (clone $now)->modify('-7 days'));
        $this->addMessage($manager, $complet, $theo, "Merci pour l'accès, tout est très clair.", (clone $now)->modify('-7 days +30 minutes'));

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
        $manager->persist($invitation);

        // =========================================================
        // Entreprise B — Zephyr (démo d'isolation multi-tenant)
        // =========================================================
        $zephyr = new Entreprise();
        $zephyr->setName('Zephyr');
        $zephyr->setSlug('zephyr');
        $zephyr->setDomain('zephyr.demo');
        $manager->persist($zephyr);

        $zephyrTeam = new Team();
        $zephyrTeam->setName('Direction');
        $zephyrTeam->setEntreprise($zephyr);
        $manager->persist($zephyrTeam);

        $nadia = $this->createUser($manager, 'dir@zephyr.demo', 'Nadia Girard', 'Nadia', 'Girard', User::ROLE_USER);
        $victor = $this->createUser($manager, 'dev@zephyr.demo', 'Victor Dubois', 'Victor', 'Dubois', User::ROLE_USER);
        $manager->persist($nadia);
        $manager->persist($victor);

        $nadia->addToEntreprise($zephyr, User::ROLE_OWNER);
        $victor->addToEntreprise($zephyr, User::ROLE_EDITOR);

        $zephyrTeam->addUser($nadia);
        $zephyrTeam->addUser($victor);

        $manager->flush();

        $zephyrRoom = $this->createRoom($manager, 'Kickoff Zephyr', $nadia, $zephyr, 'Réunion');
        $this->addMessage($manager, $zephyrRoom, $nadia, "Bienvenue sur Synkro ! On centralise nos échanges ici.", (clone $now)->modify('-1 day'));
        $this->addMessage($manager, $zephyrRoom, $victor, "Nickel, je regarde les modules disponibles.", (clone $now)->modify('-1 day +5 minutes'));

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ModuleFixtures::class,
            RoomTemplateFixtures::class,
        ];
    }

    private function createUser(
        ObjectManager $manager,
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

    private function findTemplate(ObjectManager $manager, string $name): RoomTemplate
    {
        $template = $manager->getRepository(RoomTemplate::class)->findOneBy(['name' => $name]);
        if (!$template) {
            throw new \RuntimeException(sprintf('RoomTemplate "%s" introuvable (RoomTemplateFixtures doit être chargé avant DemoFixtures).', $name));
        }

        return $template;
    }

    private function createRoom(
        ObjectManager $manager,
        string $name,
        User $creator,
        Entreprise $entreprise,
        string $templateName,
        string $visibility = Room::VISIBILITY_ENTERPRISE
    ): Room {
        $template = $this->findTemplate($manager, $templateName);

        $room = new Room();
        $room->setName($name);
        $room->setCreator($creator);
        $room->setEntreprise($entreprise);
        $room->setTemplate($template);
        $room->setVisibility($visibility);
        $manager->persist($room);

        $moduleCodes = [];
        foreach (RoomTemplateFixtures::TEMPLATES as $templateDefinition) {
            if ($templateDefinition['name'] === $templateName) {
                $moduleCodes = $templateDefinition['modules'];
                break;
            }
        }

        $order = 0;
        foreach ($moduleCodes as $code) {
            /** @var Module $module */
            $module = $this->getReference('module_' . $code, Module::class);
            $moduleRoom = new ModuleRoom();
            $moduleRoom->setRoom($room);
            $moduleRoom->setModule($module);
            $moduleRoom->setDisplayOrder($order++);
            $manager->persist($moduleRoom);
        }

        // Le créateur dispose toujours d'un accès explicite (comme dans CreateRoomUseCase)
        $creatorPermission = new UserRoomPermission();
        $creatorPermission->setRoom($room);
        $creatorPermission->setUser($creator);
        $manager->persist($creatorPermission);

        return $room;
    }

    private function addMessage(ObjectManager $manager, Room $room, User $user, string $content, \DateTimeInterface $createdAt): Message
    {
        $message = new Message();
        $message->setRoom($room);
        $message->setUser($user);
        $message->setContent($content);
        $message->setCreatedAt($createdAt);
        $manager->persist($message);

        return $message;
    }

    private function addParticipant(ObjectManager $manager, CalendarEvent $event, User $user, string $status): CalendarEventParticipant
    {
        $participant = new CalendarEventParticipant();
        $participant->setEvent($event);
        $participant->setUser($user);
        $participant->setStatus($status);
        $manager->persist($participant);
        $event->addParticipant($participant);

        return $participant;
    }

    private function createKanbanColumn(ObjectManager $manager, Room $room, string $name, string $color, int $position): KanbanColumn
    {
        $column = new KanbanColumn();
        $column->setRoom($room);
        $column->setName($name);
        $column->setColor($color);
        $column->setPosition($position);
        $manager->persist($column);

        return $column;
    }

    private function createTask(
        ObjectManager $manager,
        Room $room,
        KanbanColumn $column,
        string $title,
        ?string $description,
        ?User $assignedTo,
        ?int $estimation,
        int $position
    ): Task {
        $task = new Task();
        $task->setRoom($room);
        $task->setColumn($column);
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setAssignedTo($assignedTo);
        $task->setEstimation($estimation);
        $task->setPosition($position);
        $manager->persist($task);

        return $task;
    }

    private function createFileResource(
        ObjectManager $manager,
        Room $room,
        User $user,
        string $fileName,
        string $filePath,
        string $mimeType,
        int $size
    ): FileResource {
        $file = new FileResource();
        $file->setRoom($room);
        $file->setUser($user);
        $file->setFileName($fileName);
        $file->setFilePath($filePath);
        $file->setMimeType($mimeType);
        $file->setSize($size);
        $manager->persist($file);

        return $file;
    }
}
