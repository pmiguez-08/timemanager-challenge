<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserProjectRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id; // ID único.

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'projectRates')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user; // Usuario relacionado.

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectRates')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project; // Proyecto relacionado.

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $rate; // Tarifa por hora o unidad.

    public function getId(): int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getProject(): Project { return $this->project; }
    public function setProject(Project $project): self { $this->project = $project; return $this; }
    public function getRate(): float { return $this->rate; }
    public function setRate(float $rate): self { $this->rate = $rate; return $this; }
}
