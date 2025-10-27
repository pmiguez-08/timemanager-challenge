<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\TaskRepository;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id; // ID de la tarea.

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user; // Usuario que realiza la tarea.

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project; // Proyecto de la tarea.

    #[ORM\Column(length: 255)]
    private string $title; // Título de la tarea.

    #[ORM\Column(type: 'integer')]
    private int $durationMinutes; // Duración en minutos.

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $appliedRate; // Tarifa aplicada.

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount; // Monto calculado (duración * tarifa).

    //agregamos la fecha de la tarea para poder filtrar y ordenar por día.
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $date; // Fecha en la que se registró o se realizó la tarea.


    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function setDurationMinutes(int $minutes): self { $this->durationMinutes = $minutes; return $this; }
    public function getAppliedRate(): float { return $this->appliedRate; }
    public function setAppliedRate(float $rate): self { $this->appliedRate = $rate; return $this; }
    public function getAmount(): float { return $this->amount; }
    public function setAmount(float $amount): self { $this->amount = $amount; return $this; }

    // getters y setters para la fecha de la tarea.
    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }

    //  getters/setters de user y project si no los tenías generados.
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function getProject(): Project { return $this->project; }
    public function setProject(Project $project): self { $this->project = $project; return $this; }
    // <<< NUEVO
}
