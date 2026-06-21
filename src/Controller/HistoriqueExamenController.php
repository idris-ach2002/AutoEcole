<?php

namespace App\Controller;

use App\Repository\CandidatExamenRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HistoriqueExamenController extends AbstractController
{
    #[Route('/historique-examens', name: 'app_historique_examens', methods: ['GET'])]
    public function index(Request $request, CandidatExamenRepository $repo): Response
    {
        $filters = [
            'nom' => $request->query->get('nom'),
            'prenom' => $request->query->get('prenom'),
            'dateDebut' => $request->query->get('dateDebut'),
            'dateFin' => $request->query->get('dateFin'),
        ];
        return $this->render('historique_examen/index.html.twig', [
            'candidat_examens' => $repo->filtrer($filters['nom'], $filters['prenom'], $filters['dateDebut'], $filters['dateFin']),
            ...$filters,
        ]);
    }

    #[Route('/historique-examens/export/csv', name: 'app_historique_examens_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, CandidatExamenRepository $repo): StreamedResponse
    {
        $items = $repo->filtrer($request->query->get('nom'), $request->query->get('prenom'), $request->query->get('dateDebut'), $request->query->get('dateFin'));
        $response = new StreamedResponse(function () use ($items): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nom', 'Prenom', 'Email', 'Telephone', 'Examen', 'Date', 'Lieu', 'Frais', 'Reste a payer', 'Statut'], ';');
            foreach ($items as $ce) {
                fputcsv($out, [$ce->getCandidat()->getNom(), $ce->getCandidat()->getPrenom(), $ce->getCandidat()->getEmail(), $ce->getCandidat()->getTelephone(), $ce->getExamen()->getTypeLabel(), $ce->getExamen()->getDatePassage()?->format('d/m/Y'), $ce->getExamen()->getLieu(), $ce->getExamen()->getFrais(), $ce->getResteAPayer(), $ce->getStatut()], ';');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="historique_examens.csv"');
        return $response;
    }

    #[Route('/historique-examens/export/json', name: 'app_historique_examens_export_json', methods: ['GET'])]
    public function exportJson(Request $request, CandidatExamenRepository $repo): JsonResponse
    {
        $items = $repo->filtrer($request->query->get('nom'), $request->query->get('prenom'), $request->query->get('dateDebut'), $request->query->get('dateFin'));
        $payload = [];
        foreach ($items as $ce) {
            $payload[] = [
                'candidat' => $ce->getCandidat()->getNomComplet(),
                'examen' => $ce->getExamen()->getTypeLabel(),
                'date' => $ce->getExamen()->getDatePassage()?->format('Y-m-d'),
                'lieu' => $ce->getExamen()->getLieu(),
                'statut' => $ce->getStatut(),
                'resteAPayer' => $ce->getResteAPayer(),
            ];
        }
        return $this->json($payload);
    }

    #[Route('/historique-examens/export/pdf', name: 'app_historique_examens_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, CandidatExamenRepository $repo): Response
    {
        $items = $repo->filtrer($request->query->get('nom'), $request->query->get('prenom'), $request->query->get('dateDebut'), $request->query->get('dateFin'));
        $html = $this->renderView('historique_examen/pdf.html.twig', ['candidat_examens' => $items]);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="historique_examens.pdf"',
        ]);
    }
}
