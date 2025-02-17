<?php namespace App\Controller;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Uid\Ulid;
use App\Entity\User;
use App\Form\UserType;
use App\Form\DeleteAccountType;
use App\Form\UserEditType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Length;


use App\Repository\UserRepository;

class UserController extends AbstractController
{ private const SCOPES = [
    'google' => ['openid', 'profile', 'email'],
    // Add other services here as needed
];
    private $tokenStorage;
    private $entityManager;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
        }
        $userExist = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);




        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
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
                return $this->redirectToRoute('app_main'); // Redirection si ADMIN
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
            if (!$user) {
                $this->addFlash('error', 'Cet email est introuvable.');
                return $this->redirectToRoute('app_forgot_password_request');
            }
    
            // Vérifier si l'utilisateur est archivé
            if ($user->isArchived()) {
                $this->addFlash('error', 'Ce compte est archivé et ne peut plus être utilisé.');
                return $this->redirectToRoute('app_login');
            }
    
            // Vérification du mot de passe
            if (!password_verify($password, $user->getPassword())) {
                $this->addFlash('error', 'Mot de passe incorrect.');
                return $this->redirectToRoute('app_login');
            }
    
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
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
    }

    #[Route('/profile/{id<\d+>}', name: 'app_profile')]
   
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

            return $this->redirectToRoute('app_profile', ['id' => $user->getId()]);
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

}