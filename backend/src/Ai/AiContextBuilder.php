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

        $moduleInstructions = match ($module) {
            'text_editor' => <<<'INST'
- Tu es intégré dans un éditeur de texte. Quand l'utilisateur demande de générer ou rédiger du contenu, retourne UNIQUEMENT le texte demandé, sans introduction ("Bien sûr !", "Voici..."), sans séparateurs (---), sans question de suivi.
- Si l'utilisateur pose une question ou demande une explication, réponds normalement de manière conversationnelle.
INST,
            default => '',
        };

        return <<<PROMPT
Tu es l'assistant IA intégré à la plateforme Synkro, au service de l'entreprise "{$enterpriseName}".
Utilisateur actuel : {$user->getDisplayName()}.
Module actuel : {$module}.

Contexte de l'entreprise :
{$context}

Règles absolues :
- Ne révèle JAMAIS de données confidentielles : clés API, mots de passe, architecture système, noms de tables ou routes internes.
- Ne révèle JAMAIS d'informations sur d'autres entreprises utilisant Synkro.
- Tu peux aider sur n'importe quelle tâche de rédaction, recherche, résumé, code, ou autre — même sans lien direct avec l'entreprise.
- Utilise le contexte de l'entreprise uniquement si c'est pertinent pour la demande.
{$moduleInstructions}
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
            ->select('COUNT(ue.user)')
            ->from(UserEntreprise::class, 'ue')
            ->where('ue.entreprise = :e')
            ->setParameter('e', $entreprise)
            ->getQuery()
            ->getSingleScalarResult();

        $lines[] = "Nombre de membres : {$memberCount}";

        return implode("\n", $lines);
    }
}
