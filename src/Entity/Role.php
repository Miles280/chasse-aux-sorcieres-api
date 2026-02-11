<?php

namespace App\Entity;

use App\Enum\Alignment;
use App\Enum\Camp;
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

    // --- CHANGEMENT ICI : On utilise l'Enum directement ---
    #[ORM\Column(type: 'string', enumType: Camp::class)]
    #[Groups(['role:read', 'role:write'])]
    private ?Camp $camp = null;

    // Je suppose que Goal reste une entité ? Sinon il faut faire pareil.
    #[ORM\ManyToOne]
    #[Groups(['role:read', 'role:write'])]
    private ?Goal $goal = null;

    /**
     * @var Collection<int, Power>
     */
    #[ORM\OneToMany(targetEntity: Power::class, mappedBy: 'role', orphanRemoval: true)]
    #[Groups(['role:read', 'role:write'])]
    private Collection $powers;

    // --- CHANGEMENT ICI : Stockage JSON pour les alignements multiples ---
    /**
     * @var string[] On stocke les valeurs (strings) en base
     */
    #[ORM\Column(type: 'json')]
    #[Groups(['role:read', 'role:write'])]
    private array $alignments = [];

    public function __construct()
    {
        $this->powers = new ArrayCollection();
        // Plus besoin d'initialiser alignment comme ArrayCollection, c'est un array natif []
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

    // --- Gestion du Camp (Enum simple) ---

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
            if ($power->getRole() === $this) {
                $power->setRole(null);
            }
        }
        return $this;
    }

    // --- Gestion des Alignements (Collection d'Enums en JSON) ---

    /**
     * Retourne un tableau d'objets Enum Alignment
     * @return Alignment[]
     */
    public function getAlignments(): array
    {
        // On transforme les strings stockées en base en objets Enum
        return array_map(fn($val) => Alignment::tryFrom($val), $this->alignments);
    }

    /**
     * @param Alignment[] $alignments
     */
    public function setAlignments(array $alignments): static
    {
        // On transforme les objets Enum en strings pour la base de données
        $this->alignments = array_map(
            fn(Alignment $alignment) => $alignment->value, 
            $alignments
        );
        return $this;
    }

    // Helper pour ajouter un seul alignement facilement via le code
    public function addAlignment(Alignment $alignment): static
    {
        if (!in_array($alignment->value, $this->alignments)) {
            $this->alignments[] = $alignment->value;
        }
        return $this;
    }

    // Helper pour supprimer un alignement
    public function removeAlignment(Alignment $alignment): static
    {
        $index = array_search($alignment->value, $this->alignments);
        if ($index !== false) {
            unset($this->alignments[$index]);
            // Réindexe le tableau pour éviter les trous (0, 2, 3...)
            $this->alignments = array_values($this->alignments);
        }
        return $this;
    }
}