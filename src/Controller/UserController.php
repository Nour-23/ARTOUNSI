<?php namespace App\Controller;

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

class UserController extends AbstractController
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photo')->getData();
            if ($photo) {
                $newFilename = uniqid().'.'.$photo->guessExtension();
                $photo->move($this->getParameter('profile_pictures_directory'), $newFilename);
                $user->setPhoto($newFilename);
            } else {
                $user->setPhoto(null);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(LoginFormType::class);

        return $this->render('user/sign-in.html.twig', [
            'form' => $form->createView(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
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

    #[Route('/profile/{id}', name: 'app_profile')]
    public function profile(User $user): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit/{id}', name: 'app_edit_profile')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, User $user, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $filename = $fileUploader->upload($photoFile);
                $user->setPhoto($filename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_profile', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/profile/principale/{id}', name: 'app_profile_principale')]
    public function profileprincipale(User $user): Response
    {
        return $this->render('user/profilprincipale.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/reset-password/{id}', name: 'app_reset_password')]
    public function resetPassword(User $user): Response
    {
        return $this->render('user/reset_password.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/delete/{id}', name: 'app_delete_account', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        User $user,
        Security $security
    ): Response {
        $password = $request->request->get('password');  // Get password from form
    
        // Ensure the current user is of the correct type
        $currentUser = $security->getUser();
        if ($currentUser instanceof PasswordAuthenticatedUserInterface) {
            if (!$passwordHasher->isPasswordValid($currentUser, $password)) {
                $this->addFlash('error', 'Mot de passe incorrect. Veuillez réessayer.');
                return $this->redirectToRoute('app_confirm_delete_account', ['id' => $user->getId()]);
            }
        } else {
            throw new \LogicException('The user is not authenticated correctly.');
        }
    
        // Disconnect user before deleting the account
        $this->tokenStorage->setToken(null);
    
        // Delete user from database
        $entityManager->remove($user);
        $entityManager->flush();
    
        return $this->redirectToRoute('home');
    }
    
    #[Route('/profile/confirm-delete/{id}', name: 'app_confirm_delete_account')]
    public function confirmDeleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        User $user,
        Security $security
    ): Response {
        // Create form to confirm account deletion
        $form = $this->createForm(DeleteAccountType::class);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();  // Get password from form
    
            // Ensure the current user is of the correct type
            $currentUser = $security->getUser();
            if ($currentUser instanceof PasswordAuthenticatedUserInterface) {
                if (!$passwordHasher->isPasswordValid($currentUser, $password)) {
                    $this->addFlash('error', 'Mot de passe incorrect. Veuillez réessayer.');
                    return $this->redirectToRoute('app_confirm_delete_account', ['id' => $user->getId()]);
                }
            } else {
                throw new \LogicException('The user is not authenticated correctly.');
            }
    
            // Disconnect user before deleting the account
            $this->tokenStorage->setToken(null);
    
            // Delete user from database
            $entityManager->remove($user);
            $entityManager->flush();
    
            return $this->redirectToRoute('home');
        }
    
        return $this->render('user/confirm_delete.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
    
}
