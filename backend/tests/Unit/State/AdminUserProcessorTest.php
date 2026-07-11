<?php

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use App\Dto\Account\AdminUpdateUserInput;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Service\EntrepriseContext;
use App\State\AdminUserProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminUserProcessorTest extends TestCase
{
    private function buildProcessor(User $currentUser, User $targetUser, Entreprise $entreprise): AdminUserProcessor
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($currentUser);

        $userRepo = $this->createStub(EntityRepository::class);
        $userRepo->method('find')->willReturn($targetUser);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($userRepo);

        $entrepriseContext = $this->createStub(EntrepriseContext::class);
        $entrepriseContext->method('getEntreprise')->willReturn($entreprise);
        $entrepriseContext->method('isAtLeastInCurrent')->willReturn(true);

        return new AdminUserProcessor($security, $em, $entrepriseContext);
    }

    public function testUpdateThrowsBadRequestExceptionWhenUserTriesToChangeOwnRole(): void
    {
        $entreprise = new Entreprise();

        $currentUser = new User();
        $currentUser->setRole('owner');
        $currentUser->addToEntreprise($entreprise, User::ROLE_OWNER);

        $targetUser = $currentUser; // same id → same user trying to change own role

        $data = new AdminUpdateUserInput();
        $data->role = 'editor';

        $processor = $this->buildProcessor($currentUser, $targetUser, $entreprise);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas modifier votre propre rôle');

        $processor->process($data, new Patch(), ['id' => 1]);
    }

    public function testUpdateThrowsAccessDeniedExceptionWhenOwnerTriesToChangeAdminRole(): void
    {
        $entreprise = new Entreprise();

        $currentUser = new User();
        $currentUser->setRole('owner');
        $currentUser->addToEntreprise($entreprise, User::ROLE_OWNER);
        (new \ReflectionProperty(User::class, 'id'))->setValue($currentUser, 1);

        $targetUser = new User();
        $targetUser->setRole('admin');
        $targetUser->addToEntreprise($entreprise, User::ROLE_ADMIN);
        (new \ReflectionProperty(User::class, 'id'))->setValue($targetUser, 2);

        $data = new AdminUpdateUserInput();
        $data->role = 'owner';

        $processor = $this->buildProcessor($currentUser, $targetUser, $entreprise);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Seuls les administrateurs peuvent attribuer le rôle administrateur');

        $processor->process($data, new Patch(), ['id' => 2]);
    }

    public function testRemoveThrowsBadRequestExceptionWhenUserRemovesThemself(): void
    {
        $entreprise = new Entreprise();

        $currentUser = new User();
        $currentUser->setRole('owner');
        $currentUser->addToEntreprise($entreprise, User::ROLE_OWNER);

        $targetUser = $currentUser; // same id → same user trying to remove themself

        $processor = $this->buildProcessor($currentUser, $targetUser, $entreprise);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas supprimer votre propre compte depuis cette interface');

        $processor->process(null, new Delete(), ['id' => 1]);
    }
}
