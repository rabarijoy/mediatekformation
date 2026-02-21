<?php

namespace App\Tests\Functional;

use App\Entity\Categorie;
use App\Entity\Formation;
use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormationsTest extends WebTestCase
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
        $catBases = (new Categorie())->setName('Bases');

        $this->em->persist($playlistA);
        $this->em->persist($playlistB);
        $this->em->persist($catPHP);
        $this->em->persist($catBases);
        $this->em->flush();

        $f1 = (new Formation())
            ->setTitle('Eclipse PHP')
            ->setPublishedAt(new \DateTime('2024-01-01'))
            ->setPlaylist($playlistA);
        $f1->addCategory($catPHP);

        $f2 = (new Formation())
            ->setTitle('Bases de Python')
            ->setPublishedAt(new \DateTime('2023-06-15'))
            ->setPlaylist($playlistB);

        $f3 = (new Formation())
            ->setTitle('Symfony avancé')
            ->setPublishedAt(new \DateTime('2022-11-01'))
            ->setPlaylist($playlistA);
        $f3->addCategory($catBases);

        $this->em->persist($f1);
        $this->em->persist($f2);
        $this->em->persist($f3);
        $this->em->flush();
    }

    private function getPremierTitre(\Symfony\Component\DomCrawler\Crawler $crawler): string
    {
        return trim($crawler->filter('tbody tr:first-child td:first-child h5.text-info')->text());
    }

    // ─── TRIS ────────────────────────────────────────────────────────────────

    public function testTriParTitreAscPremiereLigneEstBasesDepython(): void
    {
        $crawler = $this->client->request('GET', '/formations');
        $link = $crawler->filter('thead th:first-child a.btn-info')->eq(0)->link();
        $crawler = $this->client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Bases de Python', $this->getPremierTitre($crawler));
    }

    public function testTriParTitreDescPremiereLigneEstSymfony(): void
    {
        $crawler = $this->client->request('GET', '/formations');
        $link = $crawler->filter('thead th:first-child a.btn-info')->eq(1)->link();
        $crawler = $this->client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Symfony avancé', $this->getPremierTitre($crawler));
    }

    public function testTriParDateDescPremiereLigneEstEclipsePhp(): void
    {
        $crawler = $this->client->request('GET', '/formations');
        $link = $crawler->filter('thead th:nth-child(4) a.btn-info')->eq(1)->link();
        $crawler = $this->client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Eclipse PHP', $this->getPremierTitre($crawler));
    }

    public function testTriParDateAscPremiereLigneEstSymfony(): void
    {
        $crawler = $this->client->request('GET', '/formations');
        $link = $crawler->filter('thead th:nth-child(4) a.btn-info')->eq(0)->link();
        $crawler = $this->client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Symfony avancé', $this->getPremierTitre($crawler));
    }

    // ─── FILTRES ─────────────────────────────────────────────────────────────

    public function testFiltreParTitreRetourneUnResultat(): void
    {
        $crawler = $this->client->request('GET', '/formations');
        $form = $crawler->selectButton('filtrer')->form(['recherche' => 'Eclipse']);
        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('tbody tr'));
        $this->assertEquals('Eclipse PHP', $this->getPremierTitre($crawler));
    }

    public function testFiltreParTitreAucunResultat(): void
    {
        $crawler = $this->client->request('GET', '/formations');
        $form = $crawler->selectButton('filtrer')->form(['recherche' => 'inexistant_xyz']);
        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('tbody tr'));
    }

    public function testFiltreParPlaylistRetourneFormationsDeLaPlaylist(): void
    {
        $crawler = $this->client->request('GET', '/formations');
        $form = $crawler->selectButton('filtrer')->eq(1)->form(['recherche' => 'Algorithmes']);
        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        // Algorithmes contient 2 formations (Eclipse PHP + Symfony avancé)
        $this->assertCount(2, $crawler->filter('tbody tr'));
    }
}
