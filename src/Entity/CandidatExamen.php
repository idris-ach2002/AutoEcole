<?php

namespace App\Entity;

use App\Repository\CandidatExamenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CandidatExamenRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_candidat_examen', columns: ['candidat_id', 'examen_id'])]
#[ORM\Index(columns: ['statut'], name: 'idx_candidat_examen_statut')]
class CandidatExamen
{
    public const STATUT_INSCRIT = 'inscrit';
    public const STATUT_PAYE = 'payé';
    public const STATUT_REUSSI = 'réussi';
    public const STATUT_ECHOUE = 'échoué';
    public const STATUTS = [self::STATUT_INSCRIT, self::STATUT_PAYE, self::STATUT_REUSSI, self::STATUT_ECHOUE];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'candidatExamens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Candidat $candidat = null;

    #[ORM\ManyToOne(inversedBy: 'candidatExamens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Examen $examen = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::STATUTS)]
    private string $statut = self::STATUT_INSCRIT;

    #[ORM\Column(name: 'reste_apayer', type: 'decimal', precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private float|string $resteAPayer = 0.0;

    public function getId(): ?int { return $this->id; }

    public function getCandidat(): ?Candidat { return $this->candidat; }
    public function setCandidat(?Candidat $candidat): self { $this->candidat = $candidat; return $this; }

    public function getExamen(): ?Examen { return $this->examen; }
    public function setExamen(?Examen $examen): self
    {
        $this->examen = $examen;
        if ($examen !== null && $this->getResteAPayer() <= 0.0 && $this->statut === self::STATUT_INSCRIT) {
            $this->resteAPayer = $examen->getFrais();
        }
        return $this;
    }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self
    {
        if (!in_array($statut, self::STATUTS, true)) {
            throw new \InvalidArgumentException('Statut examen invalide.');
        }
        $this->statut = $statut;
        return $this;
    }

    public function getStatutLabel(): string
    {
        return match ($this->statut) {
            self::STATUT_PAYE => 'Payé',
            self::STATUT_REUSSI => 'Réussi',
            self::STATUT_ECHOUE => 'Échoué',
            default => 'Inscrit',
        };
    }

    public function isInscrit(): bool { return $this->statut === self::STATUT_INSCRIT; }
    public function isPaye(): bool { return $this->statut === self::STATUT_PAYE; }
    public function isReussi(): bool { return $this->statut === self::STATUT_REUSSI; }
    public function isEchoue(): bool { return $this->statut === self::STATUT_ECHOUE; }

    public function getResteAPayer(): float { return (float) $this->resteAPayer; }
    public function setResteAPayer(float|string $reste): self
    {
        $this->resteAPayer = max(0.0, (float) $reste);
        if ($this->getResteAPayer() <= 0.0 && $this->statut === self::STATUT_INSCRIT) {
            $this->statut = self::STATUT_PAYE;
        }
        return $this;
    }

    public function payer(float|string $montant): self
    {
        $montant = (float) $montant;
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit etre positif.');
        }
        $this->resteAPayer = max(0.0, $this->getResteAPayer() - $montant);
        if ($this->getResteAPayer() <= 0.0 && $this->statut === self::STATUT_INSCRIT) {
            $this->statut = self::STATUT_PAYE;
        }
        return $this;
    }

    public function isSolde(): bool { return $this->getResteAPayer() <= 0.0; }
}
