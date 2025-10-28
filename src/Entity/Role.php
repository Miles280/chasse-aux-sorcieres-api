<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['role:read']],
    denormalizationContext: ['groups' => ['role:write']],
)]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['role:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['role:read', 'role:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['role:read', 'role:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['role:read', 'role:write'])]
    private ?int $minPlayer = null;

    #[ORM\ManyToOne(inversedBy: 'roles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['role:read', 'role:write'])]
    private ?Camp $camp = null;

    #[ORM\ManyToOne]
    #[Groups(['role:read', 'role:write'])]
    private ?Goal $goal = null;

    /**
     * @var Collection<int, Power>
     */
    #[ORM\OneToMany(targetEntity: Power::class, mappedBy: 'role', orphanRemoval: true)]
    #[Groups(['role:read', 'role:write'])]
    private Collection $powers;

    /**
     * @var Collection<int, Alignment>
     */
    #[ORM\ManyToMany(targetEntity: Alignment::class, inversedBy: 'roles')]
    #[Groups(['role:read', 'role:write'])]
    private Collection $alignment;

    public function __construct()
    {
        $this->powers = new ArrayCollection();
        $this->alignment = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMinPlayer(): ?int
    {
        return $this->minPlayer;
    }

    public function setMinPlayer(int $minPlayer): static
    {
        $this->minPlayer = $minPlayer;

        return $this;
    }

    public function getCamp(): ?Camp
    {
        return $this->camp;
    }

    public function setCamp(?Camp $camp): static
    {
        $this->camp = $camp;

        return $this;
    }

    public function getGoal(): ?Goal
    {
        return $this->goal;
    }

    public function setGoal(?Goal $goal): static
    {
        $this->goal = $goal;

        return $this;
    }

    /**
     * @return Collection<int, Power>
     */
    public function getPowers(): Collection
    {
        return $this->powers;
    }

    public function addPower(Power $power): static
    {
        if (!$this->powers->contains($power)) {
            $this->powers->add($power);
            $power->setRole($this);
        }

        return $this;
    }

    public function removePower(Power $power): static
    {
        if ($this->powers->removeElement($power)) {
            // set the owning side to null (unless already changed)
            if ($power->getRole() === $this) {
                $power->setRole(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Alignment>
     */
    public function getAlignment(): Collection
    {
        return $this->alignment;
    }

    public function addAlignment(Alignment $alignment): static
    {
        if (!$this->alignment->contains($alignment)) {
            $this->alignment->add($alignment);
        }

        return $this;
    }

    public function removeAlignment(Alignment $alignment): static
    {
        $this->alignment->removeElement($alignment);

        return $this;
    }
}
