<?php
// Declaramos el espacio de nombres donde ubicamos los controladores web.
namespace App\Controller;

// Importamos el controlador base de Symfony para renderizar plantillas.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Importamos objetos de petición y respuesta para manejar entradas HTTP.
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// Importamos la anotación Route para definir rutas con atributos.
use Symfony\Component\Routing\Annotation\Route;

// Definimos un controlador web para páginas HTML.
class WebTasksController extends AbstractController
{
    // Definimos la ruta que mostrará la tabla de tareas de un usuario.
    // Usamos {id} como parámetro de ruta para identificar al usuario.
    #[Route('/tasks/user/{id}', name: 'app_tasks_user', methods: ['GET'])]
    public function tasksForUser(int $id, Request $request): Response
    {
        // Validamos por seguridad que el id del usuario sea positivo.
        if ($id <= 0) {
            // Si no es válido, devolvemos una respuesta 400 con un texto simple.
            return new Response('El id de usuario debe ser mayor que 0.', Response::HTTP_BAD_REQUEST);
        }

        // Obtenemos, si existen, filtros iniciales desde query string para prellenar inputs.
        $from = $request->query->get('from');       // Posible fecha 'desde' en formato YYYY-MM-DD.
        $to   = $request->query->get('to');         // Posible fecha 'hasta' en formato YYYY-MM-DD.
        $pid  = $request->query->get('project_id'); // Posible id de proyecto para filtrar.

        // Renderizamos la plantilla Twig pasando los valores necesarios al frontend.
        return $this->render('tasks.html.twig', [   // Cargamos templates/tasks.html.twig.
            'userId'     => $id,                    // Pasamos el id de usuario para que JS construya la URL a la API.
            'initialFrom'=> $from,                  // Pasamos valor inicial opcional para el filtro 'from'.
            'initialTo'  => $to,                    // Pasamos valor inicial opcional para el filtro 'to'.
            'initialPid' => $pid,                   // Pasamos valor inicial opcional para el filtro 'project_id'.
        ]);
    }
}
