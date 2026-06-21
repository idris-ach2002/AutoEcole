<?php

namespace App\Command;

use App\Entity\Candidat;
use App\Entity\CandidatExamen;
use App\Entity\Examen;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-demo', description: 'Charge un jeu de donnees de demonstration idempotent.')]
final class SeedDemoCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->em->getRepository(Candidat::class)->count([]) > 0) {
            $io->note('Jeu de données déjà présent : aucun doublon ajouté.');
            return Command::SUCCESS;
        }

        $examens = [];
        foreach ([
            [Examen::TYPE_CODE, '+7 days', 'Centre Code - Le Havre', 4500],
            [Examen::TYPE_CRENEAU, '+21 days', 'Piste manoeuvre - Montivilliers', 7000],
            [Examen::TYPE_CONDUITE, '+35 days', 'Centre permis - Le Havre', 9500],
            [Examen::TYPE_CODE, '-10 days', 'Centre Code - Harfleur', 4500],
        ] as [$type, $date, $lieu, $frais]) {
            $examen = (new Examen())
                ->setTypeExamen($type)
                ->setDatePassage(new \DateTimeImmutable($date))
                ->setLieu($lieu)
                ->setFrais($frais)
                ->setStatutExamen(false);
            $this->em->persist($examen);
            $examens[$type][] = $examen;
        }

        $candidats = [
            ['ACHABOU', 'Idris', '2002-01-05', 'idris@example.test', '06 10 20 30 40', 180000, 45000, 'A+'],
            ['MARTIN', 'Sarah', '2001-06-14', 'sarah@example.test', '06 11 22 33 44', 165000, 0, 'O+'],
            ['BENALI', 'Yanis', '2003-11-02', 'yanis@example.test', '06 55 12 98 11', 170000, 92000, 'B+'],
            ['DUPONT', 'Emma', '2004-03-21', 'emma@example.test', '06 20 21 22 23', 160000, 25000, null],
            ['ROBERT', 'Lucas', '2000-09-12', 'lucas@example.test', '06 77 88 99 00', 175000, 0, 'AB+'],
        ];

        foreach ($candidats as $idx => [$nom, $prenom, $birth, $email, $phone, $prix, $reste, $blood]) {
            $candidat = (new Candidat())
                ->setNom($nom)
                ->setPrenom($prenom)
                ->setDateNaissance(new \DateTimeImmutable($birth))
                ->setEmail($email)
                ->setTelephone($phone)
                ->setLieuNaissance('Le Havre')
                ->setGroupeSanguin($blood)
                ->setPrixPermis($prix)
                ->setResteAPayer($reste);
            $this->em->persist($candidat);

            $code = (new CandidatExamen())->setCandidat($candidat)->setExamen($examens[Examen::TYPE_CODE][$idx % 2])->setResteAPayer($idx % 2 ? 0 : 4500)->setStatut($idx < 3 ? CandidatExamen::STATUT_REUSSI : CandidatExamen::STATUT_PAYE);
            $this->em->persist($code);
            if ($idx < 3) {
                $creneau = (new CandidatExamen())->setCandidat($candidat)->setExamen($examens[Examen::TYPE_CRENEAU][0])->setResteAPayer($idx === 0 ? 3500 : 0)->setStatut($idx === 2 ? CandidatExamen::STATUT_ECHOUE : CandidatExamen::STATUT_PAYE);
                $this->em->persist($creneau);
            }
            if ($idx === 1) {
                $conduite = (new CandidatExamen())->setCandidat($candidat)->setExamen($examens[Examen::TYPE_CONDUITE][0])->setResteAPayer(9500)->setStatut(CandidatExamen::STATUT_INSCRIT);
                $this->em->persist($conduite);
            }
        }

        $this->em->flush();
        $io->success('Jeu de démonstration chargé.');
        return Command::SUCCESS;
    }
}
