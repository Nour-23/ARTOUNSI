<?php

namespace App\Security;

use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\NullCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserAuthAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function supports(Request $request): bool
    {
        return true; // Change en fonction de ta logique
    }

    public function authenticate(Request $request): Passport
    {
        return new Passport(
            new UserBadge('user@example.com'),
            new NullCredentials()
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new Response('Authentication successful');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('Authentication failed', Response::HTTP_FORBIDDEN);
    }

    // 🔥 Ajoute cette méthode pour définir un EntryPoint valide
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new Response('Authentication required', Response::HTTP_UNAUTHORIZED);
    }
}
