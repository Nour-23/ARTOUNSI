<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\FormationRepository;
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

    
}
