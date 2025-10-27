<?php
// Mantiene el código ordenado dentro del espacio de nombres de la app.
namespace App\Controller;

// Importa el controlador base de Symfony.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Importa la clase Response para responder al navegador.
use Symfony\Component\HttpFoundation\Response;
// Importa el atributo Route para definir la ruta con PHP 8+.
use Symfony\Component\Routing\Annotation\Route;

class PingController extends AbstractController
{
    // Define la ruta /ping para peticiones GET y le da el nombre interno app_ping.
    #[Route('/ping', name: 'app_ping', methods: ['GET'])]
    public function __invoke(): Response
    {
        // Crea un texto simple para devolver.
        $mensaje = 'pong';
        // Retorna una respuesta 200 con el texto.
        return new Response($mensaje);
    }
}
