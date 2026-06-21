<?php

namespace App\Repository;

use App\Entity\Candidat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Candidat> */
class CandidatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidat::class);
    }

    /** @return list<Candidat> */
    public function findWithFilters(?string $q, ?string $paymentStatus, ?string $bloodGroup, string $sort = 'nom', string $direction = 'ASC'): array
    {
        $allowedSort = ['nom' => 'c.nom', 'prenom' => 'c.prenom', 'reste' => 'c.resteAPayer', 'prix' => 'c.prixPermis', 'age' => 'c.dateNaissance'];
        $sortExpr = $allowedSort[$sort] ?? 'c.nom';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('c')->leftJoin('c.candidatExamens', 'ce')->addSelect('ce');
        if ($q) {
            $qb->andWhere('LOWER(c.nom) LIKE LOWER(:q) OR LOWER(c.prenom) LIKE LOWER(:q) OR LOWER(c.email) LIKE LOWER(:q) OR c.telephone LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }
        if ($paymentStatus) {
            $qb->andWhere('c.statutPaiement = :paymentStatus')->setParameter('paymentStatus', $paymentStatus);
        }
        if ($bloodGroup) {
            $qb->andWhere('c.groupeSanguin = :bloodGroup')->setParameter('bloodGroup', $bloodGroup);
        }
        return $qb->orderBy($sortExpr, $direction)->addOrderBy('c.prenom', 'ASC')->getQuery()->getResult();
    }

    /** @return array<string,float|int> */
    public function getFinancialStats(): array
    {
        $row = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) AS total, COALESCE(SUM(c.prixPermis), 0) AS totalContrats, COALESCE(SUM(c.resteAPayer), 0) AS reste, COALESCE(SUM(c.prixPermis - c.resteAPayer), 0) AS encaisse')
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $row['total'],
            'totalContrats' => (float) $row['totalContrats'],
            'reste' => (float) $row['reste'],
            'encaisse' => (float) $row['encaisse'],
        ];
    }

    /** @return list<Candidat> */
    public function findPaymentAlerts(float $threshold = 500.0): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.resteAPayer >= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('c.resteAPayer', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
    }
}
