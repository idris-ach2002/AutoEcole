<?php

namespace App\Repository;

use App\Entity\Examen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Examen> */
class ExamenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Examen::class);
    }

    /** @return list<Examen> */
    public function findWithFilters(?string $type, ?string $lieu, ?string $period): array
    {
        $qb = $this->createQueryBuilder('e')->leftJoin('e.candidatExamens', 'ce')->addSelect('ce');
        if ($type) {
            $qb->andWhere('e.typeExamen = :type')->setParameter('type', $type);
        }
        if ($lieu) {
            $qb->andWhere('LOWER(e.lieu) LIKE LOWER(:lieu)')->setParameter('lieu', '%'.$lieu.'%');
        }
        if ($period === 'future') {
            $qb->andWhere('e.datePassage >= :today')->setParameter('today', new \DateTimeImmutable('today'));
        }
        if ($period === 'past') {
            $qb->andWhere('e.datePassage < :today')->setParameter('today', new \DateTimeImmutable('today'));
        }
        return $qb->orderBy('e.datePassage', 'ASC')->getQuery()->getResult();
    }

    /** @return list<Examen> */
    public function findUpcoming(int $limit = 8): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.datePassage >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('e.datePassage', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return array<string,int> */
    public function countByType(): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('e.typeExamen AS type, COUNT(e.id) AS total')
            ->groupBy('e.typeExamen')
            ->getQuery()
            ->getArrayResult();
        $stats = ['code' => 0, 'creneau' => 0, 'conduite' => 0];
        foreach ($rows as $row) {
            $stats[$row['type']] = (int) $row['total'];
        }
        return $stats;
    }
}
