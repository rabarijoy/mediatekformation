<?php

namespace App\Tests\Validator;

use App\Entity\Formation;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormationValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testDateFutureEchoueValidation(): void
    {
        $formation = new Formation();
        $formation->setPublishedAt(new \DateTime('+1 day'));

        $constraint = new LessThanOrEqual([
            'value' => 'today',
            'message' => 'La date doit être antérieure ou égale à aujourd\'hui.',
        ]);

        $violations = $this->validator->validate($formation->getPublishedAt(), $constraint);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('aujourd\'hui', $violations[0]->getMessage());
    }

    public function testDateAujourdhuiPasseValidation(): void
    {
        $formation = new Formation();
        $formation->setPublishedAt(new \DateTime('today'));

        $constraint = new LessThanOrEqual([
            'value' => 'today',
            'message' => 'La date doit être antérieure ou égale à aujourd\'hui.',
        ]);

        $violations = $this->validator->validate($formation->getPublishedAt(), $constraint);

        $this->assertCount(0, $violations);
    }

    public function testDatePasseePasseValidation(): void
    {
        $formation = new Formation();
        $formation->setPublishedAt(new \DateTime('-1 year'));

        $constraint = new LessThanOrEqual([
            'value' => 'today',
            'message' => 'La date doit être antérieure ou égale à aujourd\'hui.',
        ]);

        $violations = $this->validator->validate($formation->getPublishedAt(), $constraint);

        $this->assertCount(0, $violations);
    }

    public function testDateNullPasseValidation(): void
    {
        $formation = new Formation();

        $constraint = new LessThanOrEqual([
            'value' => 'today',
            'message' => 'La date doit être antérieure ou égale à aujourd\'hui.',
        ]);

        $violations = $this->validator->validate($formation->getPublishedAt(), $constraint);

        $this->assertCount(0, $violations);
    }
}
