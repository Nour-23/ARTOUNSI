<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;

use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
 
    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        //Redirect to google
        return $clientRegistry->getClient('google')->redirect([], []);
    }

  
      #[Route('/connect/google/check', name:'connect_google_check')]
     
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {        $user = new User();

        if (!$this->getUser()){
return new JsonResponse(array('status' => false, 'message' => "User not found!"));
} else {
    return $this->redirectToRoute('app_profile_principale', ['id' => $user->getId()]);
}}}