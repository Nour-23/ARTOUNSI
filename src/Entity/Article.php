<?php

namespace App\Entity;
use App\Repository\CategorieRepository;
use App\Repository\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ArticleRepository::class)]

class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Le nom doit contenir au moins 3 caractères.")]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prix est obligatoire.")]
#[Assert\Positive(message: "Le prix doit être un nombre positif.")]
private ?float $prix = null;

    #[ORM\Column(type: "integer")]
#[Assert\NotBlank(message: "Le nombre d'articles est obligatoire.")]
#[Assert\PositiveOrZero(message: "Le stock ne peut pas être négatif.")]
 private ?int $nbrearticle = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Image(
        maxSize: "2M",
        mimeTypes: ["image/jpeg", "image/png"],
        mimeTypesMessage: "Seules les images JPG et PNG sont acceptées.",
        maxSizeMessage: "L'image ne doit pas dépasser 2 Mo."
    )]
    private ?string $image = null;

    #[ORM\Column(type: "datetime_immutable")]
    #[Assert\NotBlank(message: "La date de publication est obligatoire.")]
    #[Assert\Type("\DateTimeImmutable", message: "La date doit être valide.")]
    #[Assert\LessThan("tomorrow", message: "La date ne peut pas être future.")]
    private ?\DateTimeImmutable $publiactiondate = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    private ?Categorie $categorie = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getNbrearticle(): ?int
    {
        return $this->nbrearticle;
    }

    public function setNbrearticle(int $nbrearticle): static
    {
        $this->nbrearticle = $nbrearticle;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getPubliactiondate(): ?\DateTimeImmutable
    {
        return $this->publiactiondate;
    }

    public function setPubliactiondate(\DateTimeImmutable $publiactiondate): static
    {
        $this->publiactiondate = $publiactiondate;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }
}