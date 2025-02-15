<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParticipantController extends AbstractController{
    #[Route('/participant', name: 'app_participant')]
    public function index(): Response
    {
        return $this->render('participant/form.html.twig', [
            'controller_name' => 'ParticipantController',
        ]);
    }

    #[Route('/participant/event/new', name: 'participant_event_new')]
    public function newEvent(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('form.html.twig', [
            'eventForm' => $form->createView(),
        ]);
    }
}
