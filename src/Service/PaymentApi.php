<?php

namespace App\Service;

use App\Entity\Payments;
use App\Entity\Reservations;
use App\Entity\ReservationStatus;
use App\Helpers\DatabaseHelper;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

require_once(__DIR__ . '/../app/application.php');

class PaymentApi
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private $defectApi;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->defectApi = new DefectApi($entityManager, $logger);

        if (session_id() === '') {
            $logger->info("Session id is empty");
            session_start();
        }
    }

    public function getReservationPayments($resId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //validate id is a number
            if(!is_numeric($resId)){
                return array(
                    'result_message' => "ID is not a number" ,
                    'result_code' => 1
                );
            }

            $payments = $this->em->getRepository(Payments::class)->findBy(array('reservation' => $resId));
            $this->logger->debug("no errors finding payments for reservation $resId. payment count " . count($payments));
            return $payments;
        } catch (Exception $ex) {
            $responseArray = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error("failed to get payments " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


    public function getPayment($paymentId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //validate id is a number
            if(!is_numeric($paymentId)){
                return array(
                    'result_message' => "ID is not a number" ,
                    'result_code' => 1
                );
            }
            return $this->em->getRepository(Payments::class)->findOneBy(array('id' => $paymentId));
        } catch (Exception $ex) {
            $responseArray = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error("failed to get payments " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getReservationPaymentsHtml($resId): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $payments = $this->em->getRepository(Payments::class)->findBy(array('reservation' => $resId));
            $html = "";
            foreach ($payments as $payment) {
                $html .= '<tr class="item">
					<td></td>
					<td>Payment</td>
					<td> ' . $payment->getDate()->format("d-M") . '</td>
					<td>-R' . number_format((float)$payment->getAmount(), 2, '.', '') . '</td>
				</tr>';
            }
            return $html;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error($ex->getMessage());
            return $ex->getMessage();
        }
    }

    public function addPayment($resId, $amount, $reference, $channel = null): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $resId = str_replace("[", "", $resId);
            $resId = str_replace("]", "", $resId);
            $reservationIdsArray = explode(",", $resId);
            $numberOfReservations = count($reservationIdsArray);

            //validate the amount is positive
            if(!is_numeric($amount) || intval($amount) < 1 ||  strlen($amount) < 1 || strlen($amount) > 4){
                $responseArray[] = array(
                    'result_code' => 1,
                    'result_message' => 'Amount is invalid'
                );
                return $responseArray;
            }

            foreach ($reservationIdsArray as $resId) {
                $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id' => $resId));
                if($reservation == null){
                    $responseArray[] = array(
                        'result_code' => 1,
                        'result_message' => 'Reservation not found'
                    );

                    return $responseArray;
                }



                $payment = new Payments();
                $now = new DateTime();

                //validate channel
                if(strcmp(strtolower($channel), "cash") !== 0
                && strcmp(strtolower($channel), "transfer") !== 0
                        && strcmp(strtolower($channel), "payfast") !== 0
                            && strcmp(strtolower($channel), "card") !== 0){
                    $responseArray[] = array(
                        'result_code' => 1,
                        'result_message' => 'Channel is invalid'
                    );
                    return $responseArray;
                }

                //validate that the ref is correct for card
                if(strcmp($channel, "card") == 0){
                    if (strlen($reference) !== 14
                        || strpos($reference, "/") !== 4
                        || strrpos($reference, "/") !== 7) {
                        $responseArray[] = array(
                            'result_code' => 1,
                            'result_message' => 'Payment reference is invalid'
                        );
                        return $responseArray;
                    }
                }

                //validate that the ref is not longer than 30 characters
                if(strcmp($channel, "transfer") == 0){
                    if (strlen($reference) > 30 || strlen($reference) < 4){
                        $responseArray[] = array(
                            'result_code' => 1,
                            'result_message' => 'Payment reference is invalid'
                        );
                        return $responseArray;
                    }
                }


                //validate that first time guests do not pay by cash
                $guestApi = new GuestApi($this->em, $this->logger);
                $stayCount = $guestApi->getGuestStaysCount($reservation->getGuest()->getId());

                if($stayCount == 0 && strcmp($channel, "cash") == 0){
                    $responseArray[] = array(
                        'result_code' => 1,
                        'result_message' => 'First time guests are not allowed to pay by cash'
                    );
                    return $responseArray;
                }

                //validate A user should not be able to pay beyond what is due
                $due = $this->getTotalDue($reservation->getId());
                if($amount > $due){
                    $responseArray[] = array(
                        'result_code' => 1,
                        'result_message' => "You cannot pay more than the due amount of $due");
                    return $responseArray;
                }

                $payment->setReservation($reservation);
                $amountPerReservation = intval($amount) / intval($numberOfReservations);
                if($this->defectApi->isDefectEnabled("payment_2")){
                    if($amountPerReservation > 999){
                        $amountPerReservation = 999;
                    }
                }

                $payment->setAmount($amountPerReservation);
                $payment->setDate($now);
                if($this->defectApi->isDefectEnabled("payment_1")){
                    $payment->setChannel("transfer");
                }else{
                    $payment->setChannel($channel);
                }

                $payment->SetReference($reference);

                $this->logger->debug("reservation status is " . $reservation->getStatus()->getName());

                //updated status to confirmed if it is pending
                if (strcmp($reservation->getStatus()->getName(), "pending") === 0) {
                    $roomApi = new RoomApi($this->em, $this->logger);

                    //is amount 50% or more of the nightly price
                    $halfRoomPrice = intval($reservation->getRoom()->getPrice())/2;

                    if($halfRoomPrice > intval($amountPerReservation)){
                        $responseArray[] = array(
                            'result_code' => 1,
                            'result_message' => 'Amount must be at least 50% of the room price'
                        );
                        return $responseArray;
                    }
                    $isRoomAvailable = $roomApi->isRoomAvailable($reservation->getRoom()->getId(), $reservation->getCheckIn()->format("Y-m-d"), $reservation->getCheckOut()->format("Y-m-d"));
                    if ($isRoomAvailable) {
                        $this->logger->debug("room is available");
                        $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => "confirmed"));
                        $reservation->setStatus($status);
                        //commit the reservation changes
                        $this->em->persist($reservation);
                        $this->em->flush($reservation);

                        //commit the payment changes
                        $this->em->persist($payment);
                        $this->em->flush($payment);

                        //block connected Room
                        $blockRoomApi = new BlockedRoomApi($this->em, $this->logger);
                        if($reservation->getRoom()->getLinkedRoom() !== 0){
                            $blockRoomApi->blockRoom($reservation->getRoom()->getLinkedRoom(), $reservation->getCheckIn()->format("Y-m-d"), $reservation->getCheckOut()->format("Y-m-d"), "Connected Room Booked ", $reservation->getId());
                        }

                        //check google ads notification
                        $now = new DateTime();
                        if (strcmp($reservation->getCheckIn()->format("Y-m-d"), $now->format("Y-m-d")) === 0) {
                            $notificationApi = new NotificationApi($this->em, $this->logger);
                            //$notificationApi->updateAdsNotification($reservation->getRoom()->getProperty()->getId());
                        }


                        $this->sendEmailToGuest($reservation, $amountPerReservation);
                        $responseArray[] = array(
                            'result_code' => 0,
                            'result_message' => 'Successfully added payment',
                            'payment_id' => $payment->getId()
                        );

                        $this->logger->debug("no errors adding payment for reservation $resId. amount $amount");
                    } else {
                        if (strcmp($channel, "payfast") === 0) {
                            $communicationApi = new CommunicationApi($this->em, $this->logger);

                            //send email to guest house
                            $emailBody = file_get_contents(__DIR__ . '/../email_template/failed_payment_to_host.html');
                            $emailBody = str_replace("reservation_id", $reservation->getId(), $emailBody);
                            $emailBody = str_replace("amount_paid", $amountPerReservation, $emailBody);
                            $emailBody = str_replace("property_name", $reservation->getRoom()->getProperty()->getName(), $emailBody);

                            $communicationApi->sendEmailViaGmail(ALUVEAPP_ADMIN_EMAIL, $reservation->getRoom()->getProperty()->getEmailAddress(), $emailBody, 'Aluve App - Adding payment failed');

                            //send email to guest
                            $emailBody = file_get_contents(__DIR__ . '/../email_template/failed_payment_to_guest.html');
                            $emailBody = str_replace("reservation_id", $reservation->getId(), $emailBody);
                            $emailBody = str_replace("amount_paid", $amountPerReservation, $emailBody);
                            $emailBody = str_replace("property_name", $reservation->getRoom()->getProperty()->getName(), $emailBody);
                            $emailBody = str_replace("property_email", $reservation->getRoom()->getProperty()->getEmailAddress(), $emailBody);
                            $emailBody = str_replace("property_number", $reservation->getRoom()->getProperty()->getPhoneNumber(), $emailBody);
                            $emailBody = str_replace("guest_name", $reservation->getGuest()->getName(), $emailBody);

                            $communicationApi->sendEmailViaGmail(ALUVEAPP_ADMIN_EMAIL, $reservation->getGuest()->getEmail(), $emailBody, 'Aluve App - Adding payment failed', $reservation->getRoom()->getProperty()->getName(), $reservation->getRoom()->getProperty()->getEmailAddress());
                        }

                        $responseArray[] = array(
                            'result_code' => 1,
                            'result_message' => 'This room is not available anymore. payment not added'
                        );

                    }
                } else {
                    //commit the payment changes
                    $this->em->persist($payment);
                    $this->em->flush($payment);
                    $responseArray[] = array(
                        'result_code' => 0,
                        'result_message' => 'Successfully added payment',
                        'payment_id' => $payment->getId()
                    );
                    $this->sendEmailToGuest($reservation, $amountPerReservation);
                }
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error("failed to get payments " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


    public function uploadPayment($paymentString)
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $payments = explode("\n", trim($paymentString));
        $this->logger->info("array lines: " . sizeof($payments));
        $responseArray = array();
        foreach($payments as $payment) {
            $resId = trim(substr($payment,0,4));
            $amount = trim(substr($payment,4,4));
            $reference = trim(substr($payment,8,28));
            $channel = "payfast";

            $this->logger->info("res id field: " . $resId);
            $this->logger->info("amount field: " . $amount);
            $this->logger->info("reference field: " . $reference);
            $this->logger->info("channel field: " . $channel);

            $response = $this->addPayment($resId, $amount, $reference, $channel);
            $responseArray[] = array(
                'result_code' => $response[0]['result_code'],
                'result_message' => $response[0]['result_message'],
            );
        }
        return $responseArray;
    }


    public function addDiscount($resId, $amount, $channel = null): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id' => $resId));
            $payment = new Payments();
            if($reservation == null){
                return array(
                    'result_message' => "Reservation not found" ,
                    'result_code' => 1
                );
            }

            //validate amount
            if (strlen($amount) > 4 || !is_numeric($amount) || intval($amount) < 1) {
                $responseArray[] = array(
                    'result_message' => "Amount is invalid",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //validate reservation is for one night
            $totalDays = intval($reservation->getCheckIn()->diff($reservation->getCheckOut())->format('%a'));
            if($totalDays > 1){
                return array(
                    'result_message' => "Discount only allowed for one-night reservations" ,
                    'result_code' => 1
                );
            }

            //validate Discount cannot be more than 50% of the price of the reservation.
            $halfRoomPrice = $reservation->getRoom()->getPrice() / 2;
            if($amount > $halfRoomPrice){
                return array(
                    'result_message' => "Discount cannot be more than 50% of the price of the room" ,
                    'result_code' => 1
                );
            }

            $payment->setReservation($reservation);

            if($this->defectApi->isDefectEnabled("view_reservation_8")){
                $amount *= -1;
            }

            $payment->setAmount($amount);
            $payment->setDate(new DateTime());
            $payment->setChannel($channel);
            $payment->setDiscount(true);
            $payment->setReference("none");

            //commit the payment changes
            $this->em->persist($payment);
            $this->em->flush($payment);

            $responseArray = array(
                'result_code' => 0,
                'result_message' => 'Successfully added discount'
            );
        } catch (Exception $ex) {
            $responseArray = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error("failed to add discount " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getTotalDue($resId): float|int|array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id' => $resId));
            $guest = $reservation->getGuest();
            $roomPrice = 0;
            if (strcasecmp($reservation->getOrigin(), "website") == 0) {
                $roomPrice = $reservation->getRoom()->getPrice();
            }

            $totalDays = intval($reservation->getCheckIn()->diff($reservation->getCheckOut())->format('%a'));

            //discount based on number of days booked
            if($totalDays > 6 && $totalDays < 28 ){
                //$roomPrice = $roomPrice * 0.9;
            }elseif($totalDays > 27){
                //$roomPrice = $roomPrice * 0.7;
            }

            //apply discount based on loyalty rewards
            $guestApi = new GuestApi($this->em, $this->logger);
            $stayCount = $guestApi->getGuestStaysCount($guest->getId());

            if($guest->isRewards() && $this->defectApi->isFunctionalityEnabled("loyalty_rewards")){
                //apply discount based on number of stays
                if($stayCount < 10){
                    $roomPrice = $roomPrice * 0.9;
                }elseif($stayCount < 20){
                    $roomPrice = $roomPrice * 0.8;
                }else{
                    $roomPrice = $roomPrice * 0.7;
                }
            }

            $numberOfWeekendNights = $this->getNumberOfWeekendNights($reservation->getCheckIn(), $reservation->getCheckOut());
            $totalPriceForWeekends = 50 * $numberOfWeekendNights;

            $addOnsApi = new AddOnsApi($this->em, $this->logger);
            $addOns = $addOnsApi->getReservationAddOns($resId);

            $this->logger->debug("looping add ons for reservation " . $resId . " add on count " . count($addOns));
            $totalPriceForAllAdOns = 0;
            foreach ($addOns as $addOn) {
                $totalPriceForAllAdOns += (intVal($addOn->getAddOn()->getPrice()) * intval($addOn->getQuantity()));
            }
            $totalPrice = intval($roomPrice) * $totalDays;
            $totalPrice += $totalPriceForAllAdOns + $totalPriceForWeekends;

            //payments
            $this->logger->debug("calculating payments " . $resId);
            $payments = $this->getReservationPayments($resId);
            $totalPayment = 0;
            foreach ($payments as $payment) {
                $totalPayment += (intVal($payment->getAmount()));
            }

            $due = $totalPrice - $totalPayment;
            $this->logger->debug("total price is " . $totalPrice);
            $this->logger->debug("total payments is " . $totalPayment);
            $this->logger->debug("Due amount is $due");
            $this->logger->debug("room price is $roomPrice");
            $this->logger->debug("days is $totalDays");
            $this->logger->debug("adons is $totalPriceForAllAdOns");
            return $due;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error("failed to get payments " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }



    function sendEmailToGuest($reservation, $amountPaid): void
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            //send email to guest
            $amountDue = $this->getTotalDue($reservation->getId());
            $emailBody = file_get_contents(__DIR__ . '/../email_template/thank_you_for_payment.html');
            $emailBody = str_replace("guest_name", $reservation->getGuest()->getName(), $emailBody);
            $emailBody = str_replace("amount_paid", $amountPaid, $emailBody);
            $emailBody = str_replace("amount_balance", $amountDue, $emailBody);
            $emailBody = str_replace("server_name", $reservation->getRoom()->getProperty()->getServerName(), $emailBody);
            $emailBody = str_replace("reservation_id", $reservation->getId(), $emailBody);
            $emailBody = str_replace("property_name", $reservation->getRoom()->getProperty()->getName(), $emailBody);
            $emailBody = str_replace("room_name", $reservation->getRoom()->getName(), $emailBody);

            $communicationApi = new CommunicationApi($this->em, $this->logger);
            $communicationApi->sendEmailViaGmail(ALUVEAPP_ADMIN_EMAIL, $reservation->getGuest()->getEmail(), $emailBody, $reservation->getRoom()->getProperty()->getName() . '- Thank you for payment', $reservation->getRoom()->getProperty()->getName(), $reservation->getRoom()->getProperty()->getEmailAddress());
            $this->logger->debug("Successfully sent email to guest");
        } catch (Exception $ex) {
            $this->logger->error(print_r($ex, true));
        }
    }

    public function getReservationPaymentsTotal($resId): int
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $payments = $this->em->getRepository(Payments::class)->findBy(array('reservation' => $resId));
            $totalPayment = 0;
            foreach ($payments as $payment) {
                $totalPayment += (intVal($payment->getAmount()));
            }
            return $totalPayment;
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return 0;
        }
    }

    public function getReservationDiscountTotal($resId): int
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $payments = $this->em->getRepository(Payments::class)->findBy(array('reservation' => $resId, 'channel' => 'discount'));
            $totalPayment = 0;
            foreach ($payments as $payment) {
                $totalPayment += (intVal($payment->getAmount()));
            }
            return $totalPayment;
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return 0;
        }
    }

    public function getCashReport($startDate, $endDate, $channel)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();

        try {

            $sql = "SELECT SUM(amount) as totalCash FROM `payments`
            WHERE channel = '".$channel."'
            and   DATE(`date`) >= '" . $startDate . "'
            and  DATE(`date`) <= '" . $endDate . "'";

            $this->logger->info($sql);

            //echo $sql;
            $databaseHelper = new DatabaseHelper($this->logger);
            $result = $databaseHelper->queryDatabase($sql);


            if (!$result) {
                $responseArray[] = array(
                    'result_message' => 0,
                    'result_code' => 0
                );
            } else {
                $amount = 0;
                while ($results = $result->fetch_assoc()) {
                    if($results["totalCash"] !== null){
                        $amount = $results["totalCash"];
                    }

                    $this->logger->info("amount is " . $results["totalCash"]);
                }
                $responseArray[] = array(
                    'result_message' => number_format($amount,2),
                    'result_code' => 0
                );
            }
            return $responseArray;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error("failed to get occupancy " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getCashReportByDay($startDate, $endDate,$channel): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $htmlResponse = "<tr><th>Date</th><th>Amount</th></tr>";

        try {

            if (strlen($channel) > 50 || strlen($channel) == 0) {
                $responseArray[] = array(
                    'result_message' => "Channel length should be between 1 and 50",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //validate dates

            if (strlen($startDate)  == 0 || strlen($endDate) == 0) {
                $responseArray[] = array(
                    'result_message' => "Dates are required",
                    'result_code' => 1
                );
                return $responseArray;
            }

            if (!DateTime::createFromFormat('Y-m-d', $startDate)) {
                return array(
                    'result_message' => "From date invalid",
                    'result_code' => 1
                );
            }

            if (!DateTime::createFromFormat('Y-m-d', $endDate)) {
                return array(
                    'result_message' => "To date invalid",
                    'result_code' => 1
                );
            }

            $checkInDateDateObject = new DateTime($startDate);
            $checkOutDateDateObject = new DateTime($endDate);

            //validate checkin dates
            if (strlen($startDate) < 1 || strlen($endDate) < 1) {
                return array(
                    'result_message' => "From and to date is mandatory",
                    'result_code' => 1
                );
            }

            if (strcmp($startDate, $endDate) == 0) {
                return array(
                    'result_message' => "From and to date can not be the same",
                    'result_code' => 1
                );
            }


            //validate checkin dates
            if ($checkInDateDateObject > $checkOutDateDateObject) {
                return array(
                    'result_message' => "From date can not be after to date",
                    'result_code' => 1
                );
            }

            $now = new DateTime('tomorrow midnight');

            if ($checkInDateDateObject > $now) {
                return array(
                    'result_message' => "From date can not be in the future",
                    'result_code' => 1
                );
            }

            $sql = "SELECT SUM(amount) as totalCash, LEFT( date, 10 ) as day FROM `payments`
            WHERE channel = '".$channel."'
            and   DATE(`date`) >= '" . $startDate . "'
            and  DATE(`date`) <= '" . $endDate . "'
GROUP BY LEFT( date, 10 ) 
order by date desc";

            $this->logger->info($sql);

            //echo $sql;
            $databaseHelper = new DatabaseHelper($this->logger);
            $result = $databaseHelper->queryDatabase($sql);

            if ($result) {
                while ($results = $result->fetch_assoc()) {
                    $htmlResponse .= "<tr><td>".$results["day"] ."</td><td>".$results["totalCash"]."</td></tr>";
                }
            }
            return $htmlResponse;
        } catch (Exception $ex) {

        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $htmlResponse;
    }

    public function getCashReportByDayJson($startDate, $endDate,$channel): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();


        try {

            if (strlen($channel) > 50 || strlen($channel) == 0) {
                $responseArray[] = array(
                    'result_message' => "Channel length should be between 1 and 50",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //validate dates

            if (strlen($startDate)  < 1 || strlen($endDate) < 1) {
                $responseArray[] = array(
                    'result_message' => "Dates are required",
                    'result_code' => 1
                );
                return $responseArray;
            }

            if (!DateTime::createFromFormat('Y-m-d', $startDate)) {
                return array(
                    'result_message' => "From date invalid",
                    'result_code' => 1
                );
            }

            if (!DateTime::createFromFormat('Y-m-d', $endDate)) {
                return array(
                    'result_message' => "To date invalid",
                    'result_code' => 1
                );
            }

            $checkInDateDateObject = new DateTime($startDate);
            $checkOutDateDateObject = new DateTime($endDate);

            //validate checkin dates
            if (strlen($startDate) < 1 || strlen($endDate) < 1) {
                return array(
                    'result_message' => "From and to date is mandatory",
                    'result_code' => 1
                );
            }

            if (strcmp($startDate, $endDate) == 0) {
                return array(
                    'result_message' => "From and to date can not be the same",
                    'result_code' => 1
                );
            }


            //validate checkin dates
            if ($checkInDateDateObject > $checkOutDateDateObject) {
                return array(
                    'result_message' => "From date can not be after to date",
                    'result_code' => 1
                );
            }

            $now = new DateTime('tomorrow midnight');

            if ($checkInDateDateObject > $now) {
                return array(
                    'result_message' => "From date can not be in the future",
                    'result_code' => 1
                );
            }


            $sql = "SELECT SUM(amount) as totalCash, LEFT( date, 10 ) as day FROM `payments`
            WHERE channel = '".$channel."'
            and   DATE(`date`) >= '" . $startDate . "'
            and  DATE(`date`) <= '" . $endDate . "'
GROUP BY LEFT( date, 10 ), date 
order by date desc";

            $this->logger->info($sql);

            //echo $sql;
            $databaseHelper = new DatabaseHelper($this->logger);
            $result = $databaseHelper->queryDatabase($sql);

            if ($result) {
                while ($results = $result->fetch_assoc()) {
                    $responseArray[] = array(
                        'date' => $results["day"],
                        'amount' => $results["totalCash"]
                    );
                }
            }
        } catch (Exception $ex) {
            $this->logger->debug("Exception: " . $ex->getMessage());
            $responseArray = array(
                'result_code' => 1,
                'result_message' => 'Exception occurred while getting payments'
            );
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getCashReportAllTransactions($startDate, $endDate,$channel): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $htmlResponse = "<tr><th>Date</th><th>Amount</th><th>Reference</th><th>Reservation</th></tr>";

        try {

            $sql = "SELECT amount, date, reservation_id, reference FROM `payments`
            WHERE channel = '".$channel."'
            and   DATE(`date`) >= '" . $startDate . "'
            and  DATE(`date`) <= '" . $endDate . "'
order by date desc";

            $this->logger->info($sql);

            //echo $sql;
            $databaseHelper = new DatabaseHelper($this->logger);
            $result = $databaseHelper->queryDatabase($sql);

            if ($result) {
                while ($results = $result->fetch_assoc()) {
                    $htmlResponse .= "<tr><td>".$results["date"] ."</td><td>".$results["amount"]."</td><td>".$results["reference"]."</td><td>".$results["reservation_id"]."</td></tr>";
                }
            }
            return $htmlResponse;
        } catch (Exception $ex) {

        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $htmlResponse;
    }

    public function getCashReportAllTransactionsJson($startDate, $endDate,$channel): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();

        try {

            if (strlen($channel) > 50 || strlen($channel) == 0) {
                $responseArray[] = array(
                    'result_message' => "Channel length should be between 1 and 50",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //validate dates

            if (strlen($startDate)  < 1 || strlen($endDate) < 1) {
                $responseArray[] = array(
                    'result_message' => "Dates are required",
                    'result_code' => 1
                );
                return $responseArray;
            }

            if (!DateTime::createFromFormat('Y-m-d', $startDate)) {
                return array(
                    'result_message' => "From date invalid",
                    'result_code' => 1
                );
            }

            if (!DateTime::createFromFormat('Y-m-d', $endDate)) {
                return array(
                    'result_message' => "To date invalid",
                    'result_code' => 1
                );
            }

            $checkInDateDateObject = new DateTime($startDate);
            $checkOutDateDateObject = new DateTime($endDate);

            //validate checkin dates
            if (strlen($startDate) < 1 || strlen($endDate) < 1) {
                return array(
                    'result_message' => "From and to date is mandatory",
                    'result_code' => 1
                );
            }

            if (strcmp($startDate, $endDate) == 0) {
                return array(
                    'result_message' => "From and to date can not be the same",
                    'result_code' => 1
                );
            }


            //validate checkin dates
            if ($checkInDateDateObject > $checkOutDateDateObject) {
                return array(
                    'result_message' => "From date can not be after to date",
                    'result_code' => 1
                );
            }

            $now = new DateTime('tomorrow midnight');

            if ($checkInDateDateObject > $now) {
                return array(
                    'result_message' => "From date can not be in the future",
                    'result_code' => 1
                );
            }

            $sql = "SELECT id, amount, date, reservation_id, reference, channel, discount FROM `payments`
            WHERE channel = '".$channel."'
            and   DATE(`date`) >= '" . $startDate . "'
            and  DATE(`date`) <= '" . $endDate . "'
order by date desc";

            $this->logger->info($sql);

            //echo $sql;
            $databaseHelper = new DatabaseHelper($this->logger);
            $result = $databaseHelper->queryDatabase($sql);

            if ($result) {
                while ($results = $result->fetch_assoc()) {
                    $responseArray[] = array(
                        'date' => $results["date"],
                        'amount' => $results["amount"],
                        'reference' => $results["reference"],
                        'reservation_id' => $results["reservation_id"],
                        'channel' => $results["channel"],
                        'discount' => $results["discount"],
                        'id' => $results["id"]
                    );
                }
            }
        } catch (Exception $ex) {
            $this->logger->debug("Exception: " . $ex->getMessage());
            $responseArray = array(
                'result_code' => 1,
                'result_message' => 'Exception occurred while getting payments'
            );
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function removePayment($paymentId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $payment = $this->em->getRepository(Payments::class)->findOneBy(array('id' => $paymentId));
            if($payment == null){
                return array(
                    'result_message' => "Payment not found",
                    'result_code' => 1
                );
            }
            $this->em->remove($payment);
            $this->em->flush($payment);

            $responseArray = array(
                'result_message' => "Successfully removed payment",
                'result_code' => 0
            );

        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            $responseArray = array(
                'result_message' =>$ex->getMessage(),
                'result_code' => 1
            );
        }

        return $responseArray;
    }

    /**
     * @throws \Exception
     */
    function getNumberOfWeekendNights($checkIn, $checkOut): int
    {
        $numberOfWeekendDays = 0;
        $numberOfDays = 0;
        $newCheckIn = new DateTime($checkIn->format("m/d/Y"));
        while (strcmp($newCheckIn->format("m/d/Y"), $checkOut->format("m/d/Y")) !== 0 && $numberOfDays < 999) {
            if ($this->isWeekend($checkOut)) {
                $numberOfWeekendDays++;
            }

            $numberOfDays++;
            $newCheckIn->modify('+1 day');
            $this->logger->debug("new check in date" . $newCheckIn->format("m/d/Y"));
            $this->logger->debug("checkout date" . $checkOut->format("m/d/Y"));

        }

        return $numberOfWeekendDays;
    }

    function isWeekend($date): bool
    {
        $weekDay = date('N', strtotime($date->format("m/d/Y")));
        $this->logger->debug("weekday is " . $weekDay);

        return ($weekDay == 6 || $weekDay == 7);
    }
}