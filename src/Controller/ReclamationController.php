<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\Reclamation1Type;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
/////execl
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
//////twilio
use Twilio\Rest\Client;

#[Route('/reclamation')]
final class ReclamationController extends AbstractController
{
    #[Route(name: 'app_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamationRepository->findAll(),
            'layout' => 'base2.html.twig',  // Utilisation de base2 pour la vue index
        ]);
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(Reclamation1Type::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();
            // Configuration Twilio
            $accountSid = 'AC2a412833af249ad7d7e8248e1e57ee64';
            $authToken = 'cdcaf025fc337b60de25c351fcfe3135';
            $twilioNumber = '+12202091643';
            $recipientNumber = '+21698768815'; // Remplace par le numéro du client
    
            // Message de confirmation
            $messageBody = "Vous avez reçu une réclamation merci de bien vouloir la consulter ! ";
    
            // Envoi du SMS
            $client = new Client($accountSid, $authToken);
            $client->messages->create(
                $recipientNumber,
                [
                    'from' => $twilioNumber,
                    'body' => $messageBody
                ]
            );

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
            'layout' => 'base.html.twig',  // L'ajout reste en frontend avec base.html.twig
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(int $id, ReclamationRepository $reclamationRepository): Response
    {
        $reclamation = $reclamationRepository->find($id);

        if (!$reclamation) {
            throw $this->createNotFoundException('Reclamation not found.');
        }

        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
            'layout' => 'base2.html.twig',  // Utilisation de base2 pour la vue show
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Reclamation1Type::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
            'layout' => 'base2.html.twig',  // Utilisation de base2 pour la vue edit
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export', name: 'app_reclamation_export', methods: ['GET'])]
    public function exportToExcel(ReclamationRepository $reclamationRepository): Response
    {
        $reclamations = $reclamationRepository->findAll();
    
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reclamations');
    
        // Set header row
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Title');
        $sheet->setCellValue('C1', 'Description');
        $sheet->setCellValue('D1', 'Status');
    
        // Populate data rows
        $row = 2;
        foreach ($reclamations as $reclamation) {
            $sheet->setCellValue('A' . $row, $reclamation->getId());
            $sheet->setCellValue('B' . $row, $reclamation->getTitre());
            $sheet->setCellValue('C' . $row, $reclamation->getDescription());
            $sheet->setCellValue('D' . $row, $reclamation->getStatut());
            $row++;
        }
    
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
    
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="reclamations.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
    
        return $response;
    }
}
