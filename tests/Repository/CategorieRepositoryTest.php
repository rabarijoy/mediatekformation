<?php

namespace App\Tests\Repository;

use App\Entity\Categorie;
use App\Entity\Formation;
use App\Entity\Playlist;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategorieRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CategorieRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->repository = $this->em->getRepository(Categorie::class);
    }

    protected function tearDown(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
        parent::tearDown();
    }

    private function creerCategorie(string $name): Categorie
    {
        $categorie = new Categorie();
        $categorie->setName($name);
        $this->em->persist($categorie);
        $this->em->flush();
        return $categorie;
    }

    private function creerPlaylist(string $name): Playlist
    {
        $playlist = new Playlist();
        $playlist->setName($name);
        $this->em->persist($playlist);
        $this->em->flush();
        return $playlist;
    }

    private function creerFormation(string $title, Playlist $playlist, array $categories = []): Formation
    {
        $formation = new Formation();
        $formation->setTitle($title);
        $formation->setPlaylist($playlist);
        foreach ($categories as $categorie) {
            $formation->addCategory($categorie);
        }
        $this->em->persist($formation);
        $this->em->flush();
        return $formation;
    }

    public function testFindAllForOnePlaylistRetourneCategoriesDeLaPlaylist(): void
    {
        $cat1 = $this->creerCategorie('PHP');
        $cat2 = $this->creerCategorie('Symfony');
        $this->creerCategorie('Laravel');

        $playlist = $this->creerPlaylist('Ma playlist');
        $this->creerFormation('Formation 1', $playlist, [$cat1, $cat2]);

        $categories = $this->repository->findAllForOnePlaylist($playlist->getId());

        $this->assertCount(2, $categories);
        $noms = array_map(fn(Categorie $c) => $c->getName(), $categories);
        $this->assertContains('PHP', $noms);
        $this->assertContains('Symfony', $noms);
        $this->assertNotContains('Laravel', $noms);
    }

    public function testFindAllForOnePlaylistTrieParNomAsc(): void
    {
        $cat1 = $this->creerCategorie('Zend');
        $cat2 = $this->creerCategorie('Angular');

        $playlist = $this->creerPlaylist('Playlist triée');
        $this->creerFormation('Formation', $playlist, [$cat1, $cat2]);

        $categories = $this->repository->findAllForOnePlaylist($playlist->getId());

        $this->assertCount(2, $categories);
        $this->assertEquals('Angular', $categories[0]->getName());
        $this->assertEquals('Zend', $categories[1]->getName());
    }

    public function testFindAllForOnePlaylistNeRetournePasCategoriesAutresPlaylists(): void
    {
        $cat = $this->creerCategorie('Python');

        $playlist1 = $this->creerPlaylist('Playlist 1');
        $playlist2 = $this->creerPlaylist('Playlist 2');

        $this->creerFormation('Formation P2', $playlist2, [$cat]);

        $categories = $this->repository->findAllForOnePlaylist($playlist1->getId());

        $this->assertCount(0, $categories);
    }

    public function testFindAllForOnePlaylistAvecPlusieursFormations(): void
    {
        $cat1 = $this->creerCategorie('PHP');
        $cat2 = $this->creerCategorie('SQL');

        $playlist = $this->creerPlaylist('Playlist multi');
        $this->creerFormation('Formation 1', $playlist, [$cat1]);
        $this->creerFormation('Formation 2', $playlist, [$cat2]);

        $categories = $this->repository->findAllForOnePlaylist($playlist->getId());

        $this->assertCount(2, $categories);
    }
}
