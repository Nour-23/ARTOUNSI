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
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        // Récupère le token d'accès via le client Google
        $accessToken = $this->fetchAccessToken($this->getGoogleClient());

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function(string $identifier) use ($accessToken) {
                $googleUser = $this->getGoogleClient()->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                // Recherche l'utilisateur en base
                $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    // Si l'utilisateur n'existe pas, le crée automatiquement
                    $user = new User();
                    $user->setGoogleId($googleId);
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
    
        // Vérifier si l'utilisateur est bien une instance de User
        if (!$user instanceof User) {
            throw new \Exception('L\'utilisateur n\'est pas valide.');
        }
    
        // Vérification de l'existence de l'ID de l'utilisateur
        $userId = $user->getId();
    
        if (empty($userId)) {
            throw new \Exception('Identifiant utilisateur manquant.');
        }
    
        return new RedirectResponse($this->router->generate('app_profile_principale', ['id' => $userId]));
    }
    


    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        // Redirige vers la page de login avec un message d'erreur
        return new RedirectResponse($this->router->generate('app_login').'?error='.urlencode($message));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Cette méthode est appelée lorsqu'une authentification est requise, mais non fournie.
        return new RedirectResponse($this->router->generate('app_login'));
    }

    private function getGoogleClient(): GoogleClient
    {
        // Assurez-vous que 'google' est bien configuré dans votre fichier de configuration OAuth2
        $client = $this->clientRegistry->getClient('google');
        
        // Vérifier que le client est bien du type GoogleClient
        if (!$client instanceof GoogleClient) {
            throw new \Exception('Le client Google n\'est pas correctement configuré.');
        }
    
        return $client;
    }
    
}
