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

         // ✅ Vérification si l'email existe en base
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user->isArchived()) {
                $this->addFlash('error', 'Ce compte est archivé et ne peut plus être utilisé.');
                return $this->redirectToRoute('app_login');
            }
            if (!$user) {
                $this->addFlash('error', 'Cet email est introuvable.');
                return $this->render('security/login.html.twig', [
                    'form' => $form->createView(),
                    'last_username' => $lastUsername,
                    'error' => 'Cet email est introuvable.',
                ]);
            }

        // Vérifier si le mot de passe est correct
        if (!password_verify($password, $user->getPassword())) {
            $this->addFlash('error', 'Mot de passe incorrect.');
            return $this->redirectToRoute('app_login');
        }

        // Redirection vers le profil après une connexion réussie
        return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
    }

    // Affichage du formulaire avec les erreurs
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
    #[Route('/reset-password', name: 'app_request_reset_password')]
    public function requestResetPassword(
        Request $request, 
        MailerInterface $mailer, 
        EntityManagerInterface $entityManager, 
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Votre email'])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setTokenExpiry((new \DateTime())->modify('+1 hour'));
                $entityManager->flush();

                $resetUrl = $urlGenerator->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                $emailMessage = (new Email())
                    ->from('no-reply@yourapp.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html("Cliquez sur <a href='" . $resetUrl . "'>ce lien</a> pour réinitialiser votre mot de passe."); 
                $mailer->send($emailMessage);
            }

            $this->addFlash('success', 'Si cet e-mail est enregistré, vous recevrez un lien de réinitialisation.');
        }
  return $this->render('security/request_reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        Request $request, 
        string $token, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getTokenExpiry() < new \DateTime()) {
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_request_reset_password');
        }

        $form = $this->createFormBuilder()
            ->add('password', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'constraints' => [
                    new Length(['min' => 8, 'minMessage' => 'Votre mot de passe doit contenir au moins 8 caractères.'])
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Réinitialiser'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setResetToken(null);
            $user->setTokenExpiry(null);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
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
