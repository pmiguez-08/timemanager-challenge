<?php
// Declaramos el espacio de nombres del controlador para mantener orden en la estructura.
namespace App\Controller\Api;

// Importamos clases base de Symfony para controladores y respuestas HTTP.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;   // Controlador base con utilidades.
use Symfony\Component\HttpFoundation\Request;                       // Para leer parámetros de la petición.
use Symfony\Component\HttpFoundation\JsonResponse;                  // Para responder en formato JSON.
use Symfony\Component\HttpFoundation\Response;                      // Códigos de estado HTTP.
use Symfony\Component\Routing\Annotation\Route;                     // Para definir rutas con atributos.

// Importamos los repositorios necesarios para validar usuario y consultar tareas.
use App\Repository\UserRepository;                                  // Para verificar que el usuario exista.
use App\Repository\TaskRepository;                                  // Para ejecutar la búsqueda paginada.

// Definimos una ruta base para agrupar endpoints de API de usuarios.
#[Route('/api/users', name: 'api_users_')]
class UserTasksController extends AbstractController
{
    // Inyectamos los repositorios por constructor para seguir buenas prácticas de DI.
    public function __construct(
        private readonly UserRepository $userRepository,            // Repositorio de usuarios.
        private readonly TaskRepository $taskRepository             // Repositorio de tareas.
    ) {}

    // Definimos el endpoint: GET /api/users/{id}/tasks
    #[Route('/{id}/tasks', name: 'tasks', methods: ['GET'])]
    public function listUserTasks(int $id, Request $request): JsonResponse
    {
        // 1) Validación de parámetros de ruta.
        // Verificamos que el ID sea un entero positivo mayor a cero.
        if ($id <= 0) {
            // Si no es válido, devolvemos 400 Bad Request con un mensaje claro.
            return $this->json(
                ['error' => 'El parámetro {id} debe ser un entero positivo.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // 2) Comprobamos que el usuario exista en base de datos.
        $user = $this->userRepository->find($id);                   // Buscamos el usuario por ID.
        if (!$user) {
            // Si no lo encontramos, devolvemos 404 Not Found.
            return $this->json(
                ['error' => 'Usuario no encontrado.'],
                Response::HTTP_NOT_FOUND
            );
        }

        // 3) Leemos y validamos filtros opcionales desde el query string.
        // page y limit para paginación, con valores por defecto seguros.
        $page  = (int) $request->query->get('page', 1);             // Página actual (por defecto 1).
        $limit = (int) $request->query->get('limit', 20);           // Tamaño de página (por defecto 20).

        // project_id opcional para filtrar por proyecto particular.
        $projectIdRaw = $request->query->get('project_id');         // Valor crudo como string o null.
        $projectId = null;                                          // Inicializamos como null (sin filtro).
        if ($projectIdRaw !== null && $projectIdRaw !== '') {       // Solo validamos si vino algo.
            if (!ctype_digit((string)$projectIdRaw)) {              // Validamos que sean dígitos positivos.
                return $this->json(
                    ['error' => 'project_id debe ser un entero positivo.'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $projectId = (int) $projectIdRaw;                       // Convertimos a entero.
            if ($projectId <= 0) {                                  // Confirmamos que sea mayor a cero.
                return $this->json(
                    ['error' => 'project_id debe ser mayor a cero.'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        // from y to como fechas opcionales en formato YYYY-MM-DD.
        $fromRaw = $request->query->get('from');                    // Texto de fecha inicial o null.
        $toRaw   = $request->query->get('to');                      // Texto de fecha final o null.

        // Usaremos una función local para parsear una fecha segura.
        $parseDate = function (?string $value, string $field) {     // Recibe el string y el nombre del campo.
            if ($value === null || $value === '') {                 // Si no viene, no hay filtro.
                return null;                                        // Devolvemos null.
            }
            // Validamos formato con una regexp simple: cuatro dígitos- dos dígitos- dos dígitos.
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {     // Si no cumple el patrón esperado...
                throw new \InvalidArgumentException(                // Lanzamos excepción para capturar luego.
                    sprintf('%s debe tener formato YYYY-MM-DD.', $field)
                );
            }
            // Intentamos crear un objeto DateTimeImmutable con la fecha.
            try {
                return new \DateTimeImmutable($value);              // Si la fecha es válida, la devolvemos.
            } catch (\Exception $e) {
                // Si la fecha es inválida (ej. 2025-02-30), informamos con error claro.
                throw new \InvalidArgumentException(                // Volvemos a lanzar como argumento inválido.
                    sprintf('%s no es una fecha válida.', $field)
                );
            }
        };

        try {
            // Parseamos las fechas usando la función local de validación.
            $from = $parseDate($fromRaw, 'from');                   // Fecha inicial o null.
            $to   = $parseDate($toRaw, 'to');                       // Fecha final o null.

            // Si ambas fechas vienen, validamos que from <= to.
            if ($from !== null && $to !== null && $from > $to) {    // Comparamos objetos DateTime.
                return $this->json(
                    ['error' => 'from no puede ser mayor que to.'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        } catch (\InvalidArgumentException $ex) {
            // Si hubo problema con el formato de fechas, devolvemos 400 con el mensaje.
            return $this->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        // 4) Ejecutamos la consulta paginada en el repositorio de tareas.
        $result = $this->taskRepository->searchUserTasks(           // Llamamos al método del Paso 4.
            userId:    $id,                                         // ID del usuario obligatorio.
            from:      $from,                                       // Fecha inicial opcional.
            to:        $to,                                         // Fecha final opcional.
            projectId: $projectId,                                  // Filtro por proyecto opcional.
            page:      max(1, $page),                               // Paginación con página mínima 1.
            limit:     min(max(1, $limit), 100)                     // Límite entre 1 y 100.
        );

        // 5) Construimos la respuesta JSON con metadatos de paginación.
        $items = $result['items'];                                  // Lista de tareas ya normalizada.
        $total = $result['total'];                                  // Total de filas para el meta.

        // Calculamos el total de páginas de forma segura evitando división por cero.
        $safeLimit = max(1, min(100, $limit));                       // Reafirmamos el rango de limit.
        $totalPages = (int) ceil($total / $safeLimit);               // Total de páginas redondeado hacia arriba.

        // Preparamos un paquete JSON claro y estable.
        $payload = [
            'user' => [                                             // Información mínima del usuario dueño.
                'id'   => $user->getId(),                           // ID del usuario.
                'name' => $user->getName(),                         // Nombre para mostrar.
            ],
            'meta' => [                                             // Metadatos para controlar la paginación.
                'page'        => max(1, $page),                     // Página actual confirmada.
                'limit'       => $safeLimit,                        // Límite por página aplicado.
                'total'       => $total,                            // Total de filas encontradas.
                'total_pages' => $totalPages,                       // Cantidad de páginas disponibles.
                'filters'     => [                                  // Eco de filtros aplicados (transparencia).
                    'from'       => $from?->format('Y-m-d'),        // from normalizado o null.
                    'to'         => $to?->format('Y-m-d'),          // to normalizado o null.
                    'project_id' => $projectId,                     // project_id usado o null.
                ],
            ],
            'items' => $items,                                      // Arreglo de tareas listo para renderizar.
        ];

        // 6) Devolvemos la respuesta 200 OK con el contenido JSON.
        return $this->json($payload, Response::HTTP_OK);            // Respuesta exitosa con datos.
    }
}
