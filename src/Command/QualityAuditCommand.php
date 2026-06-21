<?php

namespace App\Command;

use App\Repository\CandidatRepository;
use App\Service\AutoEcoleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:quality-audit', description: 'Contrôle les incohérences métier principales des dossiers candidats.')]
final class QualityAuditCommand extends Command
{
    public function __construct(private readonly CandidatRepository $candidats, private readonly AutoEcoleManager $manager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = [];
        foreach ($this->candidats->findWithFilters(null, null, null) as $candidat) {
            foreach ($this->manager->getWarnings($candidat) as $warning) {
                $rows[] = [$candidat->getId(), $candidat->getNomComplet(), $warning];
            }
        }
        if ($rows === []) {
            $io->success('Aucune incohérence bloquante détectée.');
            return Command::SUCCESS;
        }
        $io->table(['ID', 'Candidat', 'Alerte'], $rows);
        return Command::SUCCESS;
    }
}
