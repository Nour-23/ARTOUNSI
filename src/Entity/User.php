<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(min: 2, max: 50, minMessage: "Le nom doit contenir au moins 2 caractères.")]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(min: 2, max: 50, minMessage: "Le prénom doit contenir au moins 2 caractères.")]
    private ?string $familyname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 8, unique: true)]
    #[Assert\NotBlank(message: "Le C.I.N est obligatoire.")]
    #[Assert\Regex(pattern: "/^\d{8}$/", message: "Le C.I.N doit contenir exactement 8 chiffres.")]
    private ?string $cin = null;

    #[ORM\Column(length: 8)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire.")]
    #[Assert\Regex(pattern: "/^\d{8}$/", message: "Le numéro de téléphone doit contenir exactement 8 chiffres.")]
    private ?string $numtel = null;

    #[ORM\Column(type: "datetime_immutable", name: "date_naissance", nullable: false)]
#[Assert\NotBlank(message: "La date de naissance est requise.")]
#[Assert\LessThanOrEqual(
    value: "today -18 years",  // Utilisation de 'today' et l'offset pour la date d'il y a 18 ans
    message: "Vous devez avoir au moins 18 ans."
)]
private ?\DateTimeImmutable $dateNaissance = null;

    

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
    private ?string $adresse = null;
    
   
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $tokenExpiry = null;

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getTokenExpiry(): ?\DateTimeInterface
    {
        return $this->tokenExpiry;
    }

    public function setTokenExpiry(?\DateTimeInterface $tokenExpiry): self
    {
        $this->tokenExpiry = $tokenExpiry;
        return $this;
    }
    

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit contenir au moins 8 caractères.")]
    #[Assert\NotCompromisedPassword(message: "Ce mot de passe a été compromis dans une fuite de données, veuillez en choisir un autre.")]
    private ?string $password = null;

    #[Ignore]
    #[Assert\EqualTo(propertyPath: "password", message: "Les mots de passe ne correspondent pas.")]
    private ?string $confirmPassword = null;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $google_id = null;

    #[ORM\Column(length: 255, nullable: true)]
private ?string $photo = null;


    // GETTERS & SETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getFamilyname(): ?string
    {
        return $this->familyname;
    }

    public function setFamilyname(string $familyname): static
    {
        $this->familyname = $familyname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(string $cin): static
    {
        $this->cin = $cin;
        return $this;
    }

    public function getNumtel(): ?string
    {
        return $this->numtel;
    }

    public function setNumtel(string $numtel): static
    {
        $this->numtel = $numtel;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }
    
    

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getConfirmPassword(): ?string
    {
        return $this->confirmPassword;
    }

    public function setConfirmPassword(?string $confirmPassword): static
    {
        $this->confirmPassword = $confirmPassword;
        return $this;
    }

    public function getRoles(): array
    {
        if (empty($this->roles)) {
            return ['ROLE_USER'];
        }

        return array_unique($this->roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = array_intersect($roles, ["ROLE_CLIENT", "ROLE_ADMIN"]);
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->roles[0] ?? 'ROLE_CLIENT';
    }

    public function setRole(string $role): self
    {
        $this->roles = [$role];
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->google_id;
    }

    public function setGoogleId(?string $google_id): static
    {
        $this->google_id = $google_id;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Symfony exige cette méthode pour l'interface UserInterface
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
{
    $this->photo = $photo;
    return $this;
}

}
