<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/articlefront')]
final class FrontarticleController extends AbstractController
{
    #[Route(name: 'app_article_indexfront', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('article/index2.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }


    #[Route('/{id}', name: 'app_article_showfront', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show2.html.twig', [
            'article' => $article,
        ]);
    }



}