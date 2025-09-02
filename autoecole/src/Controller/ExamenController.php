<?php

namespace App\Controller;

use App\Entity\Examen;
use App\Form\ExamenType;
use App\Repository\ExamenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/examen')]
final class ExamenController extends AbstractController
{
    #[Route(name: 'app_examen_index', methods: ['GET'])]
    public function index(ExamenRepository $examenRepository): Response
    {
        return $this->render('examen/index.html.twig', [
            'examens' => $examenRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_examen_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $examen = new Examen();
        $form = $this->createForm(ExamenType::class, $examen);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($examen);
            $entityManager->flush();

            $this->addFlash('success', 'Nouvel examen créé.');
            return $this->redirectToRoute('app_examen_index');
        }

        return $this->render('examen/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_examen_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, ExamenRepository $examenRepository): Response
    {
        $examen = $examenRepository->find($id);

        if (!$examen) {
            throw $this->createNotFoundException('Examen non trouvé.');
        }

        return $this->render('examen/show.html.twig', [
            'examen' => $examen,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_examen_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Examen $examen, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExamenType::class, $examen);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Examen mis à jour.');
            return $this->redirectToRoute('app_examen_index');
        }

        return $this->render('examen/edit.html.twig', [
            'form' => $form,
            'examen' => $examen,
        ]);
    }

    #[Route('/{id}', name: 'app_examen_delete', methods: ['POST'])]
    public function delete(Request $request, Examen $examen, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$examen->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($examen);
            $entityManager->flush();
            $this->addFlash('success', 'Examen supprimé avec succès.');
        }

        return $this->redirectToRoute('app_examen_index');
    }
}
