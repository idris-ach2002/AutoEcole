<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\CandidatExamen;
use App\Form\CandidatExamenType;
use App\Repository\CandidatExamenRepository;
use App\Repository\ExamenRepository;
use App\Service\AutoEcoleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/candidat/examen')]
final class CandidatExamenController extends AbstractController
{

    public function __construct(private AutoEcoleManager $manager) {}

    #[Route(name: 'app_candidat_examen_index', methods: ['GET'])]
    public function index(CandidatExamenRepository $candidatExamenRepository): Response
    {
        return $this->render('candidat_examen/index.html.twig', [
            'candidat_examens' => $candidatExamenRepository->findAll(),
        ]);
    }



    #[Route('/new', name: 'app_candidat_examen_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $candidatExamen = new CandidatExamen();
        $form = $this->createForm(CandidatExamenType::class, $candidatExamen);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Utilisation du service injecté
            if (!$this->manager->peutPasserExamen(
                $candidatExamen->getCandidat(),
                $candidatExamen->getExamen()->getTypeExamen()
            )) {
                $this->addFlash('error', 'Le candidat ne peut pas encore passer cet examen.');
                return $this->redirectToRoute('app_candidat_examen_index');
            }

            $candidatExamen->setStatut('inscrit'); // valeur par défaut
            $em->persist($candidatExamen);
            $em->flush();

            $this->addFlash('success', 'Nouvel examen du candidat ajouté.');
            return $this->redirectToRoute('app_candidat_examen_index');
        }

        return $this->render('candidat_examen/new.html.twig', [
            'form' => $form,
        ]);
    }



    #[Route('/{id}', name: 'app_candidat_examen_show', methods: ['GET'])]
    public function show(CandidatExamen $candidatExamen): Response
    {
        return $this->render('candidat_examen/show.html.twig', [
            'candidat_examen' => $candidatExamen,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_candidat_examen_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CandidatExamenType::class, $candidatExamen);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Examen du candidat modifié.');
            return $this->redirectToRoute('app_candidat_examen_index');
        }

        return $this->render('candidat_examen/edit.html.twig', [
            'form' => $form,
            'candidat_examen' => $candidatExamen,
        ]);
    }

    #[Route('/{id}', name: 'app_candidat_examen_delete', methods: ['POST'])]
    public function delete(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$candidatExamen->getId(), $request->request->get('_token'))) {
            $em->remove($candidatExamen);
            $em->flush();
            $this->addFlash('success', 'Examen supprimé avec succès.');
        }

        return $this->redirectToRoute('app_candidat_examen_index');
    }

// Payer le permis
    #[Route('/{id}/payer-permis', name: 'app_candidat_payer_permis', methods: ['POST'])]
    public function payerPermis(Request $request, Candidat $candidat, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('payer-permis'.$candidat->getId(), $request->request->get('_token'))) {
            $montant = (float) $request->request->get('montant', 0);

            if ($montant <= 0) {
                $this->addFlash('error', 'Le montant doit être supérieur à zéro.');
            } else {
                $candidat->payer($montant);
                $em->flush();
                $this->addFlash('success', "Paiement du permis enregistré : $montant DZD. Reste à payer : ".$candidat->getResteAPayer()." DZD");
            }
        }

        return $this->redirectToRoute('app_candidat_examen_index');
    }

// Payer un examen
    #[Route('/{id}/payer-examen', name: 'app_candidat_examen_payer', methods: ['POST'])]
    public function payerExamen(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('payer-examen'.$candidatExamen->getId(), $request->request->get('_token'))) {
            $montant = (float) $request->request->get('montant', 0);

            if ($montant <= 0) {
                $this->addFlash('error', 'Le montant doit être supérieur à zéro.');
            } else {
                // On utilise la méthode payer() de l'entité pour mettre à jour le reste à payer
                $candidatExamen->payer($montant);

                $em->persist($candidatExamen);
                $em->flush();

                $this->addFlash('success', "Paiement de l'examen enregistré : $montant DZD");
            }
        }

        return $this->redirectToRoute('app_candidat_examen_index');
    }


    #[Route('/{id}/reussi', name: 'app_candidat_examen_reussi', methods: ['POST'])]
    public function reussi(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        return $this->changerStatut($request, $candidatExamen, 'réussi', 'Résultat enregistré : Réussi ✅', $em);
    }

    #[Route('/{id}/echoue', name: 'app_candidat_examen_echoue', methods: ['POST'])]
    public function echoue(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        return $this->changerStatut($request, $candidatExamen, 'échoué', 'Résultat enregistré : Échoué ❌', $em);
    }

    #[Route('/{id}/statut', name: 'app_candidat_examen_update_statut', methods: ['POST'])]
    public function updateStatut(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('statut'.$candidatExamen->getId(), $request->request->get('_token'))) {
            $nouveauStatut = $request->request->get('statut');
            if (!in_array($nouveauStatut, ['inscrit', 'payé', 'réussi', 'échoué'])) {
                $this->addFlash('error', 'Statut invalide.');
            } else {
                $candidatExamen->setStatut($nouveauStatut);
                $em->flush();
                $this->addFlash('success', "Statut mis à jour : $nouveauStatut");
            }
        }

        return $this->redirectToRoute('app_candidat_examen_show', ['id' => $candidatExamen->getId()]);
    }

    /**
     * Méthode générique pour changer le statut et afficher le flash
     */
    private function changerStatut(Request $request, CandidatExamen $candidatExamen, string $statut, string $message, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid($statut.$candidatExamen->getId(), $request->request->get('_token'))) {
            $candidatExamen->setStatut($statut);
            $em->flush();
            $this->addFlash($statut === 'échoué' ? 'error' : 'success', $message);
        }

        return $this->redirectToRoute('app_candidat_examen_show', ['id' => $candidatExamen->getId()]);
    }
}
