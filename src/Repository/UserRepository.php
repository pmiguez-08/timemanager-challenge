<?php
// Declaramos el espacio de nombres del repositorio.
namespace App\Repository;

// Importamos la entidad y las clases base para repositorios de Doctrine.
use App\Entity\User;                                               // Entidad que administrará este repositorio.
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository; // Base para repositorios inyectables.
use Doctrine\Persistence\ManagerRegistry;                          // Permite a Doctrine darnos el EntityManager correcto.

/**
 * Repositorio por defecto para la entidad User.
 * Aquí puedes crear métodos de consulta personalizados (findByEmail, etc.).
 */
class UserRepository extends ServiceEntityRepository
{
    // El constructor recibe el registro de gestores y lo pasa al padre con la clase User.
    public function __construct(ManagerRegistry $registry)
    {
        // Asociamos este repositorio con la entidad User para que Doctrine lo conozca.
        parent::__construct($registry, User::class);
    }

    // Ejemplo opcional (no lo usamos ahora): buscar por email.
    // public function findOneByEmail(string $email): ?User
    // {
    //     return $this->createQueryBuilder('u')
    //         ->andWhere('u.email = :email')
    //         ->setParameter('email', $email)
    //         ->getQuery()
    //         ->getOneOrNullResult();
    // }
}
