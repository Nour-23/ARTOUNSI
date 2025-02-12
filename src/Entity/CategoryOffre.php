<?php

namespace App\Entity;

use App\Repository\CategoryOffreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryOffreRepository::class)]
class CategoryOffre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, Offre>
     */
    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'category')]
    private Collection $offres;

    public function __construct()
    {
        $this->offres = new ArrayCollection();
    }
    public function __toString(): string
    {
        return $this->name . ' (' . $this->libelle . ')'; // Exemple d'affichage, ajustez selon vos besoins
    }

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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, Offre>
     */
    public function getOffres(): Collection
    {
        return $this->offres;
    }

    public function addOffre(Offre $offre): static
    {
        if (!$this->offres->contains($offre)) {
            $this->offres->add($offre);
            $offre->setCategory($this);
        }

        return $this;
    }

    public function removeOffre(Offre $offre): static
    {
        if ($this->offres->removeElement($offre)) {
            // set the owning side to null (unless already changed)
            if ($offre->getCategory() === $this) {
                $offre->setCategory(null);
            }
        }

        return $this;
    }
}
