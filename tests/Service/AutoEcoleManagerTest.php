<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\AutoEcoleManager;
use App\Entity\Candidat;
use App\Entity\Examen;
use App\Entity\CandidatExamen;

class AutoEcoleManagerTest extends TestCase
{
    private AutoEcoleManager $manager;

    protected function setUp(): void
    {
        $this->manager = new AutoEcoleManager();
    }

    public function testPaiementTranche()
    {
        $candidat = new Candidat();
        $candidat->setPrixPermis(1200);
        $candidat->setResteAPayer(1200);

        $candidat->payer(500);
        $this->assertEquals(700, $candidat->getResteAPayer());
        $this->assertEquals('en cours', $candidat->getStatutPaiement());

        $candidat->payer(700);
        $this->assertEquals(0, $candidat->getResteAPayer());
        $this->assertEquals('soldé', $candidat->getStatutPaiement());
        $this->assertTrue($candidat->isSolde());
    }

    public function testProgressionExamens()
    {
        $candidat = new Candidat();

        // Cas initial : peut passer code
        $this->assertTrue($this->manager->peutPasserExamen($candidat, 'code'));
        $this->assertFalse($this->manager->peutPasserExamen($candidat, 'créneau'));

        // Ajout examen code réussi
        $code = new Examen();
        $code->setTypeExamen('code');

        $ce = new CandidatExamen();
        $ce->setExamen($code);
        $ce->setStatut('réussi');

        $candidat->addCandidatExamen($ce);

        $this->assertTrue($this->manager->peutPasserExamen($candidat, 'créneau'));
        $this->assertFalse($this->manager->peutPasserExamen($candidat, 'conduite'));

        // Ajout examen créneau réussi
        $creneau = new Examen();
        $creneau->setTypeExamen('créneau');

        $ce2 = new CandidatExamen();
        $ce2->setExamen($creneau);
        $ce2->setStatut('réussi');

        $candidat->addCandidatExamen($ce2);

        $this->assertTrue($this->manager->peutPasserExamen($candidat, 'conduite'));
    }
}
