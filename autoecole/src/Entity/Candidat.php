<?php

namespace App\Entity;

use App\Repository\CandidatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
class Candidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 100)]
    private string $prenom;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateNaissance;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 150, unique: false, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $prixPermis;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    private float $resteAPayer;

    #[ORM\Column(length: 20, options: ['default' => 'en cours'])]
    private string $statutPaiement = 'en cours';

    #[ORM\OneToMany(targetEntity: CandidatExamen::class, mappedBy: "candidat", cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $candidatExamens;

    public function __construct()
    {
        $this->candidatExamens = new ArrayCollection();
        $this->resteAPayer = $this->prixPermis ?? 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getDateNaissance(): \DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $date): self
    {
        $this->dateNaissance = $date;
        return $this;
    }


    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }


    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }


    public function getPrixPermis(): float
    {
        return $this->prixPermis;
    }

    public function setPrixPermis(float $prix): self
    {
        $this->prixPermis = $prix;

        // Initialisation sécurisée
        if (!isset($this->resteAPayer) || $this->resteAPayer <= 0) {
            $this->resteAPayer = $prix;
            $this->updateStatutPaiement();
        }

        return $this;
    }
    public function getResteAPayer(): float
    {
        return $this->resteAPayer;
    }

    public function setResteAPayer(float $reste): self
    {
        $this->resteAPayer = $reste;
        $this->updateStatutPaiement();
        return $this;
    }

    public function payer(float $montant): self
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException("Le montant doit être positif");
        }

        $this->resteAPayer = max(0, $this->resteAPayer - $montant);
        $this->updateStatutPaiement();

        return $this;
    }

    public function getSolde(): float
    {
        return $this->prixPermis - $this->resteAPayer;
    }

    public function isSolde(): bool
    {
        return $this->resteAPayer <= 0;
    }

    public function getStatutPaiement(): string
    {
        return $this->statutPaiement;
    }

    public function setStatutPaiement(string $status): self
    {
        $this->statutPaiement = $status;
        return $this;
    }

    private function updateStatutPaiement(): void
    {
        $this->statutPaiement = $this->resteAPayer <= 0 ? 'soldé' : 'en cours';
    }

    /** @return Collection<int, CandidatExamen> */
    public function getCandidatExamens(): Collection
    {
        return $this->candidatExamens;
    }

    public function addCandidatExamen(CandidatExamen $candidatExamen): self
    {
        if (!$this->candidatExamens->contains($candidatExamen)) {
            $this->candidatExamens[] = $candidatExamen;
            $candidatExamen->setCandidat($this);
        }
        return $this;
    }

    public function removeCandidatExamen(CandidatExamen $candidatExamen): self
    {
        if ($this->candidatExamens->removeElement($candidatExamen)) {
            if ($candidatExamen->getCandidat() === $this) {
                $candidatExamen->setCandidat(null);
            }
        }
        return $this;
    }
}
