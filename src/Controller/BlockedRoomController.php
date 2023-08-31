<?php

namespace App\Controller;

use App\Helpers\FormatHtml\BlockedRoomsHTML;
use App\Service\BlockedRoomApi;
use App\Service\ReservationApi;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class BlockedRoomController extends AbstractController
{

    /**
     * @Route("/api/blockroom")
     */
    public function blockRoom(LoggerInterface $logger,Request $request,EntityManagerInterface $entityManager, BlockedRoomApi $blockedRoomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $blockedRoomApi->blockRoom($request->get('room'),  $request->get('start_date'), $request->get('end_date'), urldecode($request->get('note')));
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 201, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("/api/json/blockroom")
     */
    public function blockRoomJson(LoggerInterface $logger,Request $request,EntityManagerInterface $entityManager, BlockedRoomApi $blockedRoomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $parameters = json_decode($request->getContent(), true);
        if($parameters == null){
            $response = array(
                'result_code' => 1,
                'result_message' => "Invalid body string",
            );
            return new JsonResponse($response , 200, array());
        }
        $response = $blockedRoomApi->blockRoom($parameters['room_id'],  $parameters['start_date'], $parameters['end_date'], urldecode($parameters['note']));
        return new JsonResponse($response , 201, array());
    }

    /**
     * @Route("/api/blockedroom/get")
     */
    public function getBlockedRooms( LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, BlockedRoomApi $blockedRoomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $blockedRooms = $blockedRoomApi->getBlockedRoomsByProperty();
        $blockedRoomsHTML = new BlockedRoomsHTML($entityManager, $logger);
        $formattedHtml = $blockedRoomsHTML->formatHtml($blockedRooms);
        $response = array(
            'html' => $formattedHtml,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("/api/json/blockedroom/get")
     */
    public function getBlockedRoomsJson( LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, BlockedRoomApi $blockedRoomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $blockedRooms = $blockedRoomApi->getBlockedRoomsByProperty();
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($blockedRooms, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }

    /**
     * @Route("/api/blockedroom/delete/{id}")
     */
    public function deleteBlockedRooms($id, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, BlockedRoomApi $blockedRoomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('delete')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $blockedRoomApi->deleteBlockedRoom($id);
        $callback = $request->get('callback');
        if ($response['result_code'] === 0) {
            $response = new JsonResponse($response , 204, array());
        }else{
            $response = new JsonResponse($response , 200, array());
        }

        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/json/blockedroom/{id}")
     */
    public function getGetBlockedRoomJson( $id, LoggerInterface $logger,Request $request, BlockedRoomApi $blockedRoomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $blockedRoom = $blockedRoomApi->getBlockedRoom($id);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($blockedRoom, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }
}