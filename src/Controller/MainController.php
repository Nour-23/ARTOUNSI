<?php

namespace App\Controller;

ini_set('memory_limit', '1024M');

use App\Form\OffreType;
use App\Entity\Offre;
use App\Entity\Comment;
use App\Repository\OffreRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\UserRepository;

use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CategoryOffre;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\EmailNotificationService; 
use App\Service\DialogflowService;
final class MainController extends AbstractController
{
    private $em;
    private Security $security;
    private $emailNotificationService; 
    private $dialogflowService; 
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack,EntityManagerInterface $em, EmailNotificationService $emailNotificationService, DialogflowService $dialogflowService)
{
    $this->em = $em;
    $this->requestStack = $requestStack;
    $this->emailNotificationService = $emailNotificationService;  // Stocke le service
    $this->dialogflowService = $dialogflowService;   
    
}
#[Route('/main', name: 'app_main')]
public function index(Request $request, OffreRepository $offreRepo, EntityManagerInterface $em, PaginatorInterface $paginator): Response
{
    ini_set('max_execution_time', 60); // ⏳ Augmente le temps d'exécution
    
    // Récupérer toutes les catégories
    $categories = $em->getRepository(CategoryOffre::class)->findAll();
    
    // Récupérer les critères de recherche
    $searchTitle = $request->query->get('title');
    $searchCategory = $request->query->get('category');
    $searchStatus = $request->query->get('status');

    // Filtrer les offres en fonction des critères de recherche
    $query = $offreRepo->createQueryBuilder('o');

    if ($searchTitle) {
        $query->andWhere('o.title LIKE :title')
              ->setParameter('title', '%' . $searchTitle . '%');
    }

    if ($searchCategory) {
        $query->andWhere('o.category = :category')
              ->setParameter('category', $searchCategory);
    }

    if ($searchStatus) {
        $query->andWhere('o.status = :status')
              ->setParameter('status', $searchStatus);
    }

    // Paginer les résultats
    $offres = $paginator->paginate(
        $query->getQuery(),
        $request->query->getInt('page', 1),
        5  // Nombre d'offres par page
    );

    if ($request->isXmlHttpRequest()) {
        // Si la requête est AJAX, renvoyer uniquement le contenu HTML des offres
        return $this->render('main/_offres_table.html.twig', [
            'offres' => $offres
        ]);
    }

    // Statistiques
    $totalOffres = $offreRepo->count([]); 
    $activeOffres = $offreRepo->count(['status' => 'active']); 
    $archivedOffres = $offreRepo->count(['status' => 'archivé']); 
    
    return $this->render('main/index.html.twig', [
        'offres' => $offres,
        'category_offres' => $categories,
        'totalOffres' => $totalOffres,
        'activeOffres' => $activeOffres,
        'archivedOffres' => $archivedOffres,
        'searchTitle' => $searchTitle,
        'searchCategory' => $searchCategory,
        'searchStatus' => $searchStatus
    ]);
}

    
    #[Route('/create-offre/{useDialogflow}', name: 'create_offre', defaults: ['useDialogflow' => true])]
    public function createOffre(Request $request, bool $useDialogflow)
    {
        $dialogflowResponse = $this->dialogflowService->startConversation("Donne-moi un exemple d’offre d’emploi"); 
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
        // Une fois l'offre insérée, tu peux envoyer une confirmation via Dialogflow
        $this->dialogflowService->sendMessage("Your offer has been created successfully!");
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
        'form' => $form->createView(),
        'dialogflowMessage' => $dialogflowResponse // Afficher le message de Dialogflow si besoin
    ]);
}

    #[Route('/create-offre1',name: 'create_offre1')]
    public function createOffre1(Request $request)
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
       
      return $this->render('main/offre1.html.twig', [
       'form' =>$form->createView()
      ]);
    }
    #[Route('/edit-offre/{id}',name: 'edit-offre')]
    public function editOffre(Request $request,$id)
    {
       $offre=$this->em->getRepository(Offre::class)->find($id);
       $form=$this->createForm(OffreType::class, $offre);
       $form->handleRequest($request);
       if ($form->isSubmitted()&& $form->isValid()){
        $this->em->persist($offre);
        $this->em->flush();
        $this->addFlash('message','Updated successfully.');
        return $this->redirectToRoute('app_main');

       }
       return $this->render('main/offre.html.twig',[
         'form'=>$form->createView(),
         'dialogflowMessage' => ''
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

#[Route('/front', name: 'asma')]
public function front(OffreRepository $offreRepo): Response
{
    $offres = $offreRepo->findAll(); // Récupérer les offres actives

    return $this->render('main/front.html.twig', [
        'offres' => $offres
    ]);
}
#[Route('/offrefront/{id}', name: 'frontshow')]
public function showfront(Offre $offre,CommentRepository $commentRepo): Response
{   
    $comments=$offre->getComments();
    return $this->render('main/Frontshow.html.twig', [
        'offre' => $offre,
        'comments'=>$comments,
    ]);
}
#[Route('/search-offres', name: 'search_offres', methods: ['GET'])]
public function list(Request $request, OffreRepository $offreRepository, PaginatorInterface $paginator):JsonResponse
{
    $searchTitle = $request->query->get('title');
    $searchCategory = $request->query->get('category');
    $searchStatus = $request->query->get('status');

    $offres = $offreRepository->searchOffres($searchTitle, $searchCategory, $searchStatus);

    // Paginer les résultats
    dump($query->getSQL());
     die();
    $offres = $paginator->paginate(
        $query, 
        $request->query->getInt('page', 1), 
        5 // Nombre d'offres par page
    );

    return $this->json([
        'offres' => array_map(fn($offre) => [
            'id' => $offre->getId(),
            'title' => $offre->getTitle(),
            'category' => $offre->getCategory()->getName(),
            'status' => $offre->getStatus(),
        ], $offres)
    ]);
}


#[Route('/addcomment/{id}', name: 'add_comment', methods: ['GET', 'POST'])]
    public function addcomment(
        Offre $offre, 
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserRepository $userRepo
    ): Response {

        // Récupérer l'email depuis la session
        $session = $this->requestStack->getSession();
        $email = $session->get('WSS_EMAIL'); 

        // Vérifier si l'utilisateur existe en base de données
        $user = $userRepo->findOneBy(['email' => $email]);

        

        // Récupérer le texte du commentaire
        $text = $request->get('text');

        if (empty($text)) {
            throw new \InvalidArgumentException('Le commentaire ne peut pas être vide.');
        }

        // Créer et associer le commentaire
        $comment = new Comment();
        $comment->setUser($user);
        $comment->setText($text);
        $comment->setOffre($offre);
        
        

        // Sauvegarder en base
        $entityManager->persist($comment);
        $entityManager->flush();

        // Redirection vers la page de l'offre
        return $this->redirectToRoute('frontshow', ['id' => $offre->getId()]);
    }
}