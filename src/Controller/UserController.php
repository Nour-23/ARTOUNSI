<?php 
namespace App\Controller;

use App\Form\PasswordChangeProfileType;
use App\Entity\User;
use App\Form\UserType;
use App\Form\UserEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\FileUploader;
use App\Form\LoginFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;



use App\Repository\UserRepository;

class UserController extends AbstractController
{ private const SCOPES = [
    'google' => ['openid', 'profile', 'email'],
    // Add other services here as needed
];
private $passwordEncoder;


    private $tokenStorage;
    private $entityManager;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
      $this->passwordEncoder = $passwordEncoder;
        
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'email existe déjà
            $userExist = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
    
            if ($userExist) {
                if ($userExist->isArchived()) {
                    $this->addFlash('error', 'Ce compte est archivé et ne peut pas être réinscrit.');
                    return $this->redirectToRoute('app_login');
                } else {
                    $this->addFlash('error', 'Un compte avec cet email existe déjà.');
                    return $this->redirectToRoute('app_login');
                }
            }
    
            $user->setRoles(['ROLE_CLIENT']);
    
            // Traitement de la photo
            $photo = $form->get('photo')->getData();
            if ($photo) {
                try {
                    $newFilename = uniqid().'.'.$photo->guessExtension();
                    $photo->move($this->getParameter('profile_pictures_directory'), $newFilename);
                    $user->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                    return $this->render('user/register.html.twig', ['form' => $form->createView()]);
                }
            } else {
                $user->setPhoto(null);
            }
    
            // Hacher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
    
            // Générer un jeton de confirmation
            $token = bin2hex(random_bytes(32));  // Générer un jeton sécurisé
            $user->setVerificationToken($token); // Assurez-vous que vous avez ajouté ce champ dans votre entité User
    
            $this->entityManager->persist($user);
            $this->entityManager->flush();
    
            // Envoyer l'email de confirmation
          // Envoi de l'email de confirmation avec le lien de confirmation
// Envoi de l'email de confirmation avec le lien de confirmation
$email = (new TemplatedEmail())
    ->from('noreply@yourdomain.com')  // L'email de votre serveur
    ->to($user->getEmail())
    ->subject('Confirmez votre email')
    ->htmlTemplate('user/confirmation_email.html.twig')  // Créez un template pour l'email de confirmation
    ->context([
        'confirmationToken' => $token,  // Utiliser le jeton de confirmation
        'user' => $user,
        'link' => $this->generateUrl('app_confirm_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),  // Générer l'URL absolue
    ]);

$mailer->send($email);


    
            // Redirection vers la page de profil ou la page d'accueil
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/confirm_email/{token}', name: 'app_confirm_email')]
    public function confirmEmail(string $token): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);
    
        if ($user) {
            // Vérifier que le jeton est valide et confirmer l'email
            $user->setIsVerified(true);
            $user->setVerificationToken(null); // Supprimer le jeton après confirmation
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Votre email a été confirmé avec succès.');
            return $this->redirectToRoute('app_login');
        }
    
        $this->addFlash('error', 'Le lien de confirmation est invalide ou expiré.');
        return $this->redirectToRoute('app_register');
    }
        
    #[Route('/login', name: 'app_login')]
    public function login(
        Request $request, 
        AuthenticationUtils $authenticationUtils, 
        UserRepository $userRepository, 
        Security $security
    ): Response {
        // Vérifier si l'utilisateur est déjà connecté
        $user = $security->getUser(); 
        if ($user instanceof User) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('app_main'); 
            }
            return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
        }
    
        // Création du formulaire de connexion
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);
    
        // Récupération des erreurs d'authentification
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
    
        // Vérification si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $email = $form->get('email')->getData();
            $password = $form->get('password')->getData(); // Assurez-vous que votre formulaire a un champ "password"
    
            // Vérification si l'email existe en base
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user->isArchived()) {
                $this->addFlash('error', 'Ce compte est archivé et ne peut plus être utilisé.');
                return $this->redirectToRoute('app_login');
            }
            if (!$user) {
                $this->addFlash('error', 'Cet email est introuvable.');
                return $this->redirectToRoute('app_forgot_password_request');
            }
    
            // Vérification du mot de passe
            if (!password_verify($password, $user->getPassword())) {
                $this->addFlash('error', 'Mot de passe incorrect.');
                return $this->redirectToRoute('app_login');
            }
    
            // Incrémenter le compteur de connexions
            $user->setLoginCount($user->getLoginCount() + 1);
    
            // Sauvegarder les changements dans la base de données
            $this->entityManager->flush();  // Utiliser l'EntityManager injecté
    
            // ✅ Redirection selon le rôle
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('app_main'); // Redirection ADMIN
            }
            return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
        }
    
        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
    
    
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode ne contient pas de logique. Symfony intercepte automatiquement cette action
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    
 
   
  
    #[Route('/profile/redirect', name: 'profile_redirect')]
    public function profileRedirect(): Response
    {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_stats');
        }

        return $this->redirectToRoute('app_profile_principale',  ['id' => $user->getId()]);
    }

    #[Route('/profile/{id<\d+>}', name: 'app_profil')]
   
    public function profile(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
    
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
    
        return $this->render('user/profile.html.twig', [
            'user' => $user
        ]);
    }
    
    #[Route('/profile/edit/{id}', name: 'app_edit_profile')]
    public function editProfile(Request $request, User $user, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $filename = $fileUploader->upload($photoFile);
                $user->setPhoto($filename);
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('app_profil', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
    #[Route('/profile/principale/{id}', name: 'app_profile_principale')]
    public function profilePrincipale(int $id, UserRepository $userRepository): Response
    {
        // Récupérer l'utilisateur par son ID
        $user = $userRepository->find($id);
    
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
    
        // Passer l'utilisateur à la vue
        return $this->render('user/profilprincipale.html.twig', [
            'user' => $user
        ]);
    }

#[Route('/profile/archive/{id}', name: 'app_archive_user')]
public function archiveUser(User $user): Response
{
    $user->setArchived(true);
    $this->entityManager->flush();

    $this->addFlash('success', 'Le compte a été archivé avec succès.');
    return $this->redirectToRoute('app_login');
}
#[Route('/admin', name: 'admin_users')]
public function listUsers(UserRepository $userRepository): Response
{
    $users = $userRepository->findAll(); // Récupère tous les utilisateurs

    return $this->render('admin/users.html.twig', [
        'users' => $users,
    ]);
}
#[Route('/admin/profile/edit/{id}', name: 'admin_edit_profil')]
public function editadmin(Request $request, User $user, FileUploader $fileUploader): Response
{
    $form = $this->createForm(UserEditType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $photoFile = $form->get('photo')->getData();
        if ($photoFile) {
            $filename = $fileUploader->upload($photoFile);
            $user->setPhoto($filename);
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('admin_profile', ['id' => $user->getId()]);
    }

    return $this->render('admin/edit2.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
    ]);
}
#[Route('admin/profile/{id<\d+>}', name: 'admin_profile')]
   
public function adminprofile(int $id, UserRepository $userRepository): Response
{
    $user = $userRepository->find($id);

    if (!$user) {
        throw $this->createNotFoundException('Utilisateur non trouvé');
    }

    return $this->render('admin/profile2.html.twig', [
        'user' => $user
    ]);
}
#[Route('admin/profile/archive/{id}', name: 'admin_archive_user')]
public function archiveUserbyadmin(User $user): Response
{
    $user->setArchived(true);
    $this->entityManager->flush();

    $this->addFlash('success', 'Le compte a été archivé avec succès.');
    return $this->redirectToRoute('admin_users');
}
#[Route('admin/profile/principale/{id}', name: 'admin_profile_principal')]
public function profilePrincipale2(int $id, UserRepository $userRepository): Response
{
    // Récupérer l'utilisateur par son ID
    $user = $userRepository->find($id);

    if (!$user) {
        throw $this->createNotFoundException('Utilisateur non trouvé');
    }

    // Passer l'utilisateur à la vue
    return $this->render('admin/profilprincipale2.html.twig', [
        'user' => $user
    ]);
}

#[Route('/utilisateur/edition-mot-de-passe/{id}', 'user_edit_password', methods: ['GET', 'POST'])]
public function editPassword(
    User $choosenUser,
    Request $request,
    EntityManagerInterface $manager,
    UserPasswordHasherInterface $hasher,
    MailerInterface $mailer // Injecter le service Mailer
): Response {
    // Vérifier si l'utilisateur est authentifié
    if (!$this->getUser()) {
        return $this->redirectToRoute('app_login'); // Rediriger si l'utilisateur n'est pas connecté
    }

    // Vérifier si l'utilisateur authentifié correspond à l'utilisateur que l'on essaie de modifier
    $user = $this->getUser();
    if ($user !== $choosenUser) {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce mot de passe.');
    }

    $form = $this->createForm(PasswordChangeProfileType::class);

    $form->handleRequest($request);
    $progress = 0; // Valeur d'avancement par défaut

    if ($form->isSubmitted()) {
        if ($form->isValid()) {
            // Étape 1 : Vérification du mot de passe actuel
            $currentPassword = $form->get('currentPassword')->getData();
            if ($hasher->isPasswordValid($choosenUser, $currentPassword)) {
                $progress = 50; // 50% de l'avancement une fois le mot de passe vérifié

                // Étape 2 : Changer le mot de passe
                $newPassword = $form->get('newPassword')->getData();
                $encodedPassword = $hasher->hashPassword(
                    $choosenUser,
                    $newPassword
                );
                $choosenUser->setPassword($encodedPassword);

                $manager->persist($choosenUser);
                $manager->flush();

                // Étape 3 : Envoyer un email de confirmation
                $email = (new Email())
                    ->from('no-reply@votresite.com')
                    ->to($choosenUser->getEmail())
                    ->subject('Votre mot de passe a été modifié')
                    ->text('Votre mot de passe a été changé avec succès. Si vous n\'êtes pas à l\'origine de cette demande, veuillez contacter notre support.');

                $mailer->send($email);

                $progress = 100; // 100% une fois l'email envoyé

                $this->addFlash('success', 'Le mot de passe a été modifié et un email vous a été envoyé.');

                return $this->redirectToRoute('app_profile', ['id' => $choosenUser->getId()]);
            } else {
                $progress = 0; // Si le mot de passe est incorrect, l'avancement reste à 0
                $this->addFlash('warning', 'Le mot de passe actuel est incorrect.');

                // Envoyer un email d'alerte pour indiquer l'erreur
                $email = (new Email())
                    ->from('no-reply@votresite.com')
                    ->to($choosenUser->getEmail())
                    ->subject('Tentative de modification de mot de passe échouée')
                    ->text('Une tentative de modification de votre mot de passe a échoué. Assurez-vous que vos informations sont correctes.');

                $mailer->send($email);
            }
        } else {
            $progress = 25; // 25% si le formulaire est soumis mais pas valide
            $this->addFlash('warning', 'Veuillez remplir tous les champs correctement.');
        }
    }

    return $this->render('user/change.html.twig', [
        'form' => $form->createView(),
        'progress' => $progress,  // Transmettre l'avancement à la vue
    ]);
}

#[Route('/admin/stats', name: 'admin_stats')]public function index(UserRepository $userRepository, EntityManagerInterface $em)
{
    // Récupérer tous les utilisateurs
    $users = $userRepository->findAll();

    // Vérifier s'il y a des utilisateurs
    if (empty($users)) {
        return $this->render('admin/stats.html.twig', [
            'users' => $users,
            'totalConnections' => 0,
            'userWithMaxLogin' => null,
            'userWithMinLogin' => null,
            'averageLoginCount' => 0,
        ]);
    }

    // Calculer le total des connexions
    $totalConnections = array_sum(array_map(function ($user) {
        return $user ? $user->getLoginCount() : 0;
    }, $users));

    // Utilisateur avec le plus de connexions
    $userWithMaxLogin = array_reduce($users, function ($maxUser, $user) {
        if ($user && (!$maxUser || $user->getLoginCount() > $maxUser->getLoginCount())) {
            return $user;
        }
        return $maxUser;
    });

    // Utilisateur avec le moins de connexions
    $userWithMinLogin = array_reduce($users, function ($minUser, $user) {
        if ($user && (!$minUser || $user->getLoginCount() < $minUser->getLoginCount())) {
            return $user;
        }
        return $minUser;
    });

    // Archivage des utilisateurs avec 0 ou 1 connexion
    foreach ($users as $user) {
        if ($user->getLoginCount() <= 1) {
            $user->setArchived(true);
            $em->persist($user);
        }
    }
    $em->flush();

    // Calculer la moyenne des connexions
    $averageLoginCount = count($users) > 0 ? $totalConnections / count($users) : 0;

    return $this->render('admin/stats.html.twig', [
        'users' => $users,
        'totalConnections' => $totalConnections,
        'userWithMaxLogin' => $userWithMaxLogin,
        'userWithMinLogin' => $userWithMinLogin,
        'averageLoginCount' => $averageLoginCount,
    ]);
}

public function incrementLoginCount(EntityManagerInterface $em): Response
{
    // Récupérer un utilisateur, par exemple l'utilisateur avec id=1
    $user = $em->getRepository(User::class)->find(1);

    if ($user) {
        // Incrémenter manuellement le compteur de connexions
        $user->setLoginCount($user->getLoginCount() + 1);
        $em->persist($user);
        $em->flush();

        return new Response('Login count incremented to: ' . $user->getLoginCount());
    }

    return new Response('User not found');
}
// Contrôleur pour désarchiver un utilisateur
#[Route('/unarchive-user/{id}', name: 'unarchive_user')]
public function unarchiveUser(User $user, EntityManagerInterface $em): Response
{
    // Vérifier que l'utilisateur existe et est archivé
    if (!$user || !$user->isArchived()) {
        $this->addFlash('error', 'Cet utilisateur n\'est pas archivé.');
        return $this->redirectToRoute('admin_users');
    }

    // Désarchiver l'utilisateur
    $user->setArchived(false); // Désarchiver
    $em->persist($user);
    $em->flush();

    // Message de succès et redirection
    $this->addFlash('success', 'L\'utilisateur a été désarchivé avec succès.');
    return $this->redirectToRoute('admin_users'); // Rediriger après l'action
}

#[Route('/user/inactive/{id}', name: 'inactive_user')]
public function setInactive(User $user, EntityManagerInterface $em): Response
{
    // Vérifier si l'utilisateur existe
    if (!$user) {
        $this->addFlash('error', 'Utilisateur introuvable.');
        return $this->redirectToRoute('admin_users');
    }

    // Marquer l'utilisateur comme inactif
    $user->setIsActive(false); // Mettre l'utilisateur en inactif
    $em->persist($user);
    $em->flush();

    // Message de succès et redirection
    $this->addFlash('success', 'L\'utilisateur a été marqué comme inactif.');
    return $this->redirectToRoute('admin_user_stats'); // Rediriger vers les stats
}

#[Route('/profile/admin', name: 'app_profile_admin')]
public function profilAdmin(Security $security): Response
{
    // Récupérer l'utilisateur actuellement connecté
    $user = $security->getUser();

    // Vérifier si l'utilisateur est bien connecté
    if (!$user) {
        throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
    }

    // Passer l'utilisateur à la vue
    return $this->render('user/profiladmin.html.twig', [
        'user' => $user
    ]);
}

}