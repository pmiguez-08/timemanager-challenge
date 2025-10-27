<?php
// Declaramos el espacio de nombres de los repositorios.
namespace App\Repository;

// Importamos las clases base de Doctrine para repositorios y construcción de consultas.
use App\Entity\Task;                                  // Importamos la entidad Task para que el repositorio la gestione.
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository; // Repositorio base con utilidades comunes.
use Doctrine\Persistence\ManagerRegistry;             // Permite a Doctrine inyectar el EntityManager adecuado.
use Doctrine\ORM\Tools\Pagination\Paginator;         // Utilidad de Doctrine para paginar resultados grandes.

class TaskRepository extends ServiceEntityRepository
{
    // El constructor recibe el registro de gestores y lo pasa al padre con la clase Task.
    public function __construct(ManagerRegistry $registry)
    {
        // Llamamos al constructor del repositorio base indicando la entidad que administrará.
        parent::__construct($registry, Task::class);  // Esto asocia este repositorio con la entidad Task.
    }

    /**
     * Busca tareas de un usuario con filtros y paginación.
     *
     * @param int $userId                       El ID del usuario dueño de las tareas.
     * @param \DateTimeInterface|null $from     Fecha inicial inclusiva (opcional).
     * @param \DateTimeInterface|null $to       Fecha final inclusiva (opcional).
     * @param int|null $projectId               ID de proyecto para filtrar (opcional).
     * @param int $page                         Número de página (>= 1).
     * @param int $limit                        Tamaño de página (1..100).
     * @return array{items: array<int, array>, total: int}   Devuelve items normalizados y el total.
     */
    public function searchUserTasks(
        int $userId,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to,
        ?int $projectId,
        int $page = 1,
        int $limit = 20
    ): array {
        // Aseguramos rangos seguros para la paginación por protección y rendimiento.
        $page  = max(1, $page);               // Si envían página 0 o negativa, la elevamos a 1.
        $limit = min(max(1, $limit), 100);    // Forzamos límite entre 1 y 100 para evitar respuestas masivas.

        // Creamos un QueryBuilder con alias 't' para la tabla de tareas.
        $qb = $this->createQueryBuilder('t'); // Empezamos a construir la consulta desde Task como 't'.

        // Hacemos join con Project para obtener su nombre sin consultas adicionales.
        $qb->leftJoin('t.project', 'p')       // Unimos la relación ManyToOne de Task->Project con alias 'p'.
        ->addSelect('p');                  // Agregamos 'p' al SELECT para tener el proyecto en memoria.

        // Filtro obligatorio: tareas del usuario indicado.
        $qb->andWhere('t.user = :uid')        // Agregamos condición 't.user = :uid'.
        ->setParameter('uid', $userId);    // Sustituimos el parámetro ':uid' por el valor de entrada.

        // Si viene una fecha inicial, filtramos desde esa fecha (inclusive).
        if ($from !== null) {                                 // Verificamos si 'from' fue suministrado.
            $qb->andWhere('t.date >= :from')                  // Agregamos condición 't.date >= :from'.
            ->setParameter('from', $from->format('Y-m-d')); // Pasamos la fecha en formato 'YYYY-MM-DD'.
        }

        // Si viene una fecha final, filtramos hasta esa fecha (inclusive).
        if ($to !== null) {                                   // Verificamos si 'to' fue suministrado.
            $qb->andWhere('t.date <= :to')                    // Agregamos condición 't.date <= :to'.
            ->setParameter('to', $to->format('Y-m-d'));     // Pasamos la fecha en formato 'YYYY-MM-DD'.
        }

        // Si viene un proyecto específico, lo usamos como filtro adicional.
        if ($projectId !== null) {                            // Evaluamos si hay projectId a filtrar.
            $qb->andWhere('p.id = :pid')                      // Condición 'p.id = :pid' sobre el join de proyectos.
            ->setParameter('pid', $projectId);             // Vinculamos el parámetro ':pid'.
        }

        // Ordenamos por fecha descendente y luego por id para orden estable.
        $qb->orderBy('t.date', 'DESC')       // Orden principal por fecha más reciente primero.
        ->addOrderBy('t.id', 'DESC');     // Orden secundario por id descendente para consistencia.

        // Calculamos el desplazamiento según la página y el límite.
        $offset = ($page - 1) * $limit;      // Determina cuántos registros saltar antes de empezar.

        // Aplicamos paginación a la consulta principal.
        $qb->setFirstResult($offset)         // Fijamos el primer resultado a recuperar.
        ->setMaxResults($limit);          // Fijamos el máximo de resultados devueltos.

        // Creamos el paginador de Doctrine a partir de la consulta.
        $paginator = new Paginator($qb, true); // El 'true' indica que cuente los resultados totales.

        // Obtenemos el total de filas sin paginar para construir el meta de respuesta.
        $total = count($paginator);          // Contar en el paginator ejecuta una consulta COUNT(*) eficiente.

        // Normalizamos cada Task a un arreglo simple para la API y la vista.
        $items = [];                          // Inicializamos el arreglo de items de salida.
        foreach ($paginator as $task) {       // Iteramos fila a fila las tareas paginadas.
            // Para seguridad, trabajamos solo con getters públicos de la entidad.
            $project = $task->getProject();   // Obtenemos el proyecto asociado a la tarea.

            // Convertimos la entidad a un array simple apto para JSON.
            $items[] = [
                'task_id'          => $task->getId(),                        // ID de la tarea.
                'task_title'       => $task->getTitle(),                     // Título descriptivo.
                'date'             => $task->getDate()->format('Y-m-d'),     // Fecha normalizada a texto.
                'project_id'       => $project->getId(),                     // ID del proyecto.
                'project_name'     => $project->getName(),                   // Nombre del proyecto.
                'duration_minutes' => $task->getDurationMinutes(),           // Duración en minutos.
                //'applied_rate'     => (float)$task->getAppliedRate(),        // Tarifa aplicada.
                //'amount'           => (float)$task->getAmount(),             // Importe calculado.

                // Formateamos tarifa e importe a dos decimales para consistencia visual.
                'applied_rate' => (float) number_format((float)$task->getAppliedRate(), 2, '.', ''), // Dos decimales en tarifa.
                'amount'       => (float) number_format((float)$task->getAmount(), 2, '.', ''),      // Dos decimales en importe.

            ];


        }

        // Devolvemos el paquete con los items y el total para construir el meta en el controlador.
        return [
            'items' => $items,     // Arreglo de tareas normalizadas listas para serializar.
            'total' => $total,     // Entero con el total de filas que cumplen la búsqueda sin paginar.
        ];
    }
}
