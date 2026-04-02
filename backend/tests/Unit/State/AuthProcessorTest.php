<?php

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use App\Dto\Auth\RegisterInput;
use App\Entity\User;
use App\State\AuthProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthProcessorTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private ValidatorInterface $validator;
    private EntityRepository $userRepo;

    protected function setUp(): void
    {
        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->hasher = $this->createStub(UserPasswordHasherInterface::class);
        $this->validator = $this->createStub(ValidatorInterface::class);
        $this->userRepo = $this->createStub(EntityRepository::class);

        $this->em->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepo);
    }

    public function testRegisterThrowsConflictExceptionWhenEmailAlreadyExists(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(new User());

        $processor = new AuthProcessor($this->em, $this->hasher, $this->validator);

        $input = new RegisterInput();
        $input->email = 'existing@test.com';
        $input->password = 'password123';
        $input->displayName = 'Test User';

        $this->expectException(ConflictHttpException::class);
        $processor->process($input, $this->createStub(Operation::class));
    }

    public function testRegisterHashesPasswordAndPersistsUserOnSuccess(): void
    {
        // createMock needed here to verify persist/flush are called exactly once
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getRepository')->with(User::class)->willReturn($this->userRepo);

        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->hasher->method('hashPassword')->willReturn('hashed_password');
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (User $user): void {
                // Simulate DB-assigned ID so UserOutput::fromEntity() can build without TypeError
                (new \ReflectionProperty(User::class, 'id'))->setValue($user, 1);
            });
        $this->em->expects($this->once())->method('flush');

        $processor = new AuthProcessor($this->em, $this->hasher, $this->validator);

        $input = new RegisterInput();
        $input->email = 'new@test.com';
        $input->password = 'password123';
        $input->displayName = 'New User';

        $result = $processor->process($input, $this->createStub(Operation::class));

        $this->assertSame('User registered successfully', $result->message);
    }

    public function testRegisterThrowsBadRequestExceptionWhenValidationFails(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->hasher->method('hashPassword')->willReturn('hashed');

        $violation = new ConstraintViolation('Invalid email', null, [], null, 'email', 'bad');
        $this->validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $processor = new AuthProcessor($this->em, $this->hasher, $this->validator);

        $input = new RegisterInput();
        $input->email = 'bad';
        $input->password = 'pass';
        $input->displayName = 'User';

        $this->expectException(BadRequestHttpException::class);
        $processor->process($input, $this->createStub(Operation::class));
    }
}
