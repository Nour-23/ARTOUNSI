<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $familyname = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "Veuillez entrer une adresse email valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 8, unique: true)]
    #[Assert\NotBlank(message: "Le C.I.N est obligatoire.")]
    #[Assert\Regex(pattern: "/^\d{8}$/", message: "Le C.I.N doit contenir exactement 8 chiffres.")]
    private ?string $cin = null;

    #[ORM\Column(type: 'boolean')]
    private bool $archived = false;

    #[ORM\Column(length: 8)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire.")]
    #[Assert\Regex(pattern: "/^\d{8}$/", message: "Le numéro de téléphone doit contenir exactement 8 chiffres.")]
    private ?string $numtel = null;

    #[ORM\Column(type: "datetime_immutable")]
    #[Assert\NotBlank(message: "La date de naissance est obligatoire.")]
    #[Assert\LessThanOrEqual(value: "today -18 years", message: "Vous devez avoir au moins 18 ans.")]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit contenir au moins 8 caractères.")]
    private ?string $password = null;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $google_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $tokenExpiry = null;

    #[ORM\ManyToMany(targetEntity: Formation::class, inversedBy: 'users', cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'user_formation')]
    private Collection $formations;

    #[ORM\Column(type: 'integer')]
    private int $loginCount = 0;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
    }

    // GETTERS & SETTERS

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getFamilyname(): ?string { return $this->familyname; }
    public function setFamilyname(string $familyname): self { $this->familyname = $familyname; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getCin(): ?string { return $this->cin; }
    public function setCin(string $cin): self { $this->cin = $cin; return $this; }

    public function getNumtel(): ?string { return $this->numtel; }
    public function setNumtel(string $numtel): self { $this->numtel = $numtel; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(string $adresse): self { $this->adresse = $adresse; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self { $this->dateNaissance = $dateNaissance; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getRoles(): array 
    { 
        // Assure qu'un utilisateur a au moins le rôle "ROLE_USER"
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; 

        return array_unique($roles);
    }

    public function setRoles(array $roles): self 
    { 
        $validRoles = ["ROLE_ADMIN", "ROLE_CLIENT", "ROLE_USER"];
        $this->roles = array_intersect($roles, $validRoles);
        return $this;
    }

    public function getGoogleId(): ?string { return $this->google_id; }
    public function setGoogleId(?string $google_id): self { $this->google_id = $google_id; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): self { $this->photo = $photo; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): self { $this->resetToken = $resetToken; return $this; }

    public function getTokenExpiry(): ?\DateTimeInterface { return $this->tokenExpiry; }
    public function setTokenExpiry(?\DateTimeInterface $tokenExpiry): self { $this->tokenExpiry = $tokenExpiry; return $this; }

    public function isArchived(): bool { return $this->archived; }
    public function setArchived(bool $archived): self { $this->archived = $archived; return $this; }

    public function eraseCredentials(): void { /* Symfony exige cette méthode */ }
    public function getUserIdentifier(): string { return $this->email; }

    public function getFormations(): Collection { return $this->formations; }
    public function addFormation(Formation $formation): self {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->addUser($this);
        }
        return $this;
    }
    public function removeFormation(Formation $formation): self {
        if ($this->formations->removeElement($formation)) {
            $formation->removeUser($this);
        }
        return $this;
    }

    public function getLoginCount(): int { return $this->loginCount; }
    public function setLoginCount(int $loginCount): self { $this->loginCount = $loginCount; return $this; }

    public function __toString(): string
    {
        return $this->name . ' ' . $this->familyname;
    }
}
