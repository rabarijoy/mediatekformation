<?php

namespace App\Tests\Entity;

use App\Entity\Formation;
use PHPUnit\Framework\TestCase;

class FormationTest extends TestCase
{
    public function testGetPublishedAtStringAvecDate(): void
    {
        $formation = new Formation();
        $formation->setPublishedAt(new \DateTime('2024-03-15'));

        $this->assertEquals('15/03/2024', $formation->getPublishedAtString());
    }

    public function testGetPublishedAtStringAvecNull(): void
    {
        $formation = new Formation();

        $this->assertEquals('', $formation->getPublishedAtString());
    }

    public function testGetPublishedAtStringFormat(): void
    {
        $formation = new Formation();
        $formation->setPublishedAt(new \DateTime('2020-01-05'));

        $result = $formation->getPublishedAtString();

        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4}$/', $result);
        $this->assertEquals('05/01/2020', $result);
    }
}
