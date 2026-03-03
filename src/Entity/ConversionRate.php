<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ConversionRateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversionRateRepository::class)]
#[ApiResource]
class ConversionRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private ?int $socialRankLevel = null;

    #[ORM\Column]
    private ?int $gemToRubyRate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSocialRankLevel(): ?int
    {
        return $this->socialRankLevel;
    }

    public function setSocialRankLevel(int $socialRankLevel): static
    {
        $this->socialRankLevel = $socialRankLevel;

        return $this;
    }

    public function getGemToRubyRate(): ?int
    {
        return $this->gemToRubyRate;
    }

    public function setGemToRubyRate(int $gemToRubyRate): static
    {
        $this->gemToRubyRate = $gemToRubyRate;

        return $this;
    }
}
