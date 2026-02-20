<?php

namespace App\Repository;

use App\Entity\Playlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Playlist>
 */
class PlaylistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Playlist::class);
    }

    public function add(Playlist $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function remove(Playlist $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne toutes les playlists triées sur le nom de la playlist
     * @param type $champ
     * @param type $ordre
     * @return Playlist[]
     */
    public function findAllOrderByName($ordre): array
    {
        return $this->createQueryBuilder('p')
                ->leftjoin('p.formations', 'f')
                ->groupBy('p.id')
                ->orderBy('p.name', $ordre)
                ->getQuery()
                ->getResult();
    }

    /**
     * Enregistrements dont un champ contient une valeur
     * ou tous les enregistrements si la valeur est vide
     * @param type $champ
     * @param type $valeur
     * @param type $table si $champ dans une autre table
     * @param string $ordre ASC ou DESC pour le tri par nom
     * @return Playlist[]
     */
    public function findByContainValue($champ, $valeur, $table = "", string $ordre = 'ASC'): array
    {
        if ($valeur == "") {
            return $this->findAllOrderByName($ordre);
        }
        if ($table == "") {
            return $this->createQueryBuilder('p')
                    ->leftJoin('p.formations', 'f')
                    ->where('p.'.$champ.' LIKE :valeur')
                    ->setParameter('valeur', '%'.$valeur.'%')
                    ->groupBy('p.id')
                    ->orderBy('p.name', $ordre)
                    ->getQuery()
                    ->getResult();
        } else {
            return $this->createQueryBuilder('p')
                    ->leftJoin('p.formations', 'f')
                    ->leftJoin('f.categories', 'c')
                    ->where('c.'.$champ.' LIKE :valeur')
                    ->setParameter('valeur', '%'.$valeur.'%')
                    ->groupBy('p.id')
                    ->orderBy('p.name', $ordre)
                    ->getQuery()
                    ->getResult();
        }
    }

    /**
     * Même filtre que findByContainValue mais tri par nombre de formations
     * @return Playlist[]
     */
    public function findByContainValueOrderByNombreFormations(string $champ, string $valeur, string $table, string $ordre): array
    {
        if ($valeur === "") {
            return $this->findAllOrderByNombreFormations($ordre);
        }
        if ($table === "") {
            return $this->createQueryBuilder('p')
                    ->leftJoin('p.formations', 'f')
                    ->where('p.'.$champ.' LIKE :valeur')
                    ->setParameter('valeur', '%'.$valeur.'%')
                    ->groupBy('p.id')
                    ->addSelect('COUNT(f.id) AS HIDDEN nbFormations')
                    ->orderBy('nbFormations', $ordre)
                    ->getQuery()
                    ->getResult();
        } else {
            return $this->createQueryBuilder('p')
                    ->leftJoin('p.formations', 'f')
                    ->leftJoin('f.categories', 'c')
                    ->where('c.'.$champ.' LIKE :valeur')
                    ->setParameter('valeur', '%'.$valeur.'%')
                    ->groupBy('p.id')
                    ->addSelect('COUNT(f.id) AS HIDDEN nbFormations')
                    ->orderBy('nbFormations', $ordre)
                    ->getQuery()
                    ->getResult();
        }
    }

    /**
     * Retourne toutes les playlists triées par nombre de formations
     * @return Playlist[]
     */
    public function findAllOrderByNombreFormations(string $ordre): array
    {
        return $this->createQueryBuilder('p')
                ->leftJoin('p.formations', 'f')
                ->groupBy('p.id')
                ->addSelect('COUNT(f.id) AS HIDDEN nbFormations')
                ->orderBy('nbFormations', $ordre)
                ->getQuery()
                ->getResult();
    }
}
