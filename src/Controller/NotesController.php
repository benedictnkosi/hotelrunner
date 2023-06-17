<?php

namespace App\Controller;

use App\Service\NotesApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class NotesController extends AbstractController
{
    /**
     * @Route("api/note/add")
     */
    public function addNote(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, NotesApi $notesApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $response = $notesApi->addNote($request->get('id'), str_replace("+", "", $request->get('note')));
        $response = new JsonResponse($response , 201, array());
        return $response;
    }

    /**
     * @Route("api/json/note/add")
     */
    public function addNoteJson(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, NotesApi $notesApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $parameters = json_decode($request->getContent(), true);

        $response = $notesApi->addNote($parameters['id'], str_replace("+", "", $parameters['note']));
        return new JsonResponse($response , 201, array());
    }

}