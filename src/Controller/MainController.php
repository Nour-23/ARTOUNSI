<?php

namespace App\Controller;

ini_set('memory_limit', '1024M');

use App\Form\OffreType;
use App\Entity\Offre;

use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CategoryOffre;
final class MainController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }
    #[Route('/main', name: 'app_main')]
    public function index(OffreRepository $offreRepo): Response
    {
          //$offres= $this->em->getRepository(Offre::class)->findBy([], null, 50);
        return $this->render('main/index.html.twig', [
              'offres' => $offreRepo->findAll()
        ]);
    }
    #[Route('/create-offre',name: 'create_offre')]
    public function createOffre(Request $request)
    {
      $offre = new Offre();
      $form = $this->createForm(OffreType::class, $offre);
      $form ->handleRequest($request);
      
      if($form->isSubmitted()&& $form->isvalid()){

        if (!$offre->getStatus()) {
            $offre->setStatus('active'); // Valeur par défaut
        }    
    
        $this->em->persist($offre);
        $this->em->flush();
        $this->em->clear(); 

        $this->addFlash('message','Inserted successfully.');
        return $this->redirectToRoute('app_main');
      }
       
      return $this->render('main/offre.html.twig', [
       'form' =>$form->createView()
      ]);
    }
    #[Route('/edit-offre/{id}',name: 'edit-offre')]
    public function editOffre(Request $request,$id)
    {
       $offre=$this->em->getRepository(offre::class)->find($id);
       $form=$this->createForm(OffreType::class, $offre);
       $form->handleRequest($request);
       if ($form->isSubmitted()&& $form->isValid()){
        $this->em->persist($offre);
        $this->em->flush();
        $this->addFlash('message','Updated successfully.');
        return $this->redirectToRoute('app_main');

       }
       return $this->render('main/offre.html.twig',[
         'form'=>$form->createView()
       ]);
       
    }
    #[Route('/delete-offre/{id}', name: 'delete_offre', methods: ['POST', 'GET'])]
    public function deleteOffre(Request $request, $id): Response
    {
        $offre = $this->em->getRepository(Offre::class)->find($id);
        if (!$offre) {
            throw $this->createNotFoundException('Offre not found');
        }

        $this->em->remove($offre);
        $this->em->flush();

        $this->addFlash('message', 'Deleted successfully.');
        return $this->redirectToRoute('app_main');
    }
}
