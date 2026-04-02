<?php

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use App\Dto\Account\RequestResetPasswordInput;
use App\Dto\Account\ResetPasswordInput;
use App\Entity\User;
use App\Service\MailerService;
use App\State\ResetPasswordProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordProcessorTest extends TestCase
{
    private MailerService $mailer;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private EntityRepository $userRepo;

    protected function setUp(): void
    {
        $this->mailer = $this->createStub(MailerService::class);
        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->hasher = $this->createStub(UserPasswordHasherInterface::class);
        $this->userRepo = $this->createStub(EntityRepository::class);

        $this->em->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepo);
    }

    public function testRequestReturnsSuccessMessageEvenWhenEmailNotFound(): void
    {
        $this->mailer = $this->createMock(MailerService::class);
        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->mailer->expects($this->never())->method('sendResetPasswordEmail');

        $processor = new ResetPasswordProcessor($this->em, $this->hasher, $this->mailer);

        $input = new RequestResetPasswordInput();
        $input->email = 'unknown@test.com';

        $result = $processor->process($input, $this->createStub(Operation::class));

        $this->assertStringContainsString('If this email exists', $result->message);
    }

    public function testRequestSetsTokenAndSendsEmailWhenUserExists(): void
    {
        $this->mailer = $this->createMock(MailerService::class);
        $user = new User();
        $user->setEmail('user@test.com');
        $user->setDisplayName('Test User');
        $this->userRepo->method('findOneBy')->willReturn($user);

        $this->mailer->expects($this->once())->method('sendResetPasswordEmail');

        $processor = new ResetPasswordProcessor($this->em, $this->hasher, $this->mailer);

        $input = new RequestResetPasswordInput();
        $input->email = 'user@test.com';

        $processor->process($input, $this->createStub(Operation::class));

        $this->assertNotNull($user->getResetPasswordToken());
    }

    public function testConfirmThrowsBadRequestExceptionWhenTokenIsExpired(): void
    {
        $user = new User();
        $user->setResetPasswordToken('expiredtoken');
        $user->setResetPasswordExpiresAt(new \DateTime('-1 hour'));

        $this->userRepo->method('findOneBy')
            ->with(['resetPasswordToken' => 'expiredtoken'])
            ->willReturn($user);

        $processor = new ResetPasswordProcessor($this->em, $this->hasher, $this->mailer);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Ce lien a expiré, veuillez en demander un nouveau');

        $input = new ResetPasswordInput();
        $input->token = 'expiredtoken';
        $input->password = 'newpassword123';

        $processor->process($input, $this->createStub(Operation::class));
    }
}
