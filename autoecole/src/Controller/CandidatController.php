<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\CandidatExamen;
use App\Entity\Examen;
use App\Form\CandidatType;
use App\Repository\CandidatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/candidat')]
final class CandidatController extends AbstractController
{
    #[Route(name: 'app_candidat_index', methods: ['GET'])]
    public function index(CandidatRepository $candidatRepository): Response
    {
        return $this->render('candidat/index.html.twig', [
            'candidats' => $candidatRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_candidat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidat = new Candidat();
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidat);

            // Associer automatiquement un examen de type "code" si existant
            $examenCode = $entityManager->getRepository(Examen::class)->findOneBy(['typeExamen' => 'code']);
            if ($examenCode) {
                $candidatExamen = new CandidatExamen();
                $candidatExamen->setCandidat($candidat);
                $candidatExamen->setExamen($examenCode);
                $candidatExamen->setStatut('inscrit');
                $entityManager->persist($candidatExamen);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Candidat ajouté avec succès.');
            return $this->redirectToRoute('app_candidat_index');
        }

        return $this->render('candidat/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/candidat/{id}', name: 'app_candidat_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Candidat $candidat): Response
    {
        // 🔹 Passe l'objet complet, pas son ID
        return $this->render('candidat/show.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_candidat_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Candidat modifié avec succès.');
            return $this->redirectToRoute('app_candidat_index');
        }

        return $this->render('candidat/edit.html.twig', [
            'form' => $form,
            'candidat' => $candidat,
        ]);
    }

    #[Route('/{id}', name: 'app_candidat_delete', methods: ['POST'])]
    public function delete(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$candidat->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($candidat);
            $entityManager->flush();
            $this->addFlash('success', 'Candidat supprimé avec succès.');
        }

        return $this->redirectToRoute('app_candidat_index');
    }
}
