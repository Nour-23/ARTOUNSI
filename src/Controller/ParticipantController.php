<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participation;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParticipantController extends AbstractController
{
    #[Route('/participant', name: 'app_participant', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        // Get all unique event categories
        $categories = $eventRepository->createQueryBuilder('e')
            ->select('DISTINCT e.eventCategory')
            ->getQuery()
            ->getSingleColumnResult();

        // Get selected category from request
        $selectedCategory = $request->query->get('category');

        // Fetch events based on the selected category
        $queryBuilder = $eventRepository->createQueryBuilder('e');

        if ($selectedCategory) {
            $queryBuilder->where('e.eventCategory = :category')
                ->setParameter('category', $selectedCategory);
        }

        $events = $queryBuilder->getQuery()->getResult();

        return $this->render('event/participate.html.twig', [
            'events' => $events,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory
        ]);
    }

    #[Route('/participant/event/{id}/participate', name: 'participant_event_participate')]
    public function participate(int $id, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        // Fetch the Event entity from the database
        $event = $eventRepository->find($id);

        if (!$event) {
            $this->addFlash('error', 'Event not found!');
            return $this->redirectToRoute('app_participant');
        }

        $participation = new Participation();
        $participation->setEvent($event);
        $participation->setResponse(0);

        $em->persist($participation);
        $em->flush();

        $this->addFlash('success', 'You have successfully participated in the event!');

        return $this->redirectToRoute('app_participant');
    }
}
