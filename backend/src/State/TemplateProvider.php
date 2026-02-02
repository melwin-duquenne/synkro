<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\RoomTemplate;
use Doctrine\ORM\EntityManagerInterface;

class TemplateProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Return only default templates
        return $this->entityManager->getRepository(RoomTemplate::class)
            ->findBy(['isDefault' => true]);
    }
}
