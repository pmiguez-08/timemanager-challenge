<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// >>> NUEVO: importamos nuestro repositorio para declararlo como repositorio por defecto.
use App\Repository\UserRepository;


// Declaramos que esta clase es una Entidad y que su repositorio por defecto es UserRepository.
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id; // Identificador único del usuario.

    #[ORM\Column(length: 100)]
    private string $name; // Nombre completo del usuario.

    #[ORM\Column(length: 150, unique: true)]
    private string $email; // Email único para identificar al usuario.

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Task::class)]
    private Collection $tasks; // Lista de tareas asociadas al usuario.

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserProjectRate::class)]
    private Collection $projectRates; // Tarifas del usuario por proyecto.

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->projectRates = new ArrayCollection();
    }

    // Getters y setters simples.
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
}
