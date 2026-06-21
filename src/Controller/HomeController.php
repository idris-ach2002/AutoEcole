<?php

namespace App\Controller;

use App\Repository\CandidatExamenRepository;
use App\Repository\CandidatRepository;
use App\Repository\ExamenRepository;
use App\Service\AutoEcoleManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(CandidatRepository $candidats, ExamenRepository $examens, CandidatExamenRepository $inscriptions, AutoEcoleManager $manager): Response
    {
        $candidateFinancials = $candidats->getFinancialStats();
        $examFinancials = $inscriptions->getFinancialStats();
        $allInscriptions = $inscriptions->findWithFilters(null, null, null, null);

        return $this->render('home/index.html.twig', [
            'candidateFinancials' => $candidateFinancials,
            'examFinancials' => $examFinancials,
            'statusStats' => $manager->countByStatus($allInscriptions),
            'examTypeStats' => $examens->countByType(),
            'upcomingExams' => $examens->findUpcoming(6),
            'paymentAlerts' => $candidats->findPaymentAlerts(),
            'resultAlerts' => $inscriptions->findResultAlerts(),
        ]);
    }

    #[Route('/healthz', name: 'app_healthz', methods: ['GET'])]
    public function healthz(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'service' => 'autoecole',
            'checkedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }
}
