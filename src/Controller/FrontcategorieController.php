<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categoriefront')]
final class FrontcategorieController extends AbstractController
{
    #[Route(name: 'app_categorie_indexfront', methods: ['GET'])]
    public function index(CategorieRepository $categorieRepository): Response
    {
        return $this->render('categorie/index2.html.twig', [
            'categories' => $categorieRepository->findAll(),
        ]);
    }
 
    #[Route('/{id}', name: 'app_categorie_showfront', methods: ['GET'])]
    public function show(Categorie $categorie): Response
    {
        return $this->render('categorie/show2.html.twig', [
            'categorie' => $categorie,
        ]);
    }
    
}
