<?php

namespace App\Controller;

use App\Entity\QueueMessages;
use App\Entity\Reservations;
use App\Entity\ReservationStatus;
use App\Helpers\FormatHtml\CalendarHTML;
use App\Helpers\FormatHtml\InvoiceHTML;
use App\Helpers\FormatHtml\ReservationsHtml;
use App\Helpers\FormatHtml\SingleReservationHtml;
use App\Service\AddOnsApi;
use App\Service\BlockedRoomApi;
use App\Service\DefectApi;
use App\Service\GuestApi;
use App\Service\NotesApi;
use App\Service\PaymentApi;
use App\Service\ReservationApi;
use App\Service\RoomApi;
use DateTime;
use Exception;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ReservationController extends AbstractController
{

    /**
     * @Route("api/calendar")
     */
    public function getCalendar( LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $calendarHtml = new CalendarHTML($entityManager, $logger);
        $html = $calendarHtml->formatHtml();
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("no_auth/reservations/checkout/json/{propertyId}")
     */
    public function getJsonCheckOutReservations($propertyId,  LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservations = $reservationApi->getCheckOutReservation($propertyId);
        $logger->info("back from other call");
        $logger->info("reservations count " . sizeof($reservations) );
        return new JsonResponse(print_r($reservations), 200, array());

    }

    /**
     * @Route("api/reservations/{period}")
     */
    public function getReservations($period,  LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservations = "";
        switch ($period) {
            case "future":
                $reservations = $reservationApi->getUpComingReservations(0, true,true);
                break;
            case "past":
                $reservations = $reservationApi->getPastReservations();
                break;
            case "checkout":
                $reservations = $reservationApi->getCheckOutReservation();
                break;
            case "stayover":
                $reservations = $reservationApi->getStayOversReservations();
                break;
            case "pending":
                $reservations = $reservationApi->getPendingReservations();
                break;
            default:
        }

        $reservationHtml = new ReservationsHtml($entityManager, $logger);
        $html = $reservationHtml->formatHtml($reservations, $period);
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;

    }

    /**
     * @Route("api/reservations_json/{period}")
     */
    public function getReservations_json($period,  LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservations = match ($period) {
            "future" => $reservationApi->getUpComingReservations(0, true, true),
            "past" => $reservationApi->getPastReservations(),
            "checkout" => $reservationApi->getCheckOutReservation(),
            "stayover" => $reservationApi->getStayOversReservations(),
            "pending" => $reservationApi->getPendingReservations(),
            default => null,
        };

        if($reservations == null){
            $reservations[] = array(
                'result_message' => "Reservations not found" ,
                'result_code' => 1
            );
        }

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($reservations, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }

    /**
     * @Route("api/reservation_html/{reservationId}")
     */
    public function getReservationByIdHtml($reservationId,  LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservation = $reservationApi->getReservation($reservationId);
        $reservationHtml = new SingleReservationHtml($entityManager, $logger);
        $html = $reservationHtml->formatHtml($reservation);
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;

    }


    /**
     * @Route("no_auth/reservation/{reservationId}")
     */
    public function getReservationById($reservationId, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $reservationApi->getReservationJson($reservationId);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/reservations/{reservationId}/update/{field}/{newValue}")
     */
    public function updateReservation($reservationId, $field, $newValue, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, BlockedRoomApi $blockedRoomApi,  NotesApi $notesApi, DefectApi $defectApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);

        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }


        $reservation = $reservationApi->getReservation($reservationId);
        $responseArray = array();
        $now = new DateTime();
        switch ($field) {
            case "status":
                $notesApi->addNote($reservation->getId(), "Status Changed to " .$newValue. " at " . $now->format("Y-m-d H:i"));
                if(intval($newValue) !== 0){
                    $status = $entityManager->getRepository(ReservationStatus::class)->findOneBy(array('id' => $newValue));
                }else{
                    $status = $entityManager->getRepository(ReservationStatus::class)->findOneBy(array('name' => $newValue));
                }
                $reservation->SetStatus($status);
                if(strcmp($status->getName(), 'cancelled')===0){
                    $blockedRoomApi->deleteBlockedRoomByReservation($reservation->getId());
                }
                break;
            case "check_in_time":
                $reservation->SetCheckInTime($newValue);
                break;
            case "check_out_time":
                $reservation->SetCheckOutTime($newValue);
                break;
            case "check_in_status":

                if (strcmp($newValue, "checked_in") == 0) {
                    $logger->info("checked_in");
                    if ($reservationApi->isEligibleForCheckIn($reservation)) {
                        $reservation->setCheckInStatus($newValue);
                        $reservation->setCheckInTime($now->format("H:i"));
                        $notesApi->addNote($reservation->getId(), "Checked-in at " . $now->format("H:i"));
                    } else {
                        $responseArray[] = array(
                            'result_message' => "Please make sure the guest Id and phone number is captured",
                            'result_code' => 1
                        );
                        $logger->info(print_r($responseArray, true));
                        $callback = $request->get('callback');
                        $response = new JsonResponse($responseArray, 200 , array());
                        $response->setCallback($callback);
                        return $response;
                    }

                } else if (strcmp($newValue, "checked_out") == 0) {
                    $logger->info("checked_out");
                    $due = $reservationApi->getAmountDue($reservation);

                    if ($due == 0 || $defectApi->isDefectEnabled("view_reservation_11")) {
                        $reservation->setCheckInStatus($newValue);
                        $reservation->setCheckOutTime($now->format("H:i"));
                        $notesApi->addNote($reservation->getId(), "Checked-out at " . $now->format("H:i"));
                    } else {
                        $logger->info($due);
                        $responseArray[] = array(
                            'result_message' => "Please make sure the guest has settled their balance",
                            'result_code' => 1,
                            'due' => $due
                        );
                        $logger->info(print_r($responseArray, true));
                        $callback = $request->get('callback');
                        $response = new JsonResponse($responseArray, 200, array());
                        $response->setCallback($callback);
                        return $response;
                    }
                } else {
                    $responseArray[] = array(
                        'result_message' => "incorrect status provided",
                        'result_code' => 1
                    );
                    $logger->info(print_r($responseArray, true));
                    $callback = $request->get('callback');
                    $response = new JsonResponse($responseArray, 200, array());
                    $response->setCallback($callback);
                    return $response;
                }


                break;
            default:
                $responseArray[] = array(
                    'result_message' => "incorrect update field provided",
                    'result_code' => 1
                );
                $logger->info(print_r($responseArray, true));
                $callback = $request->get('callback');
                $response = new JsonResponse($responseArray, 200, array());
                $response->setCallback($callback);
                return $response;
        }
        $response = $reservationApi->updateReservation($reservation);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/json/reservations/update")
     * @throws Exception
     */
    public function updateReservationJson(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, BlockedRoomApi $blockedRoomApi,  NotesApi $notesApi, DefectApi $defectApi): Response
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
        $reservation = $reservationApi->getReservation($parameters['id']);
        if(is_array($reservation)){
            $responseArray[] = array(
                'result_message' => "Reservation not found",
                'result_code' => 1
            );
            return new JsonResponse($responseArray, 200 , array());
        }

        $newValue = $parameters['value'];
        $field = $parameters['field'];

        $responseArray = array();
        $now = new DateTime();
        switch ($field) {
            case "status":
                if(is_numeric($newValue)){
                    $status = $entityManager->getRepository(ReservationStatus::class)->findOneBy(array('id' => $newValue));
                }else{
                    $status = $entityManager->getRepository(ReservationStatus::class)->findOneBy(array('name' => $newValue));
                }
                if($status == null){
                    $responseArray[] = array(
                        'result_message' => "Status not valid",
                        'result_code' => 1
                    );
                    return new JsonResponse($responseArray, 200 , array());
                }

                //validate current status is not pending for cancellations
                if(strcmp($reservation->getStatus()->getName(), "pending") == 0
                && strcmp($status->getName(), "cancelled") == 0){
                    $responseArray[] = array(
                        'result_message' => "Pending reservations cannot be cancelled",
                        'result_code' => 1
                    );
                    return new JsonResponse($responseArray, 200 , array());
                }

                //validate current status is not pending for cancellations
                if(strcmp($reservation->getStatus()->getName(), "pending") == 0
                    && strcmp($status->getName(), "opened") == 0){
                    $responseArray[] = array(
                        'result_message' => "Pending reservations cannot be opened",
                        'result_code' => 1
                    );
                    return new JsonResponse($responseArray, 200 , array());
                }

                //validate current status is not pending for cancellations
                if(strcmp($reservation->getStatus()->getName(), "pending") == 0
                    && strcmp($status->getName(), "confirmed") == 0){
                    $responseArray[] = array(
                        'result_message' => "Pending reservations cannot be confirmed",
                        'result_code' => 1
                    );
                    return new JsonResponse($responseArray, 200 , array());
                }

                //validate reservation is not in the past for cancellations
                $checkOutDate = new DateTime($reservation->getCheckout());
                $now = new DateTime('today midnight');
                if($checkOutDate < $now){
                    $responseArray[] = array(
                        'result_message' => "Past reservations cannot be cancelled",
                        'result_code' => 1
                    );
                    return new JsonResponse($responseArray, 200 , array());
                }

                $reservation->SetStatus($status);
                $notesApi->addNote($reservation->getId(), "Status Changed to " .$newValue. " at " . $now->format("Y-m-d H:i"));

                if(strcmp($status->getName(), 'cancelled')===0){
                    $blockedRoomApi->deleteBlockedRoomByReservation($reservation->getId());
                }
                break;
            case "check_in_status":
                if (strcmp($newValue, "checked_in") == 0) {
                    $logger->info("checked_in");
                    if ($reservationApi->isEligibleForCheckIn($reservation)) {
                        $reservation->setCheckInStatus($newValue);
                        $reservation->setCheckInTime($now->format("H:i"));
                        if (!$defectApi->isDefectEnabled("update_reservation_4")) {
                            $notesApi->addNote($reservation->getId(), "Checked-in at " . $now->format("H:i"));
                        }
                    } else {
                        $responseArray[] = array(
                            'result_message' => "Please make sure the guest Id and phone number is captured",
                            'result_code' => 1
                        );
                        $logger->info(print_r($responseArray, true));
                        return new JsonResponse($responseArray, 200 , array());
                    }

                } else if (strcmp($newValue, "checked_out") == 0) {
                    $logger->info("checked_out");
                    $due = $reservationApi->getAmountDue($reservation);

                    if ($due == 0 || $defectApi->isDefectEnabled("view_reservation_11")) {
                        $reservation->setCheckInStatus($newValue);
                        $reservation->setCheckOutTime($now->format("H:i"));
                        $notesApi->addNote($reservation->getId(), "Checked-out at " . $now->format("H:i"));
                    } else {
                        $responseArray[] = array(
                            'result_message' => "Please make sure the guest has settled their balance",
                            'result_code' => 1
                        );
                        $logger->info(print_r($responseArray, true));
                        return new JsonResponse($responseArray, 200, array());
                    }
                } else {
                    $responseArray[] = array(
                        'result_message' => "incorrect check-in status provided",
                        'result_code' => 1
                    );
                    $logger->info(print_r($responseArray, true));
                    return new JsonResponse($responseArray, 200, array());
                }
                break;
            default:
                $responseArray[] = array(
                    'result_message' => "Incorrect update field provided",
                    'result_code' => 1
                );
                $logger->info(print_r($responseArray, true));
                return new JsonResponse($responseArray, 200, array());
        }
        $response = $reservationApi->updateReservation($reservation);
        return new JsonResponse($response, 200, array());
    }

    /**
     * @Route("api/reservations/{reservationId}/update_checkin_time/{checkInTime}/{checkOutTime}")
     */
    public function updateReservationCheckInTime($reservationId, $checkInTime, $checkOutTime, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, BlockedRoomApi $blockedRoomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservation = $reservationApi->getReservation($reservationId);

        $reservation->SetCheckInTime($checkInTime);
        $reservation->SetCheckOutTime($checkOutTime);

        $response = $reservationApi->updateReservation($reservation);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/reservations/{reservationId}/update/dates/{checkInDate}/{checkOutDate}")
     */
    public function updateReservationDates($reservationId, $checkInDate, $checkOutDate, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, BlockedRoomApi $blockedRoomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservation = $reservationApi->getReservation($reservationId);
        if(is_array($reservation)){
            return new JsonResponse($reservation, 200, array());
        }
        $response = $reservationApi->updateReservationDate($reservation, $checkInDate, $checkOutDate, $blockedRoomApi);

        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/json/reservations/update/dates")
     */
    public function updateReservationDatesJson(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, BlockedRoomApi $blockedRoomApi): Response
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
        $reservation = $reservationApi->getReservation($parameters["id"]);
        if(is_array($reservation)){
            return new JsonResponse($reservation, 200, array());
        }
        $response = $reservationApi->updateReservationDate($reservation, $parameters["check_in_date"], $parameters["check_out_date"], $blockedRoomApi);

        return new JsonResponse($response, 200, array());
    }

    /**
     * @Route("api/reservations/{reservationId}/update_room/{roomId}")
     */
    public function updateReservationRoom($reservationId, $roomId, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservation = $reservationApi->getReservation($reservationId);
        $response = $reservationApi->updateReservationRoom($reservation, $roomId);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/json/reservations/update_room")
     */
    public function updateReservationRoomJson(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
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
        $reservation = $reservationApi->getReservation($parameters['reservation_id']);
        if(is_array($reservation)){
            return new JsonResponse($reservation, 200, array());
        }
        $response = $reservationApi->updateReservationRoom($reservation, $parameters['room_id']);
        return new JsonResponse($response, 200, array());
    }

    /**
     * @Route("api/reservations/{reservationId}/update_confirmation/{confirmationCode}")
     */
    public function updateReservationConfirmationCode($reservationId, $confirmationCode, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservation = $reservationApi->getReservation($reservationId);
        $response = $reservationApi->updateReservationOriginUrl($reservation, $confirmationCode);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("no_auth/reservations/create")
     * @throws \Exception
     */
    public function creatReservation( Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, RoomApi $roomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);

        $logger->info("citizenship is this at controller " . $request->get('citizenship'));

        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        if (!DateTime::createFromFormat('Y-m-d', $request->get('date'))) {
            $response = array(
                'result_code' => 1,
                'result_message' => "Date not valid",
            );
            return new JsonResponse($response , 200, array());
        }

        $nowDate = new DateTime($request->get('date'));
        $now = new DateTime();

        if(strcmp($nowDate->format("Y-m-d"), $now->format("Y-m-d")) !== 0){
            $response = array(
                'result_code' => 1,
                'result_message' => "Date must be today",
            );
            return new JsonResponse($response , 200, array());
        }



        $response = $reservationApi->createReservation($request->get('room_ids'), $request->get('name'), $request->get('phone_number'),
            $request->get('email'), $request->get('check_in_date'), $request->get('check_out_date'), $request, $request->get('adult_guests'), $request->get('child_guests'), null, false, "website", "website", $request->get('smoking'), $request->get('gender'), $request->get('citizenship'));
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 201, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("no_auth/reservations/json/create")
     * @throws \Exception
     */
    public function creatReservationJson( Request $request, LoggerInterface $logger, ReservationApi $reservationApi): Response
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
        $logger->info($parameters['name']);

        if (!DateTime::createFromFormat('Y-m-d', $parameters['date'])) {
            $response = array(
                'result_code' => 1,
                'result_message' => "Date not valid",
            );
            return new JsonResponse($response , 200, array());
        }

        $nowDate = new DateTime($parameters['date']);
        $now = new DateTime();

        if(strcmp($nowDate->format("Y-m-d"), $now->format("Y-m-d")) !== 0){
            $response = array(
                'result_code' => 1,
                'result_message' => "Date must be today",
            );
            return new JsonResponse($response , 200, array());
        }

        $rooms = $parameters['rooms'];
        $roomIds = array();
        foreach ($rooms as $room ) {
            $logger->info("Room ID: " . $room['id']);
            $roomIds[] = $room['id'];
        }

        $response = $reservationApi->createReservation(implode(",",$roomIds), $parameters['name'], $parameters['phone_number'],
            $parameters['email'], $parameters['check_in_date'], $parameters['check_out_date'], $request, $parameters['guest']['adult_guests'], $parameters['guest']['child_guests'], null, false, "website", "website", $parameters['smoking'],$parameters['gender'],$parameters['citizenship']);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 201, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("/api/upload/reservations/")
     * @throws \Exception
     */
    public function uploadReservations( Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Internal server errors" , 500, array());
        }

        $file = $request->files->get('file');
        $logger->debug("File name is : " .$_FILES['file']['name'] );
        $ext =  $this->getExtension($_FILES['file']['name']);

        if (strcmp($ext, "dat")!== 0)
        {
            $logger->error("Invalid extension");
            return new JsonResponse("Unsupported Media Type" , 415, array());
        }

        if (empty($file))
        {
            $logger->info("No file specified");
            return new JsonResponse("No file specified" , 422, array());
        }

        $logger->info("File : " . file_get_contents($file));

        $response = $reservationApi->uploadReservations(file_get_contents($file), $request);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 201, array());
        $response->setCallback($callback);
        return $response;
    }

    function getExtension($string)
    {
        try {
            $parts = explode(".", $string);
            $ext = strtolower($parts[count($parts) - 1]);
        } catch (Exception $c) {
            $ext = "";
        }
        return $ext;
    }

    /**
     * @Route("/no_auth/reservations/import/ftp")
     * @throws \Exception
     */
    public function importFTPReservations( Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server errors" , 500, array());
        }

        $response = $reservationApi->importFTPReservations($request);

        return new JsonResponse($response, 200, array());
    }

    /**
     * @Route("no_auth/invoice/{reservationId}")
     * @throws \Exception
     */
    public function getInvoiceDetails($reservationId, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, RoomApi $roomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservation = $reservationApi->getReservation($reservationId);
        $invoiceHtml = new InvoiceHTML($entityManager, $logger);
        $html = $invoiceHtml->formatHtml($reservation);
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("no_auth/reviews/send/{propertyId}")
     * @throws \Exception
     */
    public function sendReviewRequest($propertyId, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $reservationApi->sendReviewRequest($propertyId);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("admin_api/reservations/{reservationId}/blockguest/{reason}")
     */
    public function blockGuest($reservationId, $reason, LoggerInterface $logger, Request $request,GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $guestApi->blockGuest($reservationId, $reason);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("admin_api/json/reservations/blockguest")
     */
    public function blockGuestJson(LoggerInterface $logger, Request $request,GuestApi $guestApi): Response
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
        $response = $guestApi->blockGuest($parameters['reservations_id'], $parameters['reason']);
        return new JsonResponse($response , 200, array());
    }

    /**
     * @Route("admin_api/reservation_addon/{addOnId}/delete")
     */
    public function removeAddOnFromReservation($addOnId, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('delete')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $addOnsApi->removeAddOnFromReservation($addOnId);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 204, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("admin_api/json/reservation_addon/delete")
     */
    public function removeAddOnFromReservationJson(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('delete')) {
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
        $response = $addOnsApi->removeAddOnFromReservation($parameters['add_on_id']);
        if ($response[0]['result_code'] === 0) {
            return new JsonResponse($response , 204, array());
        }else{
            $responseArray[] = array(
                'result_message' => $response[0]['result_message'],
                'result_code' => 1
            );
            return new JsonResponse($responseArray , 200, array());
        }
    }

    /**
     * @Route("api/json/reservation/{id}")
     */
    public function getReservationJson( $id, Request $request, LoggerInterface $logger, ReservationApi $api): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $reservation = $api->getReservation($id);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($reservation, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }

}