<?php

namespace App\Tests\Repository;

use App\Entity\Categorie;
use App\Entity\Formation;
use App\Entity\Playlist;
use App\Repository\PlaylistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PlaylistRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private PlaylistRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->repository = $this->em->getRepository(Playlist::class);
    }

    protected function tearDown(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
        parent::tearDown();
    }

    private function creerPlaylist(string $name): Playlist
    {
        $playlist = new Playlist();
        $playlist->setName($name);
        $this->em->persist($playlist);
        $this->em->flush();
        return $playlist;
    }

    private function creerFormationDansPlaylist(string $title, string $date, Playlist $playlist): Formation
    {
        $formation = new Formation();
        $formation->setTitle($title);
        $formation->setPublishedAt(new \DateTime($date));
        $formation->setPlaylist($playlist);
        $this->em->persist($formation);
        $this->em->flush();
        return $formation;
    }

    private function creerCategorie(string $name): Categorie
    {
        $categorie = new Categorie();
        $categorie->setName($name);
        $this->em->persist($categorie);
        $this->em->flush();
        return $categorie;
    }

    public function testFindAllOrderByNameAscTrieParNomCroissant(): void
    {
        $this->creerPlaylist('Zend Framework');
        $this->creerPlaylist('Angular');
        $this->creerPlaylist('MySQL');

        $playlists = $this->repository->findAllOrderByName('ASC');

        $this->assertCount(3, $playlists);
        $this->assertEquals('Angular', $playlists[0]->getName());
        $this->assertEquals('Zend Framework', $playlists[2]->getName());
    }

    public function testFindAllOrderByNameDescTrieParNomDecroissant(): void
    {
        $this->creerPlaylist('Zend Framework');
        $this->creerPlaylist('Angular');
        $this->creerPlaylist('MySQL');

        $playlists = $this->repository->findAllOrderByName('DESC');

        $this->assertCount(3, $playlists);
        $this->assertEquals('Zend Framework', $playlists[0]->getName());
        $this->assertEquals('Angular', $playlists[2]->getName());
    }

    public function testFindByContainValueRetourneToutSiValeurVide(): void
    {
        $this->creerPlaylist('Symfony');
        $this->creerPlaylist('Laravel');

        $playlists = $this->repository->findByContainValue('name', '');

        $this->assertCount(2, $playlists);
    }

    public function testFindByContainValueFiltreParNom(): void
    {
        $this->creerPlaylist('Symfony avancé');
        $this->creerPlaylist('Laravel bases');
        $this->creerPlaylist('Symfony débutant');

        $playlists = $this->repository->findByContainValue('name', 'Symfony');

        $this->assertCount(2, $playlists);
        foreach ($playlists as $p) {
            $this->assertStringContainsStringIgnoringCase('Symfony', $p->getName());
        }
    }

    public function testFindByContainValueFiltreParCategorie(): void
    {
        $cat = $this->creerCategorie('PHP');

        $p1 = $this->creerPlaylist('Playlist PHP');
        $f1 = $this->creerFormationDansPlaylist('Formation PHP', '2023-01-01', $p1);
        $f1->addCategory($cat);
        $this->em->flush();

        $this->creerPlaylist('Playlist JS');

        $playlists = $this->repository->findByContainValue('name', 'PHP', 'categories');

        $this->assertCount(1, $playlists);
        $this->assertEquals('Playlist PHP', $playlists[0]->getName());
    }

    public function testFindAllOrderByNombreFormationsAscTrieParNombreFormations(): void
    {
        $p1 = $this->creerPlaylist('Playlist vide');
        $p2 = $this->creerPlaylist('Playlist riche');

        $this->creerFormationDansPlaylist('F1', '2023-01-01', $p2);
        $this->creerFormationDansPlaylist('F2', '2023-02-01', $p2);
        $this->creerFormationDansPlaylist('F3', '2023-03-01', $p2);

        $playlists = $this->repository->findAllOrderByNombreFormations('ASC');

        $this->assertCount(2, $playlists);
        $this->assertEquals('Playlist vide', $playlists[0]->getName());
        $this->assertEquals('Playlist riche', $playlists[1]->getName());
    }

    public function testFindAllOrderByNombreFormationsDescTrieDecroissant(): void
    {
        $p1 = $this->creerPlaylist('Playlist vide');
        $p2 = $this->creerPlaylist('Playlist riche');

        $this->creerFormationDansPlaylist('F1', '2023-01-01', $p2);
        $this->creerFormationDansPlaylist('F2', '2023-02-01', $p2);

        $playlists = $this->repository->findAllOrderByNombreFormations('DESC');

        $this->assertCount(2, $playlists);
        $this->assertEquals('Playlist riche', $playlists[0]->getName());
        $this->assertEquals('Playlist vide', $playlists[1]->getName());
    }

    public function testFindByContainValueOrderByNombreFormationsFiltreSansTable(): void
    {
        $p1 = $this->creerPlaylist('Symfony A');
        $p2 = $this->creerPlaylist('Symfony B');
        $this->creerPlaylist('Laravel C');

        $this->creerFormationDansPlaylist('F1', '2023-01-01', $p2);
        $this->creerFormationDansPlaylist('F2', '2023-02-01', $p2);

        $playlists = $this->repository->findByContainValueOrderByNombreFormations('name', 'Symfony', '', 'DESC');

        $this->assertCount(2, $playlists);
        $this->assertEquals('Symfony B', $playlists[0]->getName());
        $this->assertEquals('Symfony A', $playlists[1]->getName());
    }
}
