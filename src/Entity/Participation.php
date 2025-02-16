<?php 

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Define the Many-to-One relationship with the Event entity
    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]  // Ensure this is not nullable for the foreign key constraint
    private ?Event $event = null;

    #[ORM\Column(type: "integer", nullable: true)]  // Allow null values for the response
    private ?int $response = null;  // 0 for No, 1 for Yes, or null if no response yet

    #[ORM\Column(type: "text", nullable: true)]  // Feedback can be nullable as well
    private ?string $feedback = null;

    // Getter and Setter for ID
    public function getId(): ?int
    {
        return $this->id;
    }

    // Getter and Setter for Event
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    // Getter and Setter for Response
    public function getResponse(): ?int
    {
        return $this->response;
    }

    public function setResponse(?int $response): self  // Allow nullable response
    {
        if ($response !== null && !in_array($response, [0, 1])) {
            throw new \InvalidArgumentException('Response must be 0, 1, or null');
        }

        $this->response = $response;

        return $this;
    }

    // Getter and Setter for Feedback
    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): self
    {
        $this->feedback = $feedback;

        return $this;
    }
}
