<?php

namespace App\Controller;

use App\Entity\QueueMessages;
use App\Service\PaymentApi;
use App\Service\ReservationApi;
use App\Service\RoomApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class QueueController extends AbstractController
{
    /**
     * @Route("/no_auth/import/queue")
     * @throws \Exception
     */
    public function processQueueMessage( Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, PaymentApi $paymentApi, RoomApi $roomApi): Response
    {
        $logger->info("Starting Methods: " . __METHOD__);
        if (!$request->isMethod('POST')) {

            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $logger->info("queue message" . $request->get("message"));

        //get uuid from the message
        $payload = $request->get("message");
        $underscoreIndex = strpos($payload, "_");
        $guid = substr($payload, 0,$underscoreIndex );
        $message = substr($payload, $underscoreIndex + 1);
        $logger->info("message is " . $message);
        $logger->info("guid is  " . $guid);
        $response = array();
        //create reservation
        if(strlen($guid) !== 36){
            $response[] = array(
                'result_code' => 1,
                'result_message' => "guid length is not 36",
            );
        }else{
            $logger->info("message length  " . strlen($message));
            if(strlen($message) == 431){
                $response = $reservationApi->uploadReservations($message, $request);
            }else if(strlen($message) == 36){
                $response = $paymentApi->uploadPayment($message);
            }else if(strlen($message) == 58){
                $availableRooms = $roomApi->getAvailableRoomsFromString($message, $request);
                $availableRoomsMessage = "";
                foreach ($availableRooms as $availableRoom) {
                    $availableRoomsMessage .= $availableRoom->getName() . ",";
                }
                $response[] = array(
                    'result_code' => 0,
                    'result_message' => substr($availableRoomsMessage,0, strlen($availableRoomsMessage) - 1 )
                );
            }else{
                $response[] = array(
                    'result_code' => 1,
                    'result_message' => "message length incorrect : " .strlen($message),
                );
            }
        }

        $logger->info("response message is " . $response[0]['result_message']);

        $queueMessage = new QueueMessages();
        $queueMessage->setMessage($message);
        $queueMessage->setResponse(json_encode($response));
        $entityManager->persist($queueMessage);
        $entityManager->flush($queueMessage);

        //place response on the queue
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,"https://vugtjfyp:589v1Hlivd3Eqp7qKaaUJLjSlWJCDwmd@campbell.lmq.cloudamqp.com/api/exchanges/vugtjfyp/hotelrunner-response/publish");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            '{"properties":{},"routing_key":"response_key","payload":"'.$guid . "_". $response[0]['result_message'] .'","payload_encoding":"string"}');
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $logger->info("curl output is " . $server_output);
        return new JsonResponse($response, 200, array());
    }


}