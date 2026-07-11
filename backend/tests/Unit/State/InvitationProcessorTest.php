<?php

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use App\Dto\Invitation\AcceptInvitationInput;
use App\Dto\Invitation\SendInvitationInput;
use App\Entity\Entreprise;
use App\Entity\Invitation;
use App\Entity\User;
use App\Entity\UserEntreprise;
use App\Service\EntrepriseContext;
use App\Service\MailerService;
use App\State\InvitationProcessor;
use App\UseCase\Invitation\AcceptInvitationUseCase;
use App\UseCase\Invitation\CancelInvitationUseCase;
use App\UseCase\Invitation\SendInvitationUseCase;
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
    private EntityRepository $userEntrepriseRepo;

    protected function setUp(): void
    {
        $this->security = $this->createStub(Security::class);
        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->mailer = $this->createStub(MailerService::class);
        $this->userRepo = $this->createStub(EntityRepository::class);
        $this->invitationRepo = $this->createStub(EntityRepository::class);
        $this->userEntrepriseRepo = $this->createStub(EntityRepository::class);

        $this->em->method('getRepository')->willReturnMap([
            [User::class, $this->userRepo],
            [Invitation::class, $this->invitationRepo],
            [UserEntreprise::class, $this->userEntrepriseRepo],
        ]);
    }

    private function makeAdminUser(Entreprise $entreprise): User
    {
        $admin = new User();
        $admin->setRole('admin');
        $admin->addToEntreprise($entreprise, User::ROLE_ADMIN);
        return $admin;
    }

    private function makeEntrepriseContext(Entreprise $entreprise, string $roleInCurrent = User::ROLE_ADMIN): EntrepriseContext
    {
        $context = $this->createStub(EntrepriseContext::class);
        $context->method('getEntreprise')->willReturn($entreprise);
        $context->method('getRoleInCurrent')->willReturn($roleInCurrent);
        return $context;
    }

    private function makeSendOperation(): Operation
    {
        $op = $this->createStub(Operation::class);
        $op->method('getName')->willReturn('invitation_send');
        return $op;
    }

    private function makeProcessor(EntrepriseContext $entrepriseContext): InvitationProcessor
    {
        return new InvitationProcessor(
            $this->security,
            $entrepriseContext,
            new SendInvitationUseCase($this->em, $this->mailer),
            new CancelInvitationUseCase($this->em),
            new AcceptInvitationUseCase($this->em, $this->security)
        );
    }

    public function testSendThrowsConflictExceptionWhenUserAlreadyInEntreprise(): void
    {
        $entreprise = new Entreprise();
        $admin = $this->makeAdminUser($entreprise);
        $this->security->method('getUser')->willReturn($admin);
        $this->userRepo->method('findOneBy')->willReturn(new User());
        $this->userEntrepriseRepo->method('findOneBy')->willReturn(new UserEntreprise());

        $input = new SendInvitationInput();
        $input->email = 'existing@test.com';

        $this->expectException(ConflictHttpException::class);
        $this->makeProcessor($this->makeEntrepriseContext($entreprise))->process($input, $this->makeSendOperation());
    }

    public function testSendThrowsConflictExceptionWhenPendingInvitationAlreadyExists(): void
    {
        $entreprise = new Entreprise();
        $admin = $this->makeAdminUser($entreprise);
        $this->security->method('getUser')->willReturn($admin);
        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->invitationRepo->method('findOneBy')->willReturn(new Invitation());

        $input = new SendInvitationInput();
        $input->email = 'pending@test.com';

        $this->expectException(ConflictHttpException::class);
        $this->makeProcessor($this->makeEntrepriseContext($entreprise))->process($input, $this->makeSendOperation());
    }

    public function testAcceptThrowsBadRequestExceptionWhenInvitationIsExpired(): void
    {
        $invitation = new Invitation();
        $invitation->setToken('mytoken');
        $invitation->setExpiresAt(new \DateTime('-1 day'));

        $this->invitationRepo->method('findOneBy')
            ->with(['token' => 'mytoken'])
            ->willReturn($invitation);

        $op = $this->createStub(Operation::class);
        $op->method('getName')->willReturn('invitation_accept');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Cette invitation a expiré');

        $input = new AcceptInvitationInput();
        $input->token = 'mytoken';

        $entreprise = new Entreprise();
        $this->makeProcessor($this->makeEntrepriseContext($entreprise))->process($input, $op);
    }
}
