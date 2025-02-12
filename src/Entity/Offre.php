<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre ne peut pas être vide.")]
    #[Assert\Length(
        min: 3, 
        max: 255, 
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    
    private ?string $title = null;


    #[ORM\Column(length: 255)]
    private ?string $status = 'active';

    #[ORM\ManyToOne(inversedBy: 'offres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategoryOffre $category = null;



    public function __construct()
{
    $this->status = 'active'; // Ou une autre valeur par défaut
}


    public function getId(): ?int
    {
        return $this->id;
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCategory(): ?categoryoffre
    {
        return $this->category;
    }

    public function setCategory(?categoryoffre $category): static
    {
        $this->category = $category;

        return $this;
    }

   
}
