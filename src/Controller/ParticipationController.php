<?php
namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/participation')]
class ParticipationController extends AbstractController
{
    // Liste des événements et leurs participants
    #[Route('/', name: 'participation_list', methods: ['GET'])]
    public function list(EventRepository $eventRepository, ParticipationRepository $participationRepository): Response
    {
        $events = $eventRepository->findAll(); // Récupérer tous les événements
        $eventParticipants = [];

        foreach ($events as $event) {
            // Récupérer les participations liées à cet événement
            $participants = $participationRepository->findBy(['event' => $event]);

            // Stocker les participants pour cet événement
            $eventParticipants[$event->getId()] = $participants;
        }

        return $this->render('event/participation.html.twig', [
            'events' => $events,
            'eventParticipants' => $eventParticipants,
        ]);
    }

    #[Route('/{id}/feedback', name: 'participation_feedback', methods: ['GET', 'POST'])]
    public function feedback(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder($participation)
            ->add('feedback', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => true,
                'label' => 'Provide your feedback',
            ])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
                'label' => 'Submit Feedback'
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            $this->addFlash('success', 'Your feedback has been submitted!');
            return $this->redirectToRoute('participation_list'); // 
        }

        return $this->render('event/feedback.html.twig', [
            'form' => $form->createView(),
            'participation' => $participation,
        ]);
    }
}
