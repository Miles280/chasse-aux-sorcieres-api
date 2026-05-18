<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\GameCompositionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: GameCompositionRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['composition:read']],
    denormalizationContext: ['groups' => ['composition:write']],
)]
class GameComposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['composition:read', 'game:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'compositions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['composition:read', 'composition:write'])]
    private ?Game $game = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['composition:read', 'composition:write', 'game:read'])]
    private ?Role $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;
        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;
        return $this;
    }
}