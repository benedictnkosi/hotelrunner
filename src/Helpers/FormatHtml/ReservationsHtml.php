<?php

namespace App\Helpers\FormatHtml;

use App\Entity\Reservations;
use App\Entity\Guest;
use App\Service\AddOnsApi;
use App\Service\CleaningApi;
use App\Service\DefectApi;
use App\Service\EmployeeApi;
use App\Service\GuestApi;
use App\Service\NotesApi;
use App\Service\PaymentApi;
use App\Service\RoomApi;
use DateInterval;
use DateTime;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;


class ReservationsHtml
{

    private $em;
    private $logger;
    private $defectApi;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->defectApi = new DefectApi($entityManager, $logger);

    }

    public function formatHtml($reservations, $period): string
    {

        $this->logger->debug("Starting Method: " . __METHOD__);
        $this->createCSV($reservations, $period);
        $this->createFlatFile($reservations, $period);

        $htmlString = "";
        $todayHeadingWritten = false;
        //if no reservations found

        if ($reservations === null) {
            return '<div class="reservation-item">
						<h4 class="guest-name">No reservations found</h4>
					</div>';
        }

        $htmlString .= '<div class="flexible display-none" id="res_div_message_div_' . $period . '" >
										<div class="flex-bottom">
											<div class="flex1" id="res_div_success_message_div_' . $period . '">
												<h5 id="res_div_success_message_' . $period . '"></h5>
											</div>
											<div  class="flex2" id="res_div_error_message_div_' . $period . '">
												<h5 id="res_div_error_message_' . $period . '"></h5>
											</div>
										</div>
									</div>';

        if($this->defectApi->isFunctionalityEnabled("download_reservations")) {
            $htmlString .= '<a href="/'.$period . '_reservations.csv" target="_blank" >Download CSV</a>
            <a href="/api/files/'.$period . '_reservations.dat" target="_blank">| Download Flat File</a>';
        }


        if (strcmp($period, 'past') === 0) {
            $numberOfDays = 180;
            for ($x = 0; $x <= $numberOfDays; $x++) {
                $todayDate = new DateTime();
                $todayDate->add(DateInterval::createFromDateString('yesterday'));
                $tempDate = $todayDate->sub(new DateInterval('P' . $x . 'D'));
                $htmlString .= $this->helper($tempDate, $reservations);
            }
        } else {
            $numberOfDays = 180;
            for ($x = 1; $x <= $numberOfDays; $x++) {
                $todayDate = new DateTime();
                $todayDate->add(DateInterval::createFromDateString('yesterday'));
                $tempDate = $todayDate->add(new DateInterval('P' . $x . 'D'));
                if (strcmp($period, 'pending') === 0) {
                    $htmlString .= $this->helper($tempDate, $reservations, false);
                } else {
                    $htmlString .= $this->helper($tempDate, $reservations);
                }

            }
        }


        return $htmlString;
    }


    function helper($tempDate, $reservations, $outputCheckOuts = true): string
    {

        $todayCheckIns = array();
        $todayCheckOuts = array();
        $htmlString = '';

        foreach ($reservations as $reservation) {
            if(is_array($reservation)){
                return "";
            }
            if (strcmp($reservation->getCheckIn()->format("Y-m-d"), $tempDate->format("Y-m-d")) == 0) {
                $todayCheckIns[] = ($reservation);
            }

            if (strcmp($reservation->getCheckOut()->format("Y-m-d"), $tempDate->format("Y-m-d")) == 0) {
                $todayCheckOuts[] = ($reservation);
            }
        }

        if (!empty($todayCheckIns) || (!empty($todayCheckOuts) && $outputCheckOuts)) {
            $htmlString .= '<div class="reservation-date-divider">
                            ' . $tempDate->format("d M") . '
                        </div>';
        }

        foreach ($todayCheckIns as $todayCheckIn) {
            $roomName = $todayCheckIn->getRoom()->getName();
            if($this->defectApi->isDefectEnabled("reservation_list_1")){
                $roomName = $roomName . "-d1801";
            }

                $htmlString .= '<div class="reservation-item" data-res-id="' . $todayCheckIn->getId() . '">
                         <div class="listing-description clickable open-reservation-details" data-res-id="' . $todayCheckIn->getId() . '">
                          <img class="listing-checkin-image listing-image" src="/admin/images/listing-checkin.png" data-res-id="' . $todayCheckIn->getId() . '"></img>
                        <img class="listing-image-origin" src="/admin/images/' . $todayCheckIn->getOrigin() . '.png" data-res-id="' . $todayCheckIn->getId() . '"></img>
                        <div class="listing-description-text" data-res-id="' . $todayCheckIn->getId() . '">'
                . $todayCheckIn->getGuest()->getName() . ' is expected to check-in 
                         <span class="listing-room-name" data-res-id="' . $todayCheckIn->getId() . '"> ' . $roomName . ' #' . $todayCheckIn->getId() . '</span>
                        </div>
                        </div>
                    </div>';
        }

        if ($outputCheckOuts) {
            foreach ($todayCheckOuts as $todayCheckOut) {
                $roomName = $todayCheckOut->getRoom()->getName();
                if($this->defectApi->isDefectEnabled("reservation_list_1")){
                    $roomName = $roomName . "-d1801";
                }

                $htmlString .= '<div class="reservation-item" data-res-id="' . $todayCheckOut->getId() . '">
                         <div class="listing-description clickable open-reservation-details" data-res-id="' . $todayCheckOut->getId() . '">
                          <img class="listing-checkin-image listing-image" src="/admin/images/listing-checkout.png" data-res-id="' . $todayCheckOut->getId() . '"></img>
                        <img class="listing-image-origin" src="/admin/images/' . $todayCheckOut->getOrigin() . '.png" data-res-id="' . $todayCheckOut->getId() . '"></img>
                        <div class="listing-description-text" data-res-id="' . $todayCheckOut->getId() . '">'
                    . $todayCheckOut->getGuest()->getName() . ' is expected to check-out 
                         <span class="listing-room-name" data-res-id="' . $todayCheckOut->getId() . '"> ' . $roomName . ' #' . $todayCheckOut->getId() . ' </span>
                        </div>
                        </div>
                    </div>';
            }
        }


        return $htmlString;
    }

    function createCSV($reservations, $fileName): void
    {
        try {
            $cfile = fopen($fileName . '_reservations.csv', 'w');

//Inserting the table headers
            $header_data = array('id', 'room_name', 'check_in', 'check_out', 'guest_name', 'phone_number', 'amount');
            fputcsv($cfile, $header_data);

//Data to be inserted
            $allReservations = array();

            foreach ($reservations as $reservation) {
                if(is_array($reservation)){
                    return;
                }
                $totalDays = intval($reservation->getCheckIn()->diff($reservation->getCheckOut())->format('%a'));
                $totalPrice = intval($reservation->getRoom()->getPrice()) * $totalDays;

                $gl = $totalPrice / 1.15;
                $vat = $totalPrice - $gl;
                $gl = round($gl, 2);
                $vat = round($vat, 2);

                $row = array($reservation->GetId(), $reservation->getRoom()->getName(), $reservation->getCheckIn()->format('Y-m-d'), $reservation->getCheckOut()->format('Y-m-d'), $reservation->getGuest()->getName(), $reservation->getGuest()->getPhoneNumber(), $totalPrice);
                $allReservations[] = $row;
                $row = array($reservation->GetId(), $reservation->getRoom()->getName(), $reservation->getCheckIn()->format('Y-m-d'), $reservation->getCheckOut()->format('Y-m-d'), $reservation->getGuest()->getName(), $reservation->getGuest()->getPhoneNumber(), $gl);
                $allReservations[] = $row;
                $row = array($reservation->GetId(), $reservation->getRoom()->getName(), $reservation->getCheckIn()->format('Y-m-d'), $reservation->getCheckOut()->format('Y-m-d'), $reservation->getGuest()->getName(), $reservation->getGuest()->getPhoneNumber(), $vat);
                $allReservations[] = $row;
            }

// save each row of the data
            foreach ($allReservations as $row) {
                fputcsv($cfile, $row);
            }

// Closing the file
            fclose($cfile);
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }


    }

    function createFlatFile($reservations, $fileName): void
    {
        try {
            $cfile = fopen(__DIR__ . '/../../../files/' . $fileName . '_reservations.dat', 'w');

//Data to be inserted
            $allReservations = array();

            foreach ($reservations as $reservation) {
                if(is_array($reservation)){
                    return;
                }
                $reservationId = str_pad($reservation->GetId(), 5, "0", STR_PAD_LEFT);
                $roomName = str_pad($reservation->getRoom()->getName(), 36);
                $roomPrice = str_pad($reservation->getRoom()->getPrice(), 9,"0", STR_PAD_LEFT);
                $checkIn = str_pad($reservation->getCheckIn()->format('Y-m-d'), 10);
                $checkOut = str_pad($reservation->getCheckOut()->format('Y-m-d'), 10);
                $guestName = str_pad($reservation->getGuest()->getName(), 36);
                $guestPhoneNumber = str_pad($reservation->getGuest()->getPhoneNumber(), 18);
                if($this->defectApi->isDefectEnabled("download_reservation_1")){
                    $guestPhoneNumber =  str_pad("0837917430", 18);
                }

                $origin = str_pad($reservation->getOrigin(), 46);
                $originURL = str_pad($reservation->getOriginUrl(), 46);
                $uid = str_pad($reservation->getUid(), 72);
                $additionalInformation = str_pad($reservation->getAdditionalInfo(), 108);
                $receivedOn = str_pad($reservation->getReceivedOn()->format('Y-m-d'), 10);
                $roomId = str_pad($reservation->getRoom()->getId(), 4, "0", STR_PAD_LEFT);
                $totalDays = intval($reservation->getCheckIn()->diff($reservation->getCheckOut())->format('%a'));
                $totalPrice = intval($reservation->getRoom()->getPrice()) * $totalDays;
                $status = str_pad($reservation->getStatus()->getName(), 12);

                $gl = $totalPrice / 1.15;
                if($this->defectApi->isDefectEnabled("download_reservation_2")){
                    $gl = $totalPrice / 1.14;
                }
                $vat = $totalPrice - $gl;
                $gl = round($gl, 2);
                $vat = round($vat, 2);
                $gl = str_pad($gl, 9,"0", STR_PAD_LEFT);
                $vat = str_pad($vat, 9,"0", STR_PAD_LEFT);
                $totalPrice = str_pad($totalPrice, 9,"0", STR_PAD_LEFT);

                $row = $reservationId. $roomName . $roomPrice . $checkIn . $checkOut. $guestName. $guestPhoneNumber . $origin . $originURL . $uid . $additionalInformation.  $receivedOn . $roomId .$status. "TP" .$totalPrice .  "\n";
                fwrite($cfile, $row);
                $row = $reservationId. $roomName . $roomPrice . $checkIn . $checkOut. $guestName. $guestPhoneNumber . $origin . $originURL . $uid . $additionalInformation.  $receivedOn . $roomId .$status. "GL" .$gl .  "\n";
                fwrite($cfile, $row);
                $row = $reservationId. $roomName . $roomPrice . $checkIn . $checkOut. $guestName. $guestPhoneNumber . $origin . $originURL . $uid . $additionalInformation.  $receivedOn . $roomId .$status. "VT" .$vat .  "\n";
                fwrite($cfile, $row);
            }

            fclose($cfile);
        } catch (\Exception $exception) {
            fclose($cfile);
            $this->logger->debug($exception->getMessage());
        }
    }
}