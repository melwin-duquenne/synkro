<?php

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use App\Dto\Account\AdminUpdateUserInput;
use App\Entity\User;
use App\State\AdminUserProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminUserProcessorTest extends TestCase
{
    private function buildProcessor(User $currentUser, User $targetUser): AdminUserProcessor
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($currentUser);

        $userRepo = $this->createStub(EntityRepository::class);
        $userRepo->method('find')->willReturn($targetUser);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($userRepo);

        return new AdminUserProcessor($security, $em);
    }

    public function testUpdateThrowsBadRequestExceptionWhenUserTriesToChangeOwnRole(): void
    {
        $currentUser = new User();
        $currentUser->setRole('owner');

        $targetUser = new User(); // both id = null → null === null → same user

        $data = new AdminUpdateUserInput();
        $data->role = 'editor';

        $processor = $this->buildProcessor($currentUser, $targetUser);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('You cannot change your own role');

        $processor->process($data, new Patch(), ['id' => 1]);
    }

    public function testUpdateThrowsAccessDeniedExceptionWhenOwnerTriesToChangeAdminRole(): void
    {
        $currentUser = new User();
        $currentUser->setRole('owner');
        (new \ReflectionProperty(User::class, 'id'))->setValue($currentUser, 1);

        $targetUser = new User();
        $targetUser->setRole('admin');
        (new \ReflectionProperty(User::class, 'id'))->setValue($targetUser, 2);

        $data = new AdminUpdateUserInput();
        $data->role = 'owner';

        $processor = $this->buildProcessor($currentUser, $targetUser);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Only admins can change admin roles');

        $processor->process($data, new Patch(), ['id' => 2]);
    }

    public function testRemoveThrowsBadRequestExceptionWhenUserRemovesThemself(): void
    {
        $currentUser = new User();
        $currentUser->setRole('owner');

        $targetUser = new User(); // both id = null → null === null → same user

        $processor = $this->buildProcessor($currentUser, $targetUser);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('You cannot delete your own account');

        $processor->process(null, new Delete(), ['id' => 1]);
    }
}
