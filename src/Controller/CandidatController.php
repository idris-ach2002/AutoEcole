<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\CandidatExamen;
use App\Entity\Examen;
use App\Form\CandidatType;
use App\Repository\CandidatRepository;
use App\Service\AutoEcoleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/candidat')]
final class CandidatController extends AbstractController
{
    #[Route(name: 'app_candidat_index', methods: ['GET'])]
    public function index(Request $request, CandidatRepository $candidatRepository, AutoEcoleManager $manager): Response
    {
        $filters = [
            'q' => trim((string) $request->query->get('q', '')) ?: null,
            'paymentStatus' => $request->query->get('paymentStatus') ?: null,
            'bloodGroup' => $request->query->get('bloodGroup') ?: null,
            'sort' => (string) $request->query->get('sort', 'nom'),
            'direction' => (string) $request->query->get('direction', 'ASC'),
        ];
        $candidats = $candidatRepository->findWithFilters($filters['q'], $filters['paymentStatus'], $filters['bloodGroup'], $filters['sort'], $filters['direction']);

        return $this->render('candidat/index.html.twig', [
            'candidats' => $candidats,
            'filters' => $filters,
            'manager' => $manager,
            'financials' => $candidatRepository->getFinancialStats(),
        ]);
    }

    #[Route('/export/csv', name: 'app_candidat_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, CandidatRepository $repo, AutoEcoleManager $manager): StreamedResponse
    {
        $candidats = $repo->findWithFilters(
            $request->query->get('q'),
            $request->query->get('paymentStatus'),
            $request->query->get('bloodGroup'),
            (string) $request->query->get('sort', 'nom'),
            (string) $request->query->get('direction', 'ASC')
        );

        $response = new StreamedResponse(function () use ($candidats, $manager): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nom', 'Prenom', 'Age', 'Email', 'Telephone', 'Prix permis', 'Reste a payer', 'Paiement', 'Progression examens', 'Prochain examen'], ';');
            foreach ($candidats as $candidat) {
                fputcsv($out, [
                    $candidat->getNom(),
                    $candidat->getPrenom(),
                    $candidat->getAge(),
                    $candidat->getEmail() ?: '',
                    $candidat->getTelephone() ?: '',
                    $candidat->getPrixPermis(),
                    $candidat->getResteAPayer(),
                    $candidat->getStatutPaiement(),
                    $manager->getProgressionPermis($candidat).'%',
                    $manager->labelType($manager->getNextExamType($candidat)),
                ], ';');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="candidats.csv"');
        return $response;
    }

    #[Route('/export/json', name: 'app_candidat_export_json', methods: ['GET'])]
    public function exportJson(CandidatRepository $repo, AutoEcoleManager $manager): JsonResponse
    {
        $payload = [];
        foreach ($repo->findWithFilters(null, null, null) as $candidat) {
            $payload[] = $manager->buildCandidateDossier($candidat) + [
                'id' => $candidat->getId(),
                'email' => $candidat->getEmail(),
                'telephone' => $candidat->getTelephone(),
                'resteAPayer' => $candidat->getResteAPayer(),
            ];
        }
        return $this->json($payload);
    }

    #[Route('/new', name: 'app_candidat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidat = new Candidat();
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidat);
            $examenCode = $entityManager->getRepository(Examen::class)->findOneBy(['typeExamen' => Examen::TYPE_CODE]);
            if ($examenCode) {
                $candidatExamen = (new CandidatExamen())->setCandidat($candidat)->setExamen($examenCode)->setStatut(CandidatExamen::STATUT_INSCRIT);
                $entityManager->persist($candidatExamen);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Candidat ajouté avec dossier initialisé.');
            return $this->redirectToRoute('app_candidat_show', ['id' => $candidat->getId()]);
        }

        return $this->render('candidat/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_candidat_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Candidat $candidat, AutoEcoleManager $manager): Response
    {
        return $this->render('candidat/show.html.twig', [
            'candidat' => $candidat,
            'dossier' => $manager->buildCandidateDossier($candidat),
            'manager' => $manager,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_candidat_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Dossier candidat mis à jour.');
            return $this->redirectToRoute('app_candidat_show', ['id' => $candidat->getId()]);
        }
        return $this->render('candidat/edit.html.twig', ['form' => $form, 'candidat' => $candidat]);
    }

    #[Route('/{id}/payer-permis', name: 'app_candidat_payer_permis', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function payerPermis(Request $request, Candidat $candidat, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('payer-permis'.$candidat->getId(), (string) $request->request->get('_token'))) {
            try {
                $candidat->payer((float) $request->request->get('montant', 0));
                $em->flush();
                $this->addFlash('success', 'Paiement permis enregistré.');
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }
        return $this->redirectToRoute('app_candidat_show', ['id' => $candidat->getId()]);
    }

    #[Route('/{id}', name: 'app_candidat_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$candidat->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($candidat);
            $entityManager->flush();
            $this->addFlash('success', 'Candidat supprimé avec succès.');
        }
        return $this->redirectToRoute('app_candidat_index');
    }
}
