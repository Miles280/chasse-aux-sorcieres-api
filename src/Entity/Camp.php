<?php

namespace App\Entity;

use App\Repository\CampRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CampRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['camp:read']],
    denormalizationContext: ['groups' => ['camp:write']],
)]
class Camp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['camp:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['camp:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['camp:read'])]
    private ?string $color = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['camp:read'])]
    private ?string $emojiName = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['camp:read'])]
    private ?string $emojiId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['camp:read'])]
    private ?string $description = null;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\OneToMany(targetEntity: Role::class, mappedBy: 'camp', orphanRemoval: true)]
    #[Groups(['camp:read'])]
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getEmojiName(): ?string
    {
        return $this->emojiName;
    }

    public function setEmojiName(?string $emojiName): static
    {
        $this->emojiName = $emojiName;

        return $this;
    }

    public function getEmojiId(): ?string
    {
        return $this->emojiId;
    }

    public function setEmojiId(?string $emojiId): static
    {
        $this->emojiId = $emojiId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->setCamp($this);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        if ($this->roles->removeElement($role)) {
            // set the owning side to null (unless already changed)
            if ($role->getCamp() === $this) {
                $role->setCamp(null);
            }
        }

        return $this;
    }
}
