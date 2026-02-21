<?php

namespace App\Tests\Repository;

use App\Entity\Categorie;
use App\Entity\Formation;
use App\Entity\Playlist;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FormationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FormationRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->repository = $this->em->getRepository(Formation::class);
    }

    protected function tearDown(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
        parent::tearDown();
    }

    private function creerFormation(string $title, string $date, ?Playlist $playlist = null): Formation
    {
        $formation = new Formation();
        $formation->setTitle($title);
        $formation->setPublishedAt(new \DateTime($date));
        if ($playlist !== null) {
            $formation->setPlaylist($playlist);
        }
        $this->em->persist($formation);
        $this->em->flush();
        return $formation;
    }

    private function creerPlaylist(string $name): Playlist
    {
        $playlist = new Playlist();
        $playlist->setName($name);
        $this->em->persist($playlist);
        $this->em->flush();
        return $playlist;
    }

    public function testFindAllOrderByTrieParDateAsc(): void
    {
        $this->creerFormation('Formation B', '2023-06-01');
        $this->creerFormation('Formation A', '2022-01-01');
        $this->creerFormation('Formation C', '2024-12-01');

        $formations = $this->repository->findAllOrderBy('publishedAt', 'ASC');

        $this->assertCount(3, $formations);
        $this->assertEquals('Formation A', $formations[0]->getTitle());
        $this->assertEquals('Formation C', $formations[2]->getTitle());
    }

    public function testFindAllOrderByTrieParDateDesc(): void
    {
        $this->creerFormation('Formation B', '2023-06-01');
        $this->creerFormation('Formation A', '2022-01-01');
        $this->creerFormation('Formation C', '2024-12-01');

        $formations = $this->repository->findAllOrderBy('publishedAt', 'DESC');

        $this->assertCount(3, $formations);
        $this->assertEquals('Formation C', $formations[0]->getTitle());
        $this->assertEquals('Formation A', $formations[2]->getTitle());
    }

    public function testFindByContainValueRetourneToutSiValeurVide(): void
    {
        $this->creerFormation('Symfony avancé', '2023-01-01');
        $this->creerFormation('PHP bases', '2023-02-01');

        $formations = $this->repository->findByContainValue('title', '');

        $this->assertCount(2, $formations);
    }

    public function testFindByContainValueFiltreParTitre(): void
    {
        $this->creerFormation('Symfony avancé', '2023-01-01');
        $this->creerFormation('PHP bases', '2023-02-01');
        $this->creerFormation('Symfony débutant', '2023-03-01');

        $formations = $this->repository->findByContainValue('title', 'Symfony');

        $this->assertCount(2, $formations);
        foreach ($formations as $f) {
            $this->assertStringContainsStringIgnoringCase('Symfony', $f->getTitle());
        }
    }

    public function testFindAllLastedRetourneNFormations(): void
    {
        $this->creerFormation('Formation 1', '2022-01-01');
        $this->creerFormation('Formation 2', '2023-01-01');
        $this->creerFormation('Formation 3', '2024-01-01');

        $formations = $this->repository->findAllLasted(2);

        $this->assertCount(2, $formations);
        $this->assertEquals('Formation 3', $formations[0]->getTitle());
    }

    public function testFindAllForOnePlaylistRetourneFormationsDeLaPlaylist(): void
    {
        $playlist = $this->creerPlaylist('Ma playlist');
        $this->creerFormation('Formation playlist', '2023-05-01', $playlist);
        $this->creerFormation('Formation sans playlist', '2023-06-01');

        $formations = $this->repository->findAllForOnePlaylist($playlist->getId());

        $this->assertCount(1, $formations);
        $this->assertEquals('Formation playlist', $formations[0]->getTitle());
    }
}
