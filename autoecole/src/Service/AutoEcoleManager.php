<?php

namespace App\Service;

use App\Entity\Candidat;
use App\Entity\Examen;
use App\Entity\CandidatExamen;

class AutoEcoleManager
{
    /**
     * Vérifie si un candidat peut passer le prochain examen
     */
    public function peutPasserExamen(Candidat $candidat, string $typeExamen): bool
    {
        $examens = $candidat->getCandidatExamens();

        // Règles de progression
        if ($typeExamen === 'code') {
            return true; // Toujours le premier
        }

        if ($typeExamen === 'créneau') {
            return $this->aReussi($examens, 'code');
        }

        if ($typeExamen === 'conduite') {
            return $this->aReussi($examens, 'créneau');
        }

        return false;
    }

    private function aReussi(iterable $examens, string $type): bool
    {
        foreach ($examens as $ce) {
            if ($ce->getExamen()->getTypeExamen() === $type && $ce->getStatut() === 'réussi') {
                return true;
            }
        }
        return false;
    }
}
