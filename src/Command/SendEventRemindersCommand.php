<?php

namespace App\Command;

use App\Repository\EventRepository;
use App\Repository\ParticipantRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendEventRemindersCommand extends Command
{
    protected static $defaultName = 'app:send-event-reminders';

    private $eventRepository;
    private $participantRepository;
    private $mailer;

    // Define the reminder intervals (in seconds) for clarity
    private const REMINDER_INTERVALS = [
        '7 days'  => 7 * 24 * 60 * 60,
        '1 day'   => 24 * 60 * 60,
        '6 hours' => 6 * 60 * 60,
    ];

    public function __construct(EventRepository $eventRepository, ParticipantRepository $participantRepository, MailerInterface $mailer)
    {
        parent::__construct();
        $this->eventRepository = $eventRepository;
        $this->participantRepository = $participantRepository;
        $this->mailer = $mailer;
    }

    protected function configure(): void
    {
        $this->setDescription('Sends email reminders to participants for upcoming events.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTimeImmutable();

        // Loop through each reminder interval
        foreach (self::REMINDER_INTERVALS as $label => $secondsOffset) {
            // Calculate target time for this reminder
            $targetTime = $now->modify('+' . $secondsOffset . ' seconds');
            // To account for hourly execution, create a 1-hour window around the target time.
            // For example, check for events scheduled between targetTime and targetTime + 1 hour.
            $windowStart = $targetTime;
            $windowEnd   = $targetTime->modify('+1 hour');

            // Retrieve events whose start time is between $windowStart and $windowEnd.
            // This method should be added to your EventRepository or implemented inline.
            $events = $this->eventRepository->findEventsBetweenDates($windowStart, $windowEnd);

            foreach ($events as $event) {
                // Retrieve participants for the event.
                // You may implement a method in ParticipantRepository such as findParticipantsByEvent($eventId)
                $participants = $this->participantRepository->findParticipantsByEvent($event->getId());

                foreach ($participants as $participant) {
                    $email = (new Email())
                        ->from('noreply@artounsi.com') // Adjust the sender email as needed
                        ->to($participant->getEmail())
                        ->subject("Reminder: Your event '{$event->getTitle()}' is in $label")
                        ->text("Hello, this is a reminder that your event '{$event->getTitle()}' is scheduled to start in $label.")
                        ->html("<p>Hello,</p><p>This is a reminder that your event '<strong>{$event->getTitle()}</strong>' is scheduled to start in $label.</p>");

                    $this->mailer->send($email);
                    $output->writeln("Sent $label reminder for event '{$event->getTitle()}' to " . $participant->getEmail());
                }
            }
        }

        return Command::SUCCESS;
    }
}
