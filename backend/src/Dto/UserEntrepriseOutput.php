<?php

namespace App\Dto;

use App\Entity\UserEntreprise;

final class UserEntrepriseOutput
{
    public int $id;
    public string $name;
    public string $role;
    public string $slug;
    public bool $aiEnabled = false;
    public string $aiMode = 'byok';
    public ?string $aiProvider = null;
    public bool $aiApiKeyConfigured = false;
    public int $aiTokensUsed = 0;
    public ?int $aiTokensLimit = null;

    public static function fromMembership(UserEntreprise $membership): self
    {
        $output = new self();
        $entreprise = $membership->getEntreprise();
        $output->id = $entreprise->getId();
        $output->name = $entreprise->getName();
        $output->role = $membership->getRole();
        $output->slug = $entreprise->getSlug() ?? '';
        $output->aiEnabled = $entreprise->isAiEnabled();
        $output->aiMode = $entreprise->getAiMode();
        $output->aiProvider = $entreprise->getAiProvider();
        $output->aiApiKeyConfigured = $entreprise->getAiApiKey() !== null;
        $output->aiTokensUsed = $entreprise->getAiTokensUsed();
        $output->aiTokensLimit = $entreprise->getAiTokensLimit();
        return $output;
    }
}
