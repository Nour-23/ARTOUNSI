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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\UserRepository;


class UserController extends AbstractController
{ private const SCOPES = [
    'google' => ['openid', 'profile', 'email'],
    'facebook' => ['public_profile', 'email'],
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

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }#[Route(path: '/login', name: 'app_login')]
    public function login(
        Request $request, 
        AuthenticationUtils $authenticationUtils, 
        UserRepository $userRepository, 
        Security $security
    ): Response {
        // ✅ Vérification si l'utilisateur est déjà connecté
        $user = $security->getUser();
        if ($user instanceof User) {
            // Si l'utilisateur est déjà connecté, redirige vers son profil
            return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
        }
    
        // Création du formulaire de connexion
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);
    
        // Récupération des erreurs et du dernier nom d'utilisateur
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
    
        // Si le formulaire est soumis et non valide
        if ($form->isSubmitted() && !$form->isValid()) {
            return $this->render('security/login.html.twig', [
                'form' => $form->createView(),
                'last_username' => $lastUsername,
                'error' => $error,
            ]);
        }
    
        // Si le formulaire est soumis
        if ($form->isSubmitted()) {
            $email = $form->get('email')->getData();
    
            // ✅ Vérification si l'email est vide (double sécurité)
            if (empty($email)) {
                $this->addFlash('error', 'Veuillez entrer une adresse email.');
                return $this->render('security/login.html.twig', [
                    'form' => $form->createView(),
                    'last_username' => $lastUsername,
                    'error' => 'Veuillez entrer une adresse email.',
                ]);
            }
    
            // ✅ Vérification si l'email existe en base
            $user = $userRepository->findOneBy(['email' => $email]);
    
            if (!$user) {
                $this->addFlash('error', 'Cet email est introuvable.');
                return $this->render('security/login.html.twig', [
                    'form' => $form->createView(),
                    'last_username' => $lastUsername,
                    'error' => 'Cet email est introuvable.',
                ]);
            }
    
            // ✅ Si la connexion est réussie, Symfony va rediriger l'utilisateur automatiquement.
            // Nous n'avons plus besoin d'un deuxième contrôle ici, puisque Symfony gère l'authentification
            return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
        }
    
        // Si rien n'est soumis, simplement afficher le formulaire
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
    
 
   
  

    #[Route('/profile/redirect/{id}', name: 'profile_redirect')]
    public function profileRedirect(User $user): Response
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            return $this->redirectToRoute('admin_dashboard');
        } elseif (in_array('ROLE_CLIENT', $roles)) {
            return $this->redirectToRoute('client_profile', ['id' => $user->getId()]);
        } elseif (in_array('ROLE_ARTISAN', $roles)) {
            return $this->redirectToRoute('artisan_profile', ['id' => $user->getId()]);
        } elseif (in_array('ROLE_COLLABORATEUR', $roles)) {
            return $this->redirectToRoute('collaborateur_profile', ['id' => $user->getId()]);
        }

        return $this->redirectToRoute('home');
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
    public function profilePrincipale(User $user): Response
    {
        return $this->render('user/profilprincipale.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/reset-password', name: 'app_request_reset_password')]
    public function requestResetPassword(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Votre email'])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                $token = Uuid::v4()->toRfc4122();
                $user->setResetToken($token);
                $expiryDate = new \DateTime();
                $expiryDate->modify('+1 hour');
                $user->setTokenExpiry($expiryDate);
                $this->entityManager->flush();

                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], true);
                $emailMessage = (new Email())
                    ->from('no-reply@yourapp.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html("Cliquez sur <a href='" . $resetUrl . "'>ce lien</a> pour réinitialiser votre mot de passe.");

                $mailer->send($emailMessage);
                $this->addFlash('success', 'Un e-mail de réinitialisation a été envoyé.');
            } else {
                $this->addFlash('error', 'L\'utilisateur avec cet e-mail n\'existe pas.');
            }
        }

        return $this->render('security/request_reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $token, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getTokenExpiry() < new \DateTime()) {
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_request_reset_password');
        }

        $form = $this->createFormBuilder()
            ->add('password', PasswordType::class, ['label' => 'Nouveau mot de passe'])
            ->add('submit', SubmitType::class, ['label' => 'Réinitialiser'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setResetToken(null);
            $user->setTokenExpiry(null);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/delete', name: 'app_delete_account', methods: ['POST'])]
    public function deleteAccount(Request $request, UserPasswordHasherInterface $passwordHasher, Security $security): Response
    {
        $currentUser = $security->getUser();
    
        if (!$currentUser instanceof User) {
            throw new \LogicException('Utilisateur non authentifié.');
        }
    
        $password = $request->request->get('password');
    
        if (!$passwordHasher->isPasswordValid($currentUser, $password)) {
            $this->addFlash('error', 'Mot de passe incorrect. Veuillez réessayer.');
            return $this->redirectToRoute('app_confirm_delete_account');
        }
    
        $this->tokenStorage->setToken(null);
        $this->entityManager->remove($currentUser);
        $this->entityManager->flush();
    
        return $this->redirectToRoute('home');
    }
    
    #[Route('/profile/confirm-delete', name: 'app_confirm_delete_account')]
public function confirmDeleteAccount(Request $request, UserPasswordHasherInterface $passwordHasher, Security $security): Response
{
    $currentUser = $security->getUser();

    if (!$currentUser instanceof User) {
        throw new \LogicException('Utilisateur non authentifié.');
    }

    $form = $this->createForm(DeleteAccountType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $password = $form->get('password')->getData();

        if (!$passwordHasher->isPasswordValid($currentUser, $password)) {
            $this->addFlash('error', 'Mot de passe incorrect. Veuillez réessayer.');
            return $this->redirectToRoute('app_confirm_delete_account');
        }

        $this->tokenStorage->setToken(null);
        $this->entityManager->remove($currentUser);
        $this->entityManager->flush();

        return $this->redirectToRoute('home');
    }

    return $this->render('user/confirm_delete.html.twig', [
        'form' => $form->createView(),
    ]);
}

}
