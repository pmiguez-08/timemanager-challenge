<?php
// Declaramos el namespace para mantener la estructura del proyecto.
namespace App\DataFixtures;

// Importamos clases base para fixtures y manejo de entidades.
use Doctrine\Bundle\FixturesBundle\Fixture;         // Clase base para crear fixtures.
use Doctrine\Persistence\ObjectManager;             // Administrador de entidades.

// Importamos nuestras entidades para poder referenciarlas.
use App\Entity\User;                                 // Entidad de usuarios.
use App\Entity\Project;                              // Entidad de proyectos.
use App\Entity\Task;                                 // Entidad de tareas.

// Esta fixture agrega 10 tareas extra para ampliar el set de pruebas.
class TenMoreTasksFixtures extends Fixture
{
    // Método principal que Doctrine ejecuta al cargar las fixtures.
    public function load(ObjectManager $manager): void
    {
        // Buscamos a los usuarios existentes por nombre (ya creados en AppFixtures).
        $ana    = $manager->getRepository(User::class)->findOneBy(['name' => 'Ana López']);     // Usuario Ana.
        $carlos = $manager->getRepository(User::class)->findOneBy(['name' => 'Carlos Pérez']);  // Usuario Carlos.

        // Buscamos los proyectos existentes por nombre (ya creados en AppFixtures).
        $portal     = $manager->getRepository(Project::class)->findOneBy(['name' => 'Portal Ventas']);     // Proyecto 1.
        $backoffice = $manager->getRepository(Project::class)->findOneBy(['name' => 'Backoffice Interno']); // Proyecto 2.

        // Si por algún motivo faltan, salimos temprano para evitar errores (entorno inconsistente).
        if (!$ana || !$carlos || !$portal || !$backoffice) {                                       // Validamos existencia.
            return;                                                                                // No insertamos nada si faltan referencias.
        }

        // Fechas de ejemplo para las nuevas tareas (últimos días).
        $d1 = new \DateTimeImmutable('2025-10-20');  // Fecha fija 1.
        $d2 = new \DateTimeImmutable('2025-10-21');  // Fecha fija 2.
        $d3 = new \DateTimeImmutable('2025-10-22');  // Fecha fija 3.
        $d4 = new \DateTimeImmutable('2025-10-23');  // Fecha fija 4.
        $d5 = new \DateTimeImmutable('2025-10-24');  // Fecha fija 5.

        // Función local para crear una tarea calculando el importe.
        $mk = function(User $u, Project $p, string $title, int $min, float $rate, \DateTimeInterface $date) use ($manager) {
            // Creamos la instancia de Task para insertar una fila en la tabla task.
            $t = new Task();                                                   // Nueva tarea.
            $t->setUser($u);                                                   // Asignamos usuario dueño.
            $t->setProject($p);                                                // Asignamos proyecto asociado.
            $t->setTitle($title);                                              // Título descriptivo de la tarea.
            $t->setDurationMinutes($min);                                      // Minutos trabajados.
            $t->setAppliedRate($rate);                                         // Tarifa aplicada en ese momento.
            $t->setDate($date);                                                // Fecha de realización/registro.
            $amount = ($min / 60) * $rate;                                     // Cálculo del importe.
            $t->setAmount(round($amount, 2));                                   // Redondeo a dos decimales.
            $manager->persist($t);                                             // Encolamos para insertar en DB.
        };

        // 10 tareas nuevas: distribuimos entre usuarios y proyectos con diferentes duraciones/fechas/tarifas.
        $mk($ana,    $portal,     'Refactor de componentes',           75,  25.00, $d1); // 1
        $mk($ana,    $portal,     'Reunión con producto',              30,  25.00, $d2); // 2
        $mk($ana,    $backoffice, 'Optimización de consultas',         90,  30.00, $d2); // 3
        $mk($ana,    $backoffice, 'Diseño de endpoints',               120, 30.00, $d3); // 4
        $mk($carlos, $portal,     'Corrección de estilos',             45,  20.00, $d3); // 5
        $mk($carlos, $portal,     'Pruebas de integración',            80,  20.00, $d4); // 6
        $mk($carlos, $backoffice, 'Ajuste de migraciones',             50,  22.00, $d4); // 7
        $mk($carlos, $backoffice, 'Documentación técnica',             60,  22.00, $d5); // 8
        $mk($ana,    $portal,     'Monitoreo de errores',              40,  25.00, $d5); // 9
        $mk($carlos, $portal,     'Reporte de métricas',               55,  20.00, $d1); // 10

        // Escribimos definitivamente los INSERT en la base de datos.
        $manager->flush();                                                      // Ejecuta los cambios en DB.
    }
}
