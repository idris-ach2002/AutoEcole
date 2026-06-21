<?php

namespace App\Controller;

use App\Entity\CandidatExamen;
use App\Form\CandidatExamenType;
use App\Repository\CandidatExamenRepository;
use App\Service\AutoEcoleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inscription')]
final class CandidatExamenController extends AbstractController
{
    public function __construct(private readonly AutoEcoleManager $manager) {}

    #[Route(name: 'app_candidat_examen_index', methods: ['GET'])]
    public function index(Request $request, CandidatExamenRepository $repo): Response
    {
        $filters = [
            'q' => trim((string) $request->query->get('q', '')) ?: null,
            'status' => $request->query->get('status') ?: null,
            'type' => $request->query->get('type') ?: null,
            'payment' => $request->query->get('payment') ?: null,
        ];
        $inscriptions = $repo->findWithFilters($filters['q'], $filters['status'], $filters['type'], $filters['payment']);
        return $this->render('candidat_examen/index.html.twig', [
            'candidat_examens' => $inscriptions,
            'filters' => $filters,
            'statusStats' => $this->manager->countByStatus($inscriptions),
        ]);
    }

    #[Route('/new', name: 'app_candidat_examen_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $candidatExamen = new CandidatExamen();
        $form = $this->createForm(CandidatExamenType::class, $candidatExamen);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->manager->peutPasserExamen($candidatExamen->getCandidat(), $candidatExamen->getExamen()->getTypeExamen())) {
                $this->addFlash('error', 'Progression invalide : le candidat ne peut pas encore passer cet examen.');
                return $this->redirectToRoute('app_candidat_examen_index');
            }
            $em->persist($candidatExamen);
            $em->flush();
            $this->addFlash('success', 'Inscription créée avec contrôle de progression.');
            return $this->redirectToRoute('app_candidat_examen_index');
        }
        return $this->render('candidat_examen/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_candidat_examen_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(CandidatExamen $candidatExamen): Response
    {
        return $this->render('candidat_examen/show.html.twig', ['candidat_examen' => $candidatExamen]);
    }

    #[Route('/{id}/edit', name: 'app_candidat_examen_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CandidatExamenType::class, $candidatExamen);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Inscription mise à jour.');
            return $this->redirectToRoute('app_candidat_examen_show', ['id' => $candidatExamen->getId()]);
        }
        return $this->render('candidat_examen/edit.html.twig', ['form' => $form, 'candidat_examen' => $candidatExamen]);
    }

    #[Route('/{id}/payer-examen', name: 'app_candidat_examen_payer', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function payerExamen(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('payer-examen'.$candidatExamen->getId(), (string) $request->request->get('_token'))) {
            try {
                $candidatExamen->payer((float) $request->request->get('montant', 0));
                $em->flush();
                $this->addFlash('success', 'Paiement examen enregistré.');
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }
        return $this->redirectToRoute('app_candidat_examen_index');
    }

    #[Route('/{id}/statut', name: 'app_candidat_examen_update_statut', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function updateStatut(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('statut'.$candidatExamen->getId(), (string) $request->request->get('_token'))) {
            $statut = (string) $request->request->get('statut');
            try {
                $candidatExamen->setStatut($statut);
                $em->flush();
                $this->addFlash($statut === CandidatExamen::STATUT_ECHOUE ? 'warning' : 'success', 'Statut examen mis à jour.');
            } catch (\Throwable) {
                $this->addFlash('error', 'Statut invalide.');
            }
        }
        return $this->redirectToRoute('app_candidat_examen_index');
    }

    #[Route('/{id}/delete', name: 'app_candidat_examen_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, CandidatExamen $candidatExamen, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$candidatExamen->getId(), (string) $request->request->get('_token'))) {
            $em->remove($candidatExamen);
            $em->flush();
            $this->addFlash('success', 'Inscription supprimée.');
        }
        return $this->redirectToRoute('app_candidat_examen_index');
    }
}
