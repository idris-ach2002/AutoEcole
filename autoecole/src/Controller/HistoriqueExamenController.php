<?php

namespace App\Controller;

use App\Repository\CandidatExamenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class HistoriqueExamenController extends AbstractController
{
    #[Route('/historique-examens', name: 'app_historique_examens', methods: ['GET'])]
    public function index(Request $request, CandidatExamenRepository $repo): Response
    {
        $nom = $request->query->get('nom');
        $prenom = $request->query->get('prenom');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        $candidatExamens = $repo->filtrer($nom, $prenom, $dateDebut, $dateFin);

        return $this->render('historique_examen/index.html.twig', [
            'candidat_examens' => $candidatExamens,
            'nom' => $nom,
            'prenom' => $prenom,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ]);
    }

    #[Route('/historique-examens/export/csv', name: 'app_historique_examens_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, CandidatExamenRepository $repo): Response
    {
        $candidatExamens = $repo->filtrer(
            $request->query->get('nom'),
            $request->query->get('prenom'),
            $request->query->get('dateDebut'),
            $request->query->get('dateFin')
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="historique_examens.csv"');

        $handle = fopen('php://output', 'w+');

        // En-têtes
        fputcsv($handle, ['Nom', 'Prénom', 'Email', 'Téléphone', 'Examen', 'Date', 'Lieu', 'Frais', 'Reste à payer', 'Statut'], ';');

        foreach ($candidatExamens as $ce) {
            fputcsv($handle, [
                $ce->getCandidat()->getNom(),
                $ce->getCandidat()->getPrenom(),
                $ce->getCandidat()->getEmail(),
                $ce->getCandidat()->getTelephone(),
                ucfirst($ce->getExamen()->getTypeExamen()),
                $ce->getExamen()->getDatePassage()->format('d/m/Y'),
                $ce->getExamen()->getLieu() ?? 'Non défini',
                number_format($ce->getExamen()->getFrais(), 2, ',', ' '),
                number_format($ce->getResteAPayer(), 2, ',', ' '),
                $ce->getStatut(),
            ], ';');
        }

        fclose($handle);

        return $response;
    }

    #[Route('/historique-examens/export/pdf', name: 'app_historique_examens_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, CandidatExamenRepository $repo): Response
    {
        $candidatExamens = $repo->filtrer(
            $request->query->get('nom'),
            $request->query->get('prenom'),
            $request->query->get('dateDebut'),
            $request->query->get('dateFin')
        );

        // ⚡ Ici on utilise Dompdf (facile à intégrer)
        $html = $this->renderView('historique_examen/pdf.html.twig', [
            'candidat_examens' => $candidatExamens
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="historique_examens.pdf"',
        ]);
    }
}
