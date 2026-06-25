<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_playlist', fields: ['user', 'playlist'])]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Playlist::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Playlist $playlist = null;

    /** Note sur 100, null si pas encore évaluée */
    #[ORM\Column(nullable: true)]
    private ?int $note = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPlaylist(): ?Playlist
    {
        return $this->playlist;
    }

    public function setPlaylist(?Playlist $playlist): static
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    /**
     * Calcule le statut à la volée : pas de stockage en BDD.
     * Toujours recalculé depuis la note actuelle et le seuil de la playlist.
     */
    public function getStatut(): string
    {
        if ($this->note === null) {
            return 'Non évalué';
        }

        $seuil = $this->playlist?->getSeuilReussite();

        if ($seuil === null) {
            return 'Non évalué';
        }

        return $this->note >= $seuil ? 'Certificat obtenu' : 'Échec';
    }
}
