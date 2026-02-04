<?php

namespace App\Dto\Entreprise;

use App\Entity\Entreprise;

final class EntrepriseOutput
{
    public int $id;
    public string $name;
    public string $domain;
    public string $createdAt;

    public static function fromEntity(Entreprise $entreprise): self
    {
        $output = new self();
        $output->id = $entreprise->getId();
        $output->name = $entreprise->getName();
        $output->domain = $entreprise->getDomain();
        $output->createdAt = $entreprise->getCreatedAt()->format('c');

        return $output;
    }
}
