<?php

namespace App\Helpers\FormatHtml;

use App\Service\AddOnsApi;
use App\Service\BlockedRoomApi;
use App\Service\CleaningApi;
use App\Service\GuestApi;
use App\Service\NotesApi;
use App\Service\PaymentApi;
use App\Service\ReservationApi;
use App\Service\RoomApi;
use DateInterval;
use DateTime;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class CalendarHTML
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    public function formatHtml(): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $htmlString = "";

        $roomsApi = new RoomApi($this->em, $this->logger);
        $reservationApi = new ReservationApi($this->em, $this->logger);
        $blockRoomApi = new BlockedRoomApi($this->em, $this->logger);
        $cleaningApi = new CleaningApi($this->em, $this->logger);
        $paymentApi = new PaymentApi($this->em, $this->logger);

        $numberOfDays = 180;
        $numberOfFirstOfMonth = 0;

        //headings
        $htmlString .= "<tr><th class='calendar-table-header'>Room Name</th>";

        for ($x = 0; $x <= $numberOfDays; $x++) {
            $todayDate = new DateTime();
            $todayDate->add(DateInterval::createFromDateString('yesterday'));
            $tempDate = $todayDate->add(new DateInterval('P' . $x . 'D'));

            if (strcmp($tempDate->format('d'), "01") === 0) {
                $htmlString .= '<th class="new-month">' . $tempDate->format('M') . '</th>';
                $numberOfFirstOfMonth++;
            }

            if (strcmp($tempDate->format('D'), "Sat") == 0 || strcmp($tempDate->format('D'), "Sun") == 0) {
                $htmlString .= '<th class="weekend">' . $tempDate->format('D') . '<br>' . $tempDate->format('d') . '</th>';
            } else {
                $htmlString .= '<th>' . $tempDate->format('D') . '<br>' . $tempDate->format('d') . '</th>';
            }
        }
        $htmlString .= '</tr>';

        $rooms = $roomsApi->getRoomsEntities();
        foreach ($rooms as $room) {
            $htmlString .= '<tr><th class="headcol">' . $room->getName() . '</th>';
            $reservations = $reservationApi->getUpComingReservations( $room->getId(),false, true);
            $blockedRooms = $blockRoomApi->getBlockedRoomsByRoomId( $room->getId());

            if ($reservations === null && $blockedRooms === null) {
                $htmlString .= str_repeat('<td class="available"></td>', $numberOfDays + 1 + $numberOfFirstOfMonth);
            } else {
                for ($x = 0; $x <= $numberOfDays; $x++) {
                    $todayDate = new DateTime();
                    $todayDate->add(DateInterval::createFromDateString('yesterday'));
                    $tempDate = $todayDate->add(new DateInterval('P' . $x . 'D'));
                    $isDateBlocked = false;
                    $isDateBooked = false;
                    $isDateBookedButOpen = false;
                    $resID = "";
                    $guestName = "";
                    $blockNote = "";
                    $isCheckInDay = false;

                    if (strcmp($tempDate->format('d'), "01") === 0) {
                        $htmlString .= '<td class="new-month"></td>';
                    }
                    if($reservations !== null){
                        foreach ($reservations as $reservation) {
                            $isCheckInDay = false;
                            if ($tempDate >= $reservation->getCheckIn() && $tempDate < $reservation->getCheckOut()) {
                                if (strcasecmp($reservation->getStatus()->getName(), "confirmed") === 0
                                || strcasecmp($reservation->getStatus()->getName(), "opened") === 0) {
                                    $resID = $reservation->getId();
                                    $isDateBooked = true;
                                    $guestName = $reservation->getGuest()->getName();
                                    if (strcasecmp($tempDate->format("Y-m-d"), $reservation->getCheckIn()->format("Y-m-d")) === 0) {
                                        $this->logger->debug("Check in day is true because tempdate is " . $tempDate->format("Y-m-d") . " and res " . $reservation->getId() . " check in date is " . $reservation->getCheckIn()->format("Y-m-d"));
                                        $isCheckInDay = true;
                                    }
                                    break;
                                }

                            }
                        }
                    }

                   // $this->logger->debug("blocked rooms");
                    if ($blockedRooms != null) {

                        foreach ($blockedRooms as $blockedRoom) {
                            if ($tempDate >= $blockedRoom->getFromDate() && $tempDate < $blockedRoom->getToDate()) {
                                $this->logger->debug("date is blocked - temp " . $tempDate->format("Y-m-d") . " getFromDate " . $blockedRoom->getFromDate()->format("Y-m-d") . " getToDate " . $blockedRoom->getToDate()->format("Y-m-d"));
                                $isDateBlocked = true;
                                $blockNote = $blockedRoom->getComment();
                                break;
                            }
                        }
                    }

                    //$this->logger->debug("checking if date booked");
                    if ($isDateBooked || $isDateBookedButOpen) {
                        if ($isCheckInDay === true) {
                            $totalDays = intval($reservation->getCheckIn()->diff($reservation->getCheckOut())->format('%a'));
                            $amountDue = $reservationApi->getAmountDue($reservation);
                            $totalDiscount = $paymentApi->getReservationDiscountTotal($reservation->getId());
                            $halfRoomPrice = $reservation->getRoom()->getPrice()/2;
                            $this->logger->debug("Total days: " . $totalDays);
                            $this->logger->debug("amount due: " . $amountDue);

                            if (strcasecmp($reservation->getCheckInStatus(), "checked_in") === 0) {
                                //if one day booking and guest checked in and amount outstanding then its short stay
                                if($amountDue > $halfRoomPrice){
                                    $this->logger->info("amount due is greater than half the price ");
                                }

                                if($totalDays < 2
                                    && ($totalDiscount > 1)
                                    && (strcasecmp($reservation->getOrigin(), "website") === 0)){
                                    $htmlString .= '<td  class="booked checked_in clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . "- IN" .'"><img  src="/admin/images/timer.png"  data-res-id="' . $resID . '" alt="checkin" class="image_checkin"></td>';
                                }else{
                                    $htmlString .= '<td  class="booked checked_in clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . "- IN" .'"><img  src="/admin/images/' . $reservation->getOrigin() . '.png"  data-res-id="' . $resID . '" alt="checkin" class="image_checkin"></td>';
                                }
                            }else if (strcasecmp($reservation->getCheckInStatus(), "checked_out") === 0) {
                                if($totalDays < 2
                                    && ($totalDiscount > 1)
                                    && (strcasecmp($reservation->getOrigin(), "website") === 0)){
                                    $htmlString .= '<td  class="booked checked_out clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . "- Out" .'"><img  src="/admin/images/timer.png"  data-res-id="' . $resID . '" alt="checkin" class="image_checkin"></td>';
                                }else if (strcasecmp($reservation->getStatus()->getName(), "opened") === 0){
                                    $htmlString .= '<td  class="booked booked_opened_td checked_out clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . "- OUT" .'"><img  src="/admin/images/' . $reservation->getOrigin() . '.png"  data-res-id="' . $resID . '" alt="checkedout" class="image_checkin opened_booking"></td>';
                                }else{
                                    $htmlString .= '<td  class="booked checked_out clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . "- OUT" .'"><img  src="/admin/images/' . $reservation->getOrigin() . '.png"  data-res-id="' . $resID . '" alt="checkedout" class="image_checkin"></td>';
                                }
                            }else {
                                if (strcasecmp($reservation->getStatus()->getName(), "confirmed") === 0){
                                    $htmlString .= '<td  class="booked clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . '"><img  src="/admin/images/' . $reservation->getOrigin() . '.png"  data-res-id="' . $resID . '" alt="checkin" class="image_checkin"></td>';

                                }else if (strcasecmp($reservation->getStatus()->getName(), "opened") === 0){
                                    $htmlString .= '<td  class="booked clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . '"><img  src="/admin/images/' . $reservation->getOrigin() . '.png"  data-res-id="' . $resID . '" alt="checkin" class="image_checkin opened_booking"></td>';

                                }
                            }
                        } else {
                            $now = new DateTime();
                            if($cleaningApi->isCleaningRequiredToday($reservation) && (strcasecmp($tempDate->format("Y-m-d"), $now->format("Y-m-d")) === 0)){
                                $htmlString .= '<td  class="booked clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . '"><img  src="/admin/images/broom.ico"  data-res-id="' . $resID . '" alt="clean room" class="image_checkin"></td>';

                            }else{
                                $htmlString .= '<td  class="booked clickable open-reservation-details" data-res-id="' . $resID . '" title="' . $guestName . '"></td>';
                            }
                        }
                    } else if ($isDateBlocked) {
                        $htmlString .= '<td class="blocked" title="' . $blockNote . '"></td>';
                    }
                    else {
                        $htmlString .= '<td class="available"></td>';
                    }

                }
            }
            $htmlString .= '</tr>';
        }
        return $htmlString;
    }
}