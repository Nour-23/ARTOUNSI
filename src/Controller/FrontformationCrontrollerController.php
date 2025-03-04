<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use App\Repository\UserRepository;
use App\Service\TwilioService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/formationfront')]
class FrontformationCrontrollerController extends AbstractController
{
    #[Route('/', name: 'app_formation_indexfront', methods: ['GET'])]
    public function index(FormationRepository $formationRepository): Response
    {
        return $this->render('formation/indexfront.html.twig', [
            'formations' => $formationRepository->findAll(),
        ]);
    }


    #[Route('/{id}', name: 'app_formation_showfront', methods: ['GET'])]
    public function show(Formation $formation): Response
    {
        return $this->render('formation/showfront.html.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/res/{id}', name: 'reserver', methods: ['GET', 'POST'])]
    public function reserver(Formation $formation, UserRepository $userRepository, EntityManagerInterface $entityManager, TwilioService $twilioService): Response
    {
         // Replace with the static user ID you want to use
         $staticUserId = 1;
         $user = $userRepository->find($staticUserId);
 
         if (!$user) {
             throw $this->createNotFoundException('User not found.');
         }

        $formation->addUser($user);
        $user->addFormation($formation);

        $entityManager->persist($formation);
        $entityManager->persist($user);
        $entityManager->flush();

         

        $to="+21626036330";
        $body = sprintf(
            'Bonjour %s, votre réservation pour la formation "%s" a été confirmée.',
            $user->getName(),
            $formation->getNom()
        );
        $twilioService->sendSms($to,$body);

        $this->addFlash('success', 'Formation reserved successfully!');
        return $this->redirectToRoute('app_formation_indexfront');
    }

    
}
