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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_CREATE')"),
        new Patch(security: "is_granted('ROLE_EDIT', object)"),
        new Delete(security: "is_granted('ROLE_DELETE', object)"),
    ],
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
    #[ORM\OneToMany(targetEntity: Power::class, mappedBy: 'role', orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['role:read', 'role:write'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
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

// --- Gestion des Alignements (Stockage JSON) ---

    /**
     * @return string[]
     */
    #[Groups(['role:read'])]
    public function getAlignments(): array
    {
        return array_values($this->alignments);
    }

    /**
     * @param string[] $alignments
     */
    #[Groups(['role:write'])]
    public function setAlignments(array $alignments): static
    {
        $this->alignments = [];
        foreach ($alignments as $val) {
            // On valide que c'est bien une valeur de notre Enum
            $stringValue = ($val instanceof Alignment) ? $val->value : $val;
            if (is_string($stringValue) && Alignment::tryFrom($stringValue)) {
                $this->alignments[] = $stringValue;
            }
        }
        $this->alignments = array_values(array_unique($this->alignments));
        return $this;
    }

    // 2. IMPORTANT : On renomme les helpers pour casser la détection automatique "Singulier/Pluriel"
    // Symfony ne fera plus le lien entre "alignments" et ces méthodes.
    
    public function pushAlignment(Alignment $alignment): static
    {
        if (!in_array($alignment->value, $this->alignments)) {
            $this->alignments[] = $alignment->value;
        }
        return $this;
    }

    public function dropAlignment(Alignment $alignment): static
    {
        $index = array_search($alignment->value, $this->alignments);
        if ($index !== false) {
            unset($this->alignments[$index]);
            $this->alignments = array_values($this->alignments);
        }
        return $this;
    }

    // Un petit helper utile pour ton code PHP (si besoin de comparer des objets)
    public function hasAlignment(Alignment $alignment): bool
    {
        return in_array($alignment->value, $this->alignments);
    }
}