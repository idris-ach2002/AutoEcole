<?php

namespace App\Service;

use App\Entity\Candidat;
use App\Entity\CandidatExamen;
use App\Entity\Examen;

class AutoEcoleManager
{
    public const EXAM_FLOW = [Examen::TYPE_CODE, Examen::TYPE_CRENEAU, Examen::TYPE_CONDUITE];

    public function peutPasserExamen(Candidat $candidat, string $typeExamen): bool
    {
        return in_array($typeExamen, $this->getEligibleExamTypes($candidat), true);
    }

    /** @return list<string> */
    public function getEligibleExamTypes(Candidat $candidat): array
    {
        if (!$this->hasSucceeded($candidat, Examen::TYPE_CODE)) {
            return [Examen::TYPE_CODE];
        }
        if (!$this->hasSucceeded($candidat, Examen::TYPE_CRENEAU)) {
            return [Examen::TYPE_CRENEAU];
        }
        if (!$this->hasSucceeded($candidat, Examen::TYPE_CONDUITE)) {
            return [Examen::TYPE_CONDUITE];
        }
        return [];
    }

    public function getNextExamType(Candidat $candidat): ?string
    {
        return $this->getEligibleExamTypes($candidat)[0] ?? null;
    }

    public function hasSucceeded(Candidat $candidat, string $type): bool
    {
        foreach ($candidat->getCandidatExamens() as $ce) {
            if ($ce->getExamen()?->getTypeExamen() === $type && $ce->isReussi()) {
                return true;
            }
        }
        return false;
    }

    public function getProgressionPermis(Candidat $candidat): int
    {
        $score = 0;
        foreach (self::EXAM_FLOW as $type) {
            if ($this->hasSucceeded($candidat, $type)) {
                $score += 1;
            }
        }
        return (int) round(($score / count(self::EXAM_FLOW)) * 100);
    }

    /** @return array<string,string> */
    public function getExamTimeline(Candidat $candidat): array
    {
        $timeline = [];
        foreach (self::EXAM_FLOW as $type) {
            $timeline[$type] = 'à programmer';
        }
        foreach ($candidat->getCandidatExamens() as $ce) {
            $type = $ce->getExamen()?->getTypeExamen();
            if ($type) {
                $timeline[$type] = $ce->getStatut();
            }
        }
        return $timeline;
    }

    /** @return list<string> */
    public function getWarnings(Candidat $candidat): array
    {
        $warnings = [];
        if (!$candidat->getEmail() && !$candidat->getTelephone()) {
            $warnings[] = 'Aucun canal de contact fiable n’est renseigné.';
        }
        if ($candidat->getResteAPayer() > 0) {
            $warnings[] = 'Solde permis restant : '.number_format($candidat->getResteAPayer(), 2, ',', ' ').' DZD.';
        }
        foreach ($candidat->getCandidatExamens() as $ce) {
            if ($ce->getResteAPayer() > 0) {
                $warnings[] = 'Frais examen non soldés pour '.$ce->getExamen()?->getTypeLabel().'.';
            }
            if ($ce->getExamen()?->isPast() && !$ce->isReussi() && !$ce->isEchoue()) {
                $warnings[] = 'Résultat manquant pour un examen déjà passé.';
            }
        }
        return $warnings;
    }

    /** @return array<string,mixed> */
    public function buildCandidateDossier(Candidat $candidat): array
    {
        $next = $this->getNextExamType($candidat);
        return [
            'identity' => $candidat->getNomComplet(),
            'age' => $candidat->getAge(),
            'progressionPermis' => $this->getProgressionPermis($candidat),
            'progressionPaiement' => $candidat->getProgressionPaiement(),
            'nextExam' => $next ? $this->labelType($next) : 'Parcours terminé',
            'timeline' => $this->getExamTimeline($candidat),
            'warnings' => $this->getWarnings($candidat),
            'isReadyForDriving' => $this->hasSucceeded($candidat, Examen::TYPE_CODE) && $this->hasSucceeded($candidat, Examen::TYPE_CRENEAU),
        ];
    }

    public function labelType(?string $type): string
    {
        return match ($type) {
            Examen::TYPE_CODE => 'Code',
            Examen::TYPE_CRENEAU => 'Créneau',
            Examen::TYPE_CONDUITE => 'Conduite',
            default => 'Non défini',
        };
    }

    /** @param iterable<CandidatExamen> $inscriptions */
    public function countByStatus(iterable $inscriptions): array
    {
        $stats = ['inscrit' => 0, 'payé' => 0, 'réussi' => 0, 'échoué' => 0];
        foreach ($inscriptions as $inscription) {
            $stats[$inscription->getStatut()] = ($stats[$inscription->getStatut()] ?? 0) + 1;
        }
        return $stats;
    }
}
