<?php

namespace App\Entity;

use App\Repository\CandidatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Index(columns: ['nom', 'prenom'], name: 'idx_candidat_identity')]
#[ORM\Index(columns: ['statut_paiement'], name: 'idx_candidat_payment_status')]
class Candidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $nom = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $prenom = '';

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Email]
    #[Assert\Length(max: 150)]
    private ?string $email = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $lieuNaissance = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Assert\Length(max: 5)]
    private ?string $groupeSanguin = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private float|string $prixPermis = 0.0;

    #[ORM\Column(name: 'reste_apayer', type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private float|string $resteAPayer = 0.0;

    #[ORM\Column(length: 20, options: ['default' => 'en cours'])]
    private string $statutPaiement = 'en cours';

    /** @var Collection<int, CandidatExamen> */
    #[ORM\OneToMany(targetEntity: CandidatExamen::class, mappedBy: 'candidat', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $candidatExamens;

    public function __construct()
    {
        $this->candidatExamens = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = mb_strtoupper(trim($nom)); return $this; }

    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $this->normalizeName($prenom); return $this; }

    public function getNomComplet(): string { return trim($this->prenom.' '.$this->nom); }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(\DateTimeInterface $date): self { $this->dateNaissance = $date; return $this; }

    public function getAge(): ?int
    {
        return $this->dateNaissance ? $this->dateNaissance->diff(new \DateTimeImmutable('today'))->y : null;
    }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone ? trim($telephone) : null; return $this; }

    public function getEmail(): ?string { return $this->email ?: null; }
    public function setEmail(?string $email): self { $this->email = $email ? mb_strtolower(trim($email)) : null; return $this; }

    public function getLieuNaissance(): ?string { return $this->lieuNaissance; }
    public function setLieuNaissance(?string $lieuNaissance): self { $this->lieuNaissance = $lieuNaissance ? trim($lieuNaissance) : null; return $this; }

    public function getGroupeSanguin(): ?string { return $this->groupeSanguin; }
    public function setGroupeSanguin(?string $groupeSanguin): self { $this->groupeSanguin = $groupeSanguin ?: null; return $this; }

    public function getPrixPermis(): float { return (float) $this->prixPermis; }
    public function setPrixPermis(float|string $prix): self
    {
        $oldPrix = $this->getPrixPermis();
        $prix = max(0.0, (float) $prix);
        $this->prixPermis = $prix;
        if ($this->getResteAPayer() <= 0.0 || $this->getResteAPayer() === $oldPrix) {
            $this->resteAPayer = $prix;
        }
        $this->updateStatutPaiement();
        return $this;
    }

    public function getResteAPayer(): float { return (float) $this->resteAPayer; }
    public function setResteAPayer(float|string $reste): self
    {
        $this->resteAPayer = max(0.0, (float) $reste);
        $this->updateStatutPaiement();
        return $this;
    }

    public function payer(float|string $montant): self
    {
        $montant = (float) $montant;
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit etre positif.');
        }
        $this->resteAPayer = max(0.0, $this->getResteAPayer() - $montant);
        $this->updateStatutPaiement();
        return $this;
    }

    public function getSolde(): float { return max(0.0, $this->getPrixPermis() - $this->getResteAPayer()); }
    public function isSolde(): bool { return $this->getResteAPayer() <= 0.0; }

    public function getProgressionPaiement(): float
    {
        if ($this->getPrixPermis() <= 0.0) {
            return 0.0;
        }
        return min(100.0, round(($this->getSolde() / $this->getPrixPermis()) * 100, 1));
    }

    public function getStatutPaiement(): string { return $this->statutPaiement; }
    public function setStatutPaiement(string $status): self { $this->statutPaiement = $status; return $this; }

    /** @return Collection<int, CandidatExamen> */
    public function getCandidatExamens(): Collection { return $this->candidatExamens; }

    public function addCandidatExamen(CandidatExamen $candidatExamen): self
    {
        if (!$this->candidatExamens->contains($candidatExamen)) {
            $this->candidatExamens->add($candidatExamen);
            $candidatExamen->setCandidat($this);
        }
        return $this;
    }

    public function removeCandidatExamen(CandidatExamen $candidatExamen): self
    {
        if ($this->candidatExamens->removeElement($candidatExamen) && $candidatExamen->getCandidat() === $this) {
            $candidatExamen->setCandidat(null);
        }
        return $this;
    }

    public function getLastExamStatus(string $type): ?string
    {
        foreach ($this->candidatExamens as $ce) {
            if ($ce->getExamen()?->getTypeExamen() === $type) {
                return $ce->getStatut();
            }
        }
        return null;
    }

    private function updateStatutPaiement(): void
    {
        $this->statutPaiement = $this->getResteAPayer() <= 0.0 ? 'soldé' : 'en cours';
    }

    private function normalizeName(string $value): string
    {
        $value = trim(mb_strtolower($value));
        return $value === '' ? '' : mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
