<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/participation')]
class ParticipationController extends AbstractController
{
    // Afficher tous les événements et permettre au participant de choisir d'y participer
    #[Route('/events', name: 'participation_events', methods: ['GET'])]
    public function events(EventRepository $eventRepository): Response
    {
        // Récupérer tous les événements disponibles
        $events = $eventRepository->findAll();

        return $this->render('participation/events.html.twig', [
            'events' => $events,
        ]);
    }

    // Participer à un événement
    #[Route('/{eventId}/participate', name: 'participation_participate', methods: ['GET', 'POST'])]
    public function participate(int $eventId, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'événement avec l'ID
        $event = $entityManager->getRepository(Event::class)->find($eventId);

        if (!$event) {
            throw $this->createNotFoundException('L\'événement demandé n\'existe pas.');
        }

        // Créer une nouvelle participation pour l'utilisateur actuel
        $participation = new Participation();
        $participation->setUserId($this->getUser()->getId()); // Assurez-vous que l'utilisateur est connecté
        $participation->setEventId($eventId);

        // Créer et gérer le formulaire de participation
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            $this->addFlash('success', 'Vous avez participé à l\'événement avec succès!');

            return $this->redirectToRoute('participation_events');
        }

        return $this->render('participation/participate.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    // Afficher les participations de l'utilisateur (ce qu'il a rejoint)
    #[Route('/my-participations', name: 'participation_my_participations', methods: ['GET'])]
    public function myParticipations(): Response
    {
        // Récupérer les participations de l'utilisateur actuel
        $userId = $this->getUser()->getId();
        $participations = $this->getDoctrine()
            ->getRepository(Participation::class)
            ->findBy(['user_id' => $userId]);

        return $this->render('participation/my_participations.html.twig', [
            'participations' => $participations,
        ]);
    }

    // Permettre au participant d'ajouter un feedback
    #[Route('/{eventId}/feedback', name: 'participation_feedback', methods: ['GET', 'POST'])]
    public function feedback(int $eventId, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'événement avec l'ID
        $event = $entityManager->getRepository(Event::class)->find($eventId);

        if (!$event) {
            throw $this->createNotFoundException('L\'événement demandé n\'existe pas.');
        }

        // Récupérer la participation existante de l'utilisateur
        $userId = $this->getUser()->getId();
        $participation = $entityManager->getRepository(Participation::class)->findOneBy([
            'user_id' => $userId,
            'event_id' => $eventId
        ]);

        if (!$participation) {
            throw $this->createNotFoundException('La participation à cet événement n\'existe pas.');
        }

        // Gérer le formulaire de feedback
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre feedback a été ajouté avec succès.');

            return $this->redirectToRoute('participation_my_participations');
        }

        return $this->render('participation/feedback.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
