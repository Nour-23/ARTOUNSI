<?php
namespace App\Controller;

use App\Entity\Participation;
use App\Entity\Event;
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
    // Liste des événements avec la possibilité de participer
    #[Route('/', name: 'participation_list', methods: ['GET', 'POST'])]
    public function list(Request $request, EventRepository $eventRepository, EntityManagerInterface $entityManager): Response
    {
        $events = $eventRepository->findAll(); // Récupérer tous les événements
        $participations = [];

        foreach ($events as $event) {
            $participation = new Participation();
            $participation->setEvent($event);

            // Créer un formulaire pour chaque événement
            $form = $this->createForm(ParticipationType::class, $participation);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($participation);
                $entityManager->flush();

                // Redirection ou message de succès
                $this->addFlash('success', 'Your participation has been saved!');
            }

            $participations[$event->getId()] = $form->createView();
        }

        return $this->render('participation/list.html.twig', [
            'events' => $events,
            'participations' => $participations,
        ]);
    }
}
