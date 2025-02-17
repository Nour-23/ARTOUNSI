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
    public function index(OffreRepository $offreRepo, EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(CategoryOffre::class)->findAll();
        
        // ✅ Filtrer pour récupérer uniquement les offres actives
        $offresActives = $offreRepo->findBy(['status' => 'active']);
    
        return $this->render('main/index.html.twig', [
            'offres' => $offresActives,  // ✅ Affiche uniquement les offres actives
            'category_offres' => $categories
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
    #[Route('/archive-offre/{id}', name: 'archive_offre', methods: ['POST', 'GET'])]
    public function archiveOffre(Request $request, $id): Response
    {
        $offre = $this->em->getRepository(Offre::class)->find($id);
        if (!$offre) {
            throw $this->createNotFoundException('Offre not found');
        }
    
        $offre->setStatus('archivé'); // On change le statut à "archivé"
        $this->em->flush();
    
        $this->addFlash('message', 'L\'offre a été archivée.');
        return $this->redirectToRoute('app_main');
    }
    #[Route('/offres-archivees', name: 'liste_offres_archivees')]
public function listeOffresArchivees(OffreRepository $offreRepo): Response
{
    $offresArchivees = $offreRepo->findBy(['status' => 'archivé']); // Récupérer seulement les offres archivées

    return $this->render('main/archives.html.twig', [
        'offres' => $offresArchivees
    ]);
}
#[Route('/offre/{id}', name: 'app_offre_show')]
public function show(Offre $offre): Response
{
    return $this->render('main/show.html.twig', [
        'offre' => $offre,
    ]);
}
#[Route('/restore-offre/{id}', name: 'restore_offre', methods: ['POST', 'GET'])]
public function restoreOffre(Request $request, $id): Response
{
    $offre = $this->em->getRepository(Offre::class)->find($id);
    if (!$offre) {
        throw $this->createNotFoundException('Offre non trouvée');
    }

    // Changer le statut en "active"
    $offre->setStatus('active');
    $this->em->flush();

    $this->addFlash('message', 'L\'offre a été restaurée.');
    return $this->redirectToRoute('app_main');
}
#[Route('/asma', name: 'asma')]
public function asma(): Response
{
    return $this->render('main/asma.html.twig' 
    );
}

#[Route('/front', name: 'asma')]
public function front(OffreRepository $offreRepo, EntityManagerInterface $em): Response
{
    $categories = $em->getRepository(CategoryOffre::class)->findAll();
    $offresActives = $offreRepo->findBy(['status' => 'active']);

    return $this->render('main/front.html.twig', [
        'offres' => $offresActives,  
        'category_offres' => $categories
    ]);
}
 
}
