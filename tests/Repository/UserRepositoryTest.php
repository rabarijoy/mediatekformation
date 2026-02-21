<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->repository = $this->em->getRepository(User::class);
    }

    protected function tearDown(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
        parent::tearDown();
    }

    private function creerUser(string $email, string $password, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setRoles($roles);
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function testUpgradePasswordMetAJourLeMotDePasse(): void
    {
        $user = $this->creerUser('test@example.com', 'ancien_hash');

        $this->repository->upgradePassword($user, 'nouveau_hash');

        $userEnBase = $this->repository->findOneBy(['email' => 'test@example.com']);
        $this->assertEquals('nouveau_hash', $userEnBase->getPassword());
    }

    public function testUpgradePasswordLanceExceptionSiNonUser(): void
    {
        $fakeUser = new class implements PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string { return 'hash'; }
            public function getUserIdentifier(): string { return 'fake'; }
        };

        $this->expectException(UnsupportedUserException::class);
        $this->repository->upgradePassword($fakeUser, 'nouveau_hash');
    }

    public function testFindOneByEmailRetourneLeBonUtilisateur(): void
    {
        $this->creerUser('admin@test.com', 'hash1', ['ROLE_ADMIN']);
        $this->creerUser('user@test.com', 'hash2', ['ROLE_USER']);

        $user = $this->repository->findOneBy(['email' => 'admin@test.com']);

        $this->assertNotNull($user);
        $this->assertEquals('admin@test.com', $user->getEmail());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testFindOneByEmailRetourneNullSiInexistant(): void
    {
        $user = $this->repository->findOneBy(['email' => 'inconnu@test.com']);

        $this->assertNull($user);
    }
}
