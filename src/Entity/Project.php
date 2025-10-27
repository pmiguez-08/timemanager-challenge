<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id; // Identificador del proyecto.

    #[ORM\Column(length: 150)]
    private string $name; // Nombre del proyecto.

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Task::class)]
    private Collection $tasks; // Tareas vinculadas al proyecto.

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: UserProjectRate::class)]
    private Collection $projectRates; // Tarifas asociadas a los usuarios.

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->projectRates = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
}
