<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccueilTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    protected function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
        parent::tearDown();
    }

    public function testPageAccueilRepond200(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testPageAccueilContientTitreBienvenue(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertStringContainsString('Bienvenue', $crawler->filter('h3')->text());
    }

    public function testPageAccueilContientLienFormations(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertGreaterThan(0, $crawler->filter('a[href$="/formations"]')->count());
    }

    public function testPageAccueilContientLienPlaylists(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertGreaterThan(0, $crawler->filter('a[href$="/playlists"]')->count());
    }
}
