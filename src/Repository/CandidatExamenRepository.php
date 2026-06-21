<?php

namespace App\Repository;

use App\Entity\CandidatExamen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CandidatExamen> */
class CandidatExamenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CandidatExamen::class);
    }

    public function filtrer(?string $nom, ?string $prenom, ?string $dateDebut, ?string $dateFin): array
    {
        $qb = $this->baseQuery();
        if ($nom) {
            $qb->andWhere('LOWER(c.nom) LIKE LOWER(:nom)')->setParameter('nom', '%'.$nom.'%');
        }
        if ($prenom) {
            $qb->andWhere('LOWER(c.prenom) LIKE LOWER(:prenom)')->setParameter('prenom', '%'.$prenom.'%');
        }
        if ($dateDebut) {
            $qb->andWhere('e.datePassage >= :dateDebut')->setParameter('dateDebut', new \DateTime($dateDebut));
        }
        if ($dateFin) {
            $qb->andWhere('e.datePassage <= :dateFin')->setParameter('dateFin', new \DateTime($dateFin));
        }
        return $qb->orderBy('e.datePassage', 'DESC')->getQuery()->getResult();
    }

    /** @return list<CandidatExamen> */
    public function findWithFilters(?string $q, ?string $status, ?string $type, ?string $payment): array
    {
        $qb = $this->baseQuery();
        if ($q) {
            $qb->andWhere('LOWER(c.nom) LIKE LOWER(:q) OR LOWER(c.prenom) LIKE LOWER(:q) OR LOWER(c.email) LIKE LOWER(:q)')
                ->setParameter('q', '%'.$q.'%');
        }
        if ($status) {
            $qb->andWhere('ce.statut = :status')->setParameter('status', $status);
        }
        if ($type) {
            $qb->andWhere('e.typeExamen = :type')->setParameter('type', $type);
        }
        if ($payment === 'due') {
            $qb->andWhere('ce.resteAPayer > 0');
        }
        if ($payment === 'paid') {
            $qb->andWhere('ce.resteAPayer <= 0');
        }
        return $qb->orderBy('e.datePassage', 'DESC')->addOrderBy('c.nom', 'ASC')->getQuery()->getResult();
    }

    /** @return list<CandidatExamen> */
    public function findResultAlerts(): array
    {
        return $this->baseQuery()
            ->andWhere('e.datePassage < :today')
            ->andWhere('ce.statut IN (:pending)')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('pending', ['inscrit', 'payé'])
            ->orderBy('e.datePassage', 'ASC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
    }

    /** @return array<string,float|int> */
    public function getFinancialStats(): array
    {
        $row = $this->createQueryBuilder('ce')
            ->select('COUNT(ce.id) AS total, COALESCE(SUM(ce.resteAPayer), 0) AS reste')
            ->getQuery()
            ->getSingleResult();
        return ['total' => (int) $row['total'], 'reste' => (float) $row['reste']];
    }

    private function baseQuery()
    {
        return $this->createQueryBuilder('ce')
            ->join('ce.candidat', 'c')
            ->join('ce.examen', 'e')
            ->addSelect('c', 'e');
    }
}
