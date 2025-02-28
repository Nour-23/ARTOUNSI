<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/event')]
class EventController extends AbstractController
{
    /**
     * Display a list of events with category filtering.
     */
    #[Route('/', name: 'event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository, Request $request): Response
    {
        // Fetch distinct categories
        $categories = $eventRepository->findDistinctCategories();
        
        // Get selected category from request
        $selectedCategory = $request->query->get('category', '');

        // Fetch events based on category filter
        $events = $selectedCategory ? 
            $eventRepository->findBy(['eventCategory' => $selectedCategory]) : 
            $eventRepository->findAll();

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'categories' => $categories, 
            'selectedCategory' => $selectedCategory,
        ]);
    }

    /**
     * Create a new event.
     */
    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès!');
            return $this->redirectToRoute('event_index');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Display a single event.
     */
    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(EventRepository $eventRepository, int $id): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException("L'événement demandé n'existe pas.");
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    /**
     * Edit an existing event.
     */
    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Événement mis à jour avec succès!');
            return $this->redirectToRoute('event_index');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    /**
     * Delete an event.
     */
    #[Route('/{id}/delete', name: 'event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();

            $this->addFlash('success', 'Événement supprimé avec succès!');
        }

        return $this->redirectToRoute('event_index');
    }
}
