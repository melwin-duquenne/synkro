<?php

namespace App\Dto;

use App\Entity\Entreprise;

/**
 * Simple entreprise representation for nested relations
 */
final class EntrepriseSimpleOutput
{
    public int $id;
    public string $name;
    public ?string $slug = null;

    public static function fromEntity(?Entreprise $entreprise): ?self
    {
        if (!$entreprise) {
            return null;
        }

        $output = new self();
        $output->id = $entreprise->getId();
        $output->name = $entreprise->getName();
        $output->slug = $entreprise->getSlug();

        return $output;
    }
}
