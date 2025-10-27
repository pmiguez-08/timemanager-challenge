<?php
// Declaramos el espacio de nombres donde guardamos nuestras fixtures.
namespace App\DataFixtures;

// Importamos las clases de Doctrine para trabajar con el EntityManager.
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// Importamos nuestras entidades para poder crear registros.
use App\Entity\User;
use App\Entity\Project;
use App\Entity\UserProjectRate;
use App\Entity\Task;

class AppFixtures extends Fixture
{
    // El método load se ejecuta cuando lanzamos el comando doctrine:fixtures:load.
    public function load(ObjectManager $manager): void
    {
        // Creamos dos usuarios de ejemplo con nombre y email únicos.
        $u1 = (new User())->setName('Ana López')->setEmail('ana@example.com');
        $u2 = (new User())->setName('Carlos Pérez')->setEmail('carlos@example.com');

        // Persistimos los usuarios para que Doctrine los tenga en cuenta al hacer flush.
        $manager->persist($u1);
        $manager->persist($u2);

        // Creamos dos proyectos de ejemplo con nombres visibles.
        $p1 = (new Project())->setName('Portal Ventas');
        $p2 = (new Project())->setName('Backoffice Interno');

        // Persistimos los proyectos en el contexto de Doctrine.
        $manager->persist($p1);
        $manager->persist($p2);

        // Definimos tarifas por usuario y proyecto (tabla puente).
        // Ana en Portal Ventas con 25.00 por hora.
        $r11 = (new UserProjectRate())->setUser($u1)->setProject($p1)->setRate(25.00);
        // Ana en Backoffice con 30.00 por hora.
        $r12 = (new UserProjectRate())->setUser($u1)->setProject($p2)->setRate(30.00);
        // Carlos en Portal Ventas con 20.00 por hora.
        $r21 = (new UserProjectRate())->setUser($u2)->setProject($p1)->setRate(20.00);

        // Persistimos las tarifas para que queden disponibles al crear tareas.
        $manager->persist($r11);
        $manager->persist($r12);
        $manager->persist($r21);

        // Ahora creamos algunas tareas de ejemplo para cada combinación.
        // Para simplificar, usamos fechas recientes: hoy y ayer.
        $hoy   = new \DateTimeImmutable('today');    // Fecha de hoy a medianoche.
        $ayer  = new \DateTimeImmutable('yesterday'); // Fecha de ayer a medianoche.

        // Función auxiliar local para crear una tarea con importe calculado.
        $crearTarea = function(User $u, Project $p, string $titulo, int $minutos, float $tarifa, \DateTimeInterface $fecha) use ($manager) {
            // Creamos la entidad Task y asignamos todos sus campos obligatorios.
            $t = new Task();                                         // Instanciamos una nueva tarea.
            $t->setUser($u);                                         // Asociamos el usuario dueño de la tarea.
            $t->setProject($p);                                      // Asociamos el proyecto al que pertenece.
            $t->setTitle($titulo);                                   // Asignamos un título descriptivo de la tarea.
            $t->setDurationMinutes($minutos);                        // Guardamos la duración en minutos.
            $t->setAppliedRate($tarifa);                             // Copiamos la tarifa aplicada en ese momento.
            $t->setDate($fecha);                                     // Guardamos la fecha de la tarea.
            // Calculamos el importe como horas trabajadas por tarifa.
            $amount = ($minutos / 60) * $tarifa;                     // Calculamos el importe con precisión básica.
            $t->setAmount(round($amount, 2));                        // Redondeamos a dos decimales por claridad.
            $manager->persist($t);                                   // Encolamos la tarea para ser guardada en base de datos.
        };

        // Tareas para Ana en Portal Ventas con tarifa 25.00.
        $crearTarea($u1, $p1, 'Revisión de tickets', 90, 25.00, $ayer);   // 1.5 h * 25.00 = 37.50
        $crearTarea($u1, $p1, 'Ajuste de reportes', 60, 25.00, $hoy);     // 1.0 h * 25.00 = 25.00

        // Tareas para Ana en Backoffice con tarifa 30.00.
        $crearTarea($u1, $p2, 'Migración de datos', 120, 30.00, $hoy);    // 2.0 h * 30.00 = 60.00

        // Tareas para Carlos en Portal Ventas con tarifa 20.00.
        $crearTarea($u2, $p1, 'QA de módulos', 45, 20.00, $ayer);         // 0.75 h * 20.00 = 15.00
        $crearTarea($u2, $p1, 'Documentación', 30, 20.00, $hoy);          // 0.5 h * 20.00 = 10.00

        // Finalmente hacemos flush para escribir definitivamente todos los registros en la base de datos.
        $manager->flush();                                                // Ejecuta los INSERT en la base.
    }
}

