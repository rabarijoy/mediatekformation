<?php

namespace App\Tests\Functional;

use App\Entity\Categorie;
use App\Entity\Formation;
use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlaylistsTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->loadTestData();
    }

    protected function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
        parent::tearDown();
    }

    private function loadTestData(): void
    {
        $playlistA = (new Playlist())->setName('Algorithmes');
        $playlistB = (new Playlist())->setName('Débutants Python');
        $catPHP = (new Categorie())->setName('PHP');

        $this->em->persist($playlistA);
        $this->em->persist($playlistB);
        $this->em->persist($catPHP);
        $this->em->flush();

        // Algorithmes : 2 formations
        $f1 = (new Formation())
            ->setTitle('Eclipse PHP')
            ->setPublishedAt(new \DateTime('2024-01-01'))
            ->setPlaylist($playlistA);
        $f1->addCategory($catPHP);

        $f2 = (new Formation())
            ->setTitle('Symfony avancé')
            ->setPublishedAt(new \DateTime('2022-11-01'))
            ->setPlaylist($playlistA);

        // Débutants Python : 1 formation
        $f3 = (new Formation())
            ->setTitle('Bases de Python')
            ->setPublishedAt(new \DateTime('2023-06-15'))
            ->setPlaylist($playlistB);

        $this->em->persist($f1);
        $this->em->persist($f2);
        $this->em->persist($f3);
        $this->em->flush();
    }

    private function getPremierNom(\Symfony\Component\DomCrawler\Crawler $crawler): string
    {
        return trim($crawler->filter('tbody tr:first-child td:first-child h5.text-info')->text());
    }

    // ─── TRIS ────────────────────────────────────────────────────────────────

    public function testTriParNomAscPremiereLigneEstAlgorithmes(): void
    {
        $crawler = $this->client->request('GET', '/playlists');
        $link = $crawler->filter('thead th:first-child a.btn-info')->eq(0)->link();
        $crawler = $this->client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Algorithmes', $this->getPremierNom($crawler));
    }

    public function testTriParNomDescPremiereLigneEstDebutants(): void
    {
        $crawler = $this->client->request('GET', '/playlists');
        $link = $crawler->filter('thead th:first-child a.btn-info')->eq(1)->link();
        $crawler = $this->client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Débutants Python', $this->getPremierNom($crawler));
    }

    public function testTriParNombreFormationsAscPremiereLigneEstDebutants(): void
    {
        $crawler = $this->client->request('GET', '/playlists');
        $link = $crawler->filter('thead th:nth-child(3) a.btn-info')->eq(0)->link();
        $crawler = $this->client->click($link);

        $this->assertResponseIsSuccessful();
        // Débutants Python = 1 formation, Algorithmes = 2 → Débutants en premier ASC
        $this->assertEquals('Débutants Python', $this->getPremierNom($crawler));
    }

    public function testTriParNombreFormationsDescPremiereLigneEstAlgorithmes(): void
    {
        $crawler = $this->client->request('GET', '/playlists');
        $link = $crawler->filter('thead th:nth-child(3) a.btn-info')->eq(1)->link();
        $crawler = $this->client->click($link);

        $this->assertResponseIsSuccessful();
        // Algorithmes = 2 formations → premier en DESC
        $this->assertEquals('Algorithmes', $this->getPremierNom($crawler));
    }
}
