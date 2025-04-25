<?php

namespace App\Entity;

use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getNeeds"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getNeeds"])]
    #[Assert\NotBlank(message: "Name is mandatory")]
    private ?string $name = null;

    /**
     * @var Collection<int, Need>
     */
    #[ORM\OneToMany(targetEntity: Need::class, mappedBy: 'author')]
    private Collection $needs;

    public function __construct()
    {
        $this->needs = new ArrayCollection();
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

    /**
     * @return Collection<int, Need>
     */
    public function getNeeds(): Collection
    {
        return $this->needs;
    }

    public function addNeed(Need $need): static
    {
        if (!$this->needs->contains($need)) {
            $this->needs->add($need);
            $need->setAuthor($this);
        }

        return $this;
    }

    public function removeNeed(Need $need): static
    {
        if ($this->needs->removeElement($need)) {
            // set the owning side to null (unless already changed)
            if ($need->getAuthor() === $this) {
                $need->setAuthor(null);
            }
        }

        return $this;
    }
}
