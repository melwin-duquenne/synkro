<?php

namespace App\Controller;

use App\Entity\Module;
use App\Entity\RoomTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ModuleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/modules', name: 'api_modules_list', methods: ['GET'])]
    public function listModules(): JsonResponse
    {
        $modules = $this->entityManager->getRepository(Module::class)->findAll();

        $data = array_map(fn(Module $m) => [
            'id' => $m->getId(),
            'code' => $m->getCode(),
            'name' => $m->getName(),
            'description' => $m->getDescription()
        ], $modules);

        return new JsonResponse($data);
    }

    #[Route('/templates', name: 'api_templates_list', methods: ['GET'])]
    public function listTemplates(): JsonResponse
    {
        $templates = $this->entityManager->getRepository(RoomTemplate::class)->findBy(['isDefault' => true]);

        $data = array_map(fn(RoomTemplate $t) => [
            'id' => $t->getId(),
            'name' => $t->getName(),
            'description' => $t->getDescription(),
            'modules' => array_map(fn($tm) => [
                'code' => $tm->getModule()->getCode(),
                'name' => $tm->getModule()->getName()
            ], $t->getTemplateModules()->toArray())
        ], $templates);

        return new JsonResponse($data);
    }
}
