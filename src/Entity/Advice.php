<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\AdviceRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;


#[ORM\Entity(repositoryClass: AdviceRepository::class)]
class Advice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[Groups(["advices"])]
    private Collection $createdBy;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["advices"])]
    private ?string $text = null;

    #[ORM\Column]
    #[Groups(["advices"])]
    private ?int $month = null;

    public function __construct()
    {
        $this->createdBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, User>
     */
    public function getCreatedBy(): Collection
    {
        return $this->createdBy;
    }

    public function addCreatedBy(User $createdBy): static
    {
        if (!$this->createdBy->contains($createdBy)) {
            $this->createdBy->add($createdBy);
        }

        return $this;
    }

    public function removeCreatedBy(User $createdBy): static
    {
        $this->createdBy->removeElement($createdBy);

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }
}
