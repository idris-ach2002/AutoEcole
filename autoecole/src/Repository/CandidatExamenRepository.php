<?php

namespace App\Repository;

use App\Entity\CandidatExamen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CandidatExamen>
 */
class CandidatExamenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CandidatExamen::class);
    }

    public function filtrer(?string $nom, ?string $prenom, ?string $dateDebut, ?string $dateFin)
    {
        $qb = $this->createQueryBuilder('ce')
            ->join('ce.candidat', 'c')
            ->join('ce.examen', 'e')
            ->addSelect('c', 'e');

        if ($nom) {
            $qb->andWhere('LOWER(c.nom) LIKE LOWER(:nom)')
                ->setParameter('nom', '%'.$nom.'%');
        }

        if ($prenom) {
            $qb->andWhere('LOWER(c.prenom) LIKE LOWER(:prenom)')
                ->setParameter('prenom', '%'.$prenom.'%');
        }

        if ($dateDebut) {
            $qb->andWhere('e.datePassage >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($dateDebut));
        }

        if ($dateFin) {
            $qb->andWhere('e.datePassage <= :dateFin')
                ->setParameter('dateFin', new \DateTime($dateFin));
        }

        return $qb->orderBy('e.datePassage', 'DESC')
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return CandidatExamen[] Returns an array of CandidatExamen objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CandidatExamen
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
