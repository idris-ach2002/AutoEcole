<?php

namespace App\Controller;

use App\Entity\Examen;
use App\Form\ExamenType;
use App\Repository\ExamenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/examen')]
final class ExamenController extends AbstractController
{
    #[Route(name: 'app_examen_index', methods: ['GET'])]
    public function index(Request $request, ExamenRepository $examenRepository): Response
    {
        $filters = [
            'type' => $request->query->get('type') ?: null,
            'lieu' => trim((string) $request->query->get('lieu', '')) ?: null,
            'period' => $request->query->get('period') ?: null,
        ];
        return $this->render('examen/index.html.twig', [
            'examens' => $examenRepository->findWithFilters($filters['type'], $filters['lieu'], $filters['period']),
            'filters' => $filters,
            'typeStats' => $examenRepository->countByType(),
        ]);
    }

    #[Route('/export/csv', name: 'app_examen_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, ExamenRepository $repo): StreamedResponse
    {
        $examens = $repo->findWithFilters($request->query->get('type'), $request->query->get('lieu'), $request->query->get('period'));
        $response = new StreamedResponse(function () use ($examens): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Type', 'Date', 'Lieu', 'Frais', 'Candidats inscrits', 'Passe'], ';');
            foreach ($examens as $examen) {
                fputcsv($out, [$examen->getTypeLabel(), $examen->getDatePassage()?->format('d/m/Y'), $examen->getLieu(), $examen->getFrais(), $examen->getInscritsCount(), $examen->isPast() ? 'oui' : 'non'], ';');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="examens.csv"');
        return $response;
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
            $this->addFlash('success', 'Session d’examen créée.');
            return $this->redirectToRoute('app_examen_index');
        }
        return $this->render('examen/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_examen_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Examen $examen): Response
    {
        return $this->render('examen/show.html.twig', ['examen' => $examen]);
    }

    #[Route('/{id}/edit', name: 'app_examen_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Examen $examen, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExamenType::class, $examen);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Session d’examen mise à jour.');
            return $this->redirectToRoute('app_examen_show', ['id' => $examen->getId()]);
        }
        return $this->render('examen/edit.html.twig', ['form' => $form, 'examen' => $examen]);
    }

    #[Route('/{id}', name: 'app_examen_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Examen $examen, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$examen->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($examen);
            $entityManager->flush();
            $this->addFlash('success', 'Session d’examen supprimée.');
        }
        return $this->redirectToRoute('app_examen_index');
    }
}
