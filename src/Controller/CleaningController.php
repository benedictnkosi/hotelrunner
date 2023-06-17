<?php

namespace App\Controller;

use App\Helpers\FormatHtml\CalendarHTML;
use App\Service\CleaningApi;
use App\Service\ReservationApi;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CleaningController extends AbstractController
{
    /**
     * @Route("api/cleanings/{roomId}")
     */
    public function getCleanings($roomId, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, CleaningApi $cleaningApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $html = $cleaningApi->getCleaningsByRoom($roomId);
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/json/cleanings/{roomId}")
     */
    public function getCleaningsJson($roomId, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, CleaningApi $cleaningApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $cleanings = $cleaningApi->getCleaningsByRoomJson($roomId);
        if($cleanings == null){
            $responseArray = array(
                'result_message' => "Cleanings not found",
                'result_code' => 1
            );
            return new JsonResponse($responseArray , 200, array());
        }
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($cleanings, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }



    /**
     * @Route("api/outstandingcleanings/today")
     */
    public function getOutstandingCleaningsForToday(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, CleaningApi $cleaningApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $html = $cleaningApi->getOutstandingCleaningsForToday();
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/json/outstandingcleanings/today")
     */
    public function getOutstandingCleaningsForTodayJson(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, CleaningApi $cleaningApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $cleaningApi->getOutstandingCleaningsForTodayJson();
        return new JsonResponse($response , 200, array());
    }

    /**
     * @Route("api/cleaning/add")
     */
    public function addCleaningToReservation(LoggerInterface $logger,Request $request, EntityManagerInterface $entityManager, CleaningApi $cleaningApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $response = $cleaningApi->addCleaningToReservation($request->get('reservation_id'),$request->get('employee_id'));
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 201, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/json/cleaning/{id}")
     */
    public function getCleaningJson( $id, LoggerInterface $logger, Request $request, CleaningApi $api): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $cleaning = $api->getCleaningById($id);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($cleaning, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }


}