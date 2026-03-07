<?php

namespace App\Controller;

use Throwable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionController extends AbstractController
{
    #[Route('/error', name: 'app_error')]
    public function showException(Throwable $exception, Request $request): Response
    {
        // Obtenez le code d'erreur HTTP
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        
        // Choisissez le template en fonction du code d'erreur
        $template = 'errors/error.html.twig'; // Template par défaut
        if ($statusCode == 403) {
            $template = 'errors/error403.html.twig';
        } elseif ($statusCode == 404) {
            $template = 'errors/error404.html.twig';
        } elseif ($statusCode == 500) {
            $template = 'errors/error500.html.twig';
        }

        return $this->render($template, [
            'status_code' => $statusCode,
            'message' => $exception->getMessage(),
            'referer' => $request->headers->get('referer'),
        ]);
    }
}
