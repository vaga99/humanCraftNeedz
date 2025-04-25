<?php

namespace App\Entity;

use App\Repository\NeedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NeedRepository::class)]
class Need
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getNeeds", "getNeed"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getNeeds", "getNeed"])]
    #[Assert\NotBlank(message: "Title is mandatory")]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getNeed"])]
    #[Assert\NotBlank(message: "Summary is mandatory")]
    private ?string $summary = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getNeed"])]
    #[Assert\NotBlank(message: "Url is mandatory")]
    #[Assert\Url(message: "Url is not valid")]
    private ?string $url = null;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\ManyToMany(targetEntity: Skill::class, mappedBy: 'needs')]
    #[Groups(["getNeeds", "getNeed"])]
    #[Assert\Count(min: "1")]
    private Collection $skills;

    #[ORM\ManyToOne(inversedBy: 'needs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getNeeds", "getNeed"])]
    #[Assert\NotBlank(message: "Author is mandatory")]
    private ?Author $author = null;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
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

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
            $skill->addNeed($this);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        if ($this->skills->removeElement($skill)) {
            $skill->removeNeed($this);
        }

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): static
    {
        $this->author = $author;

        return $this;
    }
}
