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
use Knp\Component\Pager\PaginatorInterface;
use App\Service\EmailNotificationService; 
final class MainController extends AbstractController
{
    private $em;
    private $emailNotificationService;
    public function __construct(EntityManagerInterface $em,EmailNotificationService $emailNotificationService){
        $this->em = $em;
        $this->emailNotificationService = $emailNotificationService;  // Stocker le service
    }
    #[Route('/main', name: 'app_main')]
    #[Route('/main', name: 'app_main')]
    #[Route('/main', name: 'app_main')]
    public function index(Request $request, OffreRepository $offreRepo, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        // Récupérer toutes les catégories
        $categories = $em->getRepository(CategoryOffre::class)->findAll();
        
        // Récupérer les valeurs des filtres de la requête
        $searchTitle = $request->query->get('title');
        $searchCategory = $request->query->get('category');
        $searchStatus = $request->query->get('status');
        
        // Filtrer les offres en fonction des paramètres de recherche
        $query = $offreRepo->searchOffers($searchTitle, $searchCategory, $searchStatus);
        
        // Paginater les résultats
        $offres = $paginator->paginate(
            $query,  // QueryBuilder ou DQL
            $request->query->getInt('page', 1), // Numéro de la page (par défaut 1)
            5  // Nombre d'offres par page
        );
    
        // Statistiques
        $totalOffres = $offreRepo->count([]); // Nombre total d'offres
        $activeOffres = $offreRepo->count(['status' => 'active']); // Nombre d'offres actives
        $archivedOffres = $offreRepo->count(['status' => 'archivé']); // Nombre d'offres archivées
        
        // Passer les statistiques à la vue
        return $this->render('main/index.html.twig', [
            'offres' => $offres,  // Offres paginées
            'category_offres' => $categories,
            'searchTitle' => $searchTitle,
            'searchCategory' => $searchCategory,
            'searchStatus' => $searchStatus,
            'totalOffres' => $totalOffres,          // Passer le total des offres
            'activeOffres' => $activeOffres,        // Passer le nombre d'offres actives
            'archivedOffres' => $archivedOffres,    // Passer le nombre d'offres archivées
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
       // Envoi de l'email après l'insertion de l'offre
       $this->emailNotificationService->sendOfferNotification(
        'asmabaalouch6@gmail.com',  // L'adresse à qui envoyer l'email
        'New Offer Created',
        'A new offer has been created and is now live on the platform.'  // Contenu du message
    );
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
public function front(): Response
{
    return $this->render('main/front.html.twig');
}

#[Route('/offre', name: 'app_offre')]
public function list(Request $request, OffreRepository $offreRepository, PaginatorInterface $paginator)
{
    $searchTitle = $request->query->get('title');
    $searchCategory = $request->query->get('category');
    $searchStatus = $request->query->get('status');

    // Appeler la méthode de recherche avec les paramètres
    $query = $offreRepository->searchOffers($searchTitle, $searchCategory, $searchStatus);

    // Paginer les résultats
    $offres = $paginator->paginate(
        $query, 
        $request->query->getInt('page', 1), 
        5 // Nombre d'offres par page
    );

    return $this->render('main/index.html.twig', [
        'offres' => $offresActives,  // ✅ Affiche uniquement les offres actives
        'category_offres' => $categories,
        'searchTitle' => $searchTitle,   // Ajoutez cette ligne
        'searchCategory' => $searchCategory,  // Ajoutez cette ligne
        'searchStatus' => $searchStatus,  // Ajoutez cette ligne
    ]);    
}

}
