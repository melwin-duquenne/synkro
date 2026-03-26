<?php

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use App\Dto\Invitation\AcceptInvitationInput;
use App\Dto\Invitation\SendInvitationInput;
use App\Entity\Entreprise;
use App\Entity\Invitation;
use App\Entity\User;
use App\Service\MailerService;
use App\State\InvitationProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class InvitationProcessorTest extends TestCase
{
    private Security $security;
    private EntityManagerInterface $em;
    private MailerService $mailer;
    private EntityRepository $userRepo;
    private EntityRepository $invitationRepo;

    protected function setUp(): void
    {
        $this->security = $this->createStub(Security::class);
        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->mailer = $this->createStub(MailerService::class);
        $this->userRepo = $this->createStub(EntityRepository::class);
        $this->invitationRepo = $this->createStub(EntityRepository::class);

        $this->em->method('getRepository')->willReturnMap([
            [User::class, $this->userRepo],
            [Invitation::class, $this->invitationRepo],
        ]);
    }

    private function makeAdminUser(): User
    {
        $entreprise = $this->createStub(Entreprise::class);
        $admin = new User();
        $admin->setRole('admin');
        $admin->setEntreprise($entreprise);
        return $admin;
    }

    private function makeSendOperation(): Operation
    {
        $op = $this->createStub(Operation::class);
        $op->method('getName')->willReturn('invitation_send');
        return $op;
    }

    public function testSendThrowsConflictExceptionWhenUserAlreadyInEntreprise(): void
    {
        $admin = $this->makeAdminUser();
        $this->security->method('getUser')->willReturn($admin);
        $this->userRepo->method('findOneBy')->willReturn(new User());

        $processor = new InvitationProcessor($this->security, $this->em, $this->mailer);

        $input = new SendInvitationInput();
        $input->email = 'existing@test.com';

        $this->expectException(ConflictHttpException::class);
        $processor->process($input, $this->makeSendOperation());
    }

    public function testSendThrowsConflictExceptionWhenPendingInvitationAlreadyExists(): void
    {
        $admin = $this->makeAdminUser();
        $this->security->method('getUser')->willReturn($admin);
        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->invitationRepo->method('findOneBy')->willReturn(new Invitation());

        $processor = new InvitationProcessor($this->security, $this->em, $this->mailer);

        $input = new SendInvitationInput();
        $input->email = 'pending@test.com';

        $this->expectException(ConflictHttpException::class);
        $processor->process($input, $this->makeSendOperation());
    }

    public function testAcceptThrowsBadRequestExceptionWhenInvitationIsExpired(): void
    {
        $invitation = new Invitation();
        $invitation->setToken('mytoken');
        $invitation->setExpiresAt(new \DateTime('-1 day'));
        // status defaults to 'pending' via entity property initializer

        $this->invitationRepo->method('findOneBy')
            ->with(['token' => 'mytoken'])
            ->willReturn($invitation);

        $op = $this->createStub(Operation::class);
        $op->method('getName')->willReturn('invitation_accept');

        $processor = new InvitationProcessor($this->security, $this->em, $this->mailer);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Cette invitation a expiré');

        $input = new AcceptInvitationInput();
        $input->token = 'mytoken';

        $processor->process($input, $op);
    }
}
