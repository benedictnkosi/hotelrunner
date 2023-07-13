<?php

namespace App\Controller;

use App\Helpers\FormatHtml\ConfigEmployeesHTML;
use App\Helpers\FormatHtml\ConfigGuestsHTML;
use App\Helpers\SMSHelper;
use App\Service\CommunicationApi;
use App\Service\EmployeeApi;
use App\Service\GuestApi;
use App\Service\PropertyApi;
use App\Service\ReservationApi;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class GuestController extends AbstractController
{
    /**
     * @Route("/api/guests/{filterValue}", name="guests", defaults={"guestId": 0})
     */
    public function getGuest($filterValue, LoggerInterface $logger, Request $request, GuestApi $guestApi, PropertyApi $propertyApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__ );
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $guestApi->getGuest($filterValue);
        return new JsonResponse($response , 200, array());
    }

    /**
     * @Route("no_auth/json/guest/{id}")
     */
    public function getGuestJson( $id, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $guest = $guestApi->getGuestById($id);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($guest, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }

    /**
     * @Route("/api/guest/{guestId}/phone/{phoneNumber}")
     */
    public function updateGuestPhone($guestId, $phoneNumber, LoggerInterface $logger,Request $request,GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $guestApi->updateGuestPhoneNumber($guestId, $phoneNumber);
        $guestApi->sendBookDirectSMS($guestId);

        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("/api/guest/{guestId}/email/{email}")
     */
    public function updateGuestEmail($guestId, $email, LoggerInterface $logger,Request $request,GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $guestApi->updateGuestEmail($guestId, $email);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("/api/guest/{guestId}/idnumber/{idNumber}")
     */
    public function updateGuestIdNumber($guestId, $idNumber, LoggerInterface $logger, Request $request,GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $guestApi->updateGuestIdNumber($guestId, $idNumber);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("/api/guest/idnumber")
     */
    public function updateGuestIdNumberJson(LoggerInterface $logger, Request $request,GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
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
        $response = $guestApi->updateGuestIdNumber($parameters['guest_id'], $parameters['id_number']);
        return new JsonResponse($response , 200, array());
    }


    /**
     * @Route("/api/guests/airbnbname/{confirmationCode}/{name}")
     */
    public function createAirbnbGuest($confirmationCode, $name, LoggerInterface $logger,Request $request,GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);

        $response = $guestApi->createAirbnbGuest($confirmationCode, urldecode($name));
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/json/config/guests")
     */
    public function getConfigGuestsJson(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
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
        $guests = $guestApi->getConfigGuests($parameters['name_filter']);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($guests, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }

    /**
     * @Route("api/config/guests/{nameFilter}")
     */
    public function getConfigGuests( $nameFilter, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $guests = $guestApi->getConfigGuests($nameFilter);
        $logger->info("calling Method: formatHtml" );
        $configGuestsHTML = new ConfigGuestsHTML( $entityManager, $logger);
        $html = $configGuestsHTML->formatHtml($guests);
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/guest/update/{guestId}/{field}/{newValue}")
     */
    public function updateGuest($guestId, $field, $newValue, Request $request,LoggerInterface $logger, EntityManagerInterface $entityManager, guestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = match ($field) {
            "name" => $guestApi->updateGuestName($guestId, $newValue),
            "rewards" => $guestApi->updateGuestRewards($guestId, $newValue),
            "phoneNumber" => $guestApi->updateGuestPhoneNumber($guestId, $newValue),
            "email" => $guestApi->updateGuestEmail($guestId, $newValue),
            default => array(
                'result_message' => "field not found",
                'result_code' => 1
            ),
        };


        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/json/guest/update")
     */
    public function updateGuestJson( Request $request,LoggerInterface $logger, EntityManagerInterface $entityManager, guestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
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
        $response = match ($parameters["field"]) {
            "name" => $guestApi->updateGuestName($parameters["guest_id"], $parameters["new_value"]),
            "rewards" => $guestApi->updateGuestRewards($parameters["guest_id"], $parameters["new_value"]),
            "phoneNumber" => $guestApi->updateGuestPhoneNumber($parameters["guest_id"], $parameters["new_value"]),
            default => array(
                'result_message' => "field not found",
                'result_code' => 1
            ),
        };

        $response = new JsonResponse($response , 200, array());
        return $response;
    }

    /**
     * @Route("/admin_api/guest/delete/{guestId}")
     */
    public function removeGuest($guestId, LoggerInterface $logger, Request $request,GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('delete') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $guestApi->removeGuest($guestId);
        if ($response['result_code'] === 0) {
            return new JsonResponse($response, 204, array());
        }else{
            return new JsonResponse($response, 200, array());
        }
    }

}