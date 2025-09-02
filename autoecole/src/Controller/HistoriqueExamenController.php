<?php

namespace App\Controller;

use App\Repository\CandidatExamenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
}
