<?php

namespace App\Entity;

use App\Repository\CandidatExamenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatExamenRepository::class)]
class CandidatExamen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "candidatExamens")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Candidat $candidat = null;

    #[ORM\ManyToOne(inversedBy: "candidatExamens")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Examen $examen = null;

    #[ORM\Column(length: 20)]
    private string $statut = "inscrit"; // inscrit | payé | réussi | échoué

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $resteAPayer;

    public function __construct()
    {
    }

    public function getId(): ?int { return $this->id; }

    public function getCandidat(): ?Candidat { return $this->candidat; }
    public function setCandidat(?Candidat $c): self { $this->candidat = $c; return $this; }

    public function getExamen(): ?Examen { return $this->examen; }
    public function setExamen(?Examen $examen): self
    {
        $this->examen = $examen;

        // ⚡ Initialisation automatique du reste à payer
        if ($examen !== null && ($this->resteAPayer ?? null) === null) {
            $this->resteAPayer = $examen->getFrais();
        }

        return $this;
    }


    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): self { $this->statut = $s; return $this; }

    public function isInscrit(): bool { return $this->statut === "inscrit"; }
    public function isPaye(): bool { return $this->statut === "payé"; }
    public function isReussi(): bool { return $this->statut === "réussi"; }
    public function isEchoue(): bool { return $this->statut === "échoué"; }

    // ⚠ Retourne toujours un float
    public function getResteAPayer(): float
    {
        return $this->resteAPayer ?? 0.0;
    }

    public function setResteAPayer(float $reste): self
    {
        $this->resteAPayer = $reste;
        if ($this->resteAPayer <= 0) {
            $this->setStatut('payé');
        }
        return $this;
    }

    public function payer(float $montant): self
    {
        $this->resteAPayer = max(0, $this->resteAPayer - $montant);

        // Si le reste à payer est zéro, on peut mettre à jour le statut
        if ($this->resteAPayer <= 0) {
            $this->setStatut('payé');
        }

        return $this;
    }

}
