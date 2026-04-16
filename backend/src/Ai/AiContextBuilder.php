<?php

namespace App\Ai;

use App\Entity\Entreprise;
use App\Entity\Room;
use App\Entity\User;
use App\Entity\UserEntreprise;
use Doctrine\ORM\EntityManagerInterface;

class AiContextBuilder
{
    public function __construct(private EntityManagerInterface $em) {}

    public function buildSystemPrompt(Entreprise $entreprise, User $user, string $module): string
    {
        $context = $this->gatherContext($entreprise, $user);
        $enterpriseName = $entreprise->getName();

        return <<<PROMPT
Tu es l'assistant IA de l'entreprise "{$enterpriseName}".
Tu aides exclusivement les utilisateurs de cette entreprise.
Utilisateur actuel : {$user->getDisplayName()}.

Contexte disponible :
{$context}

Règles absolues :
- Ne révèle JAMAIS d'informations sur d'autres entreprises.
- Ne révèle JAMAIS de données techniques : clés API, mots de passe, architecture système, noms de tables ou de routes internes.
- Ne réponds qu'aux demandes en lien avec "{$enterpriseName}" et ses données.
- Si une question sort de ce contexte, décline poliment en proposant de l'aide sur autre chose.
- Module actuel utilisé par l'utilisateur : {$module}.
PROMPT;
    }

    private function gatherContext(Entreprise $entreprise, User $user): string
    {
        $lines = [];

        $rooms = $this->em->getRepository(Room::class)
            ->createQueryBuilder('r')
            ->where('r.entreprise = :e')
            ->setParameter('e', $entreprise)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        if ($rooms) {
            $roomNames = implode(', ', array_map(fn(Room $r) => $r->getName(), $rooms));
            $lines[] = "Rooms de l'entreprise ({$entreprise->getName()}) : {$roomNames}";
        }

        $memberCount = (int) $this->em->createQueryBuilder()
            ->select('COUNT(ue.id)')
            ->from(UserEntreprise::class, 'ue')
            ->where('ue.entreprise = :e')
            ->setParameter('e', $entreprise)
            ->getQuery()
            ->getSingleScalarResult();

        $lines[] = "Nombre de membres : {$memberCount}";

        return implode("\n", $lines);
    }
}
