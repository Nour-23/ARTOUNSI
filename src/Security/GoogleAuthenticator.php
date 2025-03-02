<?php
namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $em;
    private RouterInterface $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $em, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        // L'authentificateur ne s'exécute que sur la route de callback de Google.
        return $request->getPathInfo() === '/connect/google/check' && $request->isMethod('GET');
    }

    public function getCredentials(Request $request)
    {
        // this method is only called if supports() returns true
        return $this->fetchAccessToken($this->getGoogleClient());
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $googleUser = $this->getGoogleClient()->fetchUserFromToken($credentials);
        $email = $googleUser->getEmail();

        // 2) do we have a matching user by email?
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        // 3) Maybe you just want to "register" them by creating
        // a User object
        if (!$user) {
            $user = new User();
            $user->setGoogleId($googleUser->getId());
            $user->setEmail($email);
            $user->setName($googleUser->getName());
            $user->setPhoto($googleUser->getAvatar());
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        // Récupère le token d'accès via le client Google
        $accessToken = $this->fetchAccessToken($this->getGoogleClient());

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function (string $identifier) use ($accessToken) {
                $googleUser = $this->getGoogleClient()->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                // Recherche l'utilisateur en base
                $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    // Si l'utilisateur n'existe pas, le crée automatiquement
                    $user = new User();
                    $user->setGoogleId($googleUser->getId());
                    $user->setEmail($email);
                    $user->setName($googleUser->getName());
                    $user->setPhoto($googleUser->getAvatar());

                    $this->em->persist($user);
                    $this->em->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            throw new \Exception('L\'utilisateur n\'est pas valide.');
        }

        $userId = $user->getId();

        if (empty($userId)) {
            throw new \Exception('Identifiant utilisateur manquant.');
        }

        return new RedirectResponse($this->router->generate('app_profile_principale', ['id' => $userId]));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        return new RedirectResponse($this->router->generate('app_login') . '?error=' . urlencode($message));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }

    private function getGoogleClient(): GoogleClient
    {
        $client = $this->clientRegistry->getClient('google');

        if (!$client instanceof GoogleClient) {
            throw new \Exception('Le client Google n\'est pas correctement configuré.');
        }

        return $client;
    }
}
