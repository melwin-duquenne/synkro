<?php

namespace App\Dto;

use App\Entity\UserEntreprise;

final class UserEntrepriseOutput
{
    public int $id;
    public string $name;
    public string $role;
    public string $slug;

    public static function fromMembership(UserEntreprise $membership): self
    {
        $output = new self();
        $output->id = $membership->getEntreprise()->getId();
        $output->name = $membership->getEntreprise()->getName();
        $output->role = $membership->getRole();
        $output->slug = $membership->getEntreprise()->getSlug() ?? '';
        return $output;
    }
}
