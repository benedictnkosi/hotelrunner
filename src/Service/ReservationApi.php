<?php

namespace App\Service;

use App\Entity\FailedUids;
use App\Entity\Reservations;
use App\Entity\ReservationStatus;
use App\Entity\Rooms;
use App\Helpers\SMSHelper;
use DateInterval;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once(__DIR__ . '/../app/application.php');

class ReservationApi
{
    private $em;
    private $logger;
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

    public function getReservation($resId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        try {
            //validate id is a number
            if (!is_numeric($resId)) {
                return array(
                    'result_message' => "Reservation id is not a number",
                    'result_code' => 1
                );
            }
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id' => $resId));
            if ($reservation == null) {
                $responseArray = array(
                    'result_message' => "Reservation not found",
                    'result_code' => 1
                );
            } else {
                return $reservation;
            }
        } catch (Exception $ex) {
            $responseArray = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getReservationJson($resId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id' => $resId));
            if ($reservation === null) {
                $responseArray[] = array(
                    'result_message' => "Reservation not found for id $resId",
                    'result_code' => 1
                );

            } else {
                $paymentApi = new PaymentApi($this->em, $this->logger);
                $payments = $paymentApi->getReservationPayments($reservation->GetId());
                $paymentsHtml = "";
                $totalPayment = 0;
                foreach ($payments as $payment) {
                    $paymentsHtml .= '<p class="small-font-italic"> ' . $payment->getDate()->format("d-M") . ' - R' . number_format((float)$payment->getAmount(), 2, '.', '') . '</p>';
                    $totalPayment += (intVal($payment->getAmount()));
                }

                $responseArray[] = array(
                    'id' => $reservation->GetId(),
                    'check_in' => $reservation->getCheckIn()->format('Y-m-d'),
                    'check_out' => $reservation->getCheckOut()->format('Y-m-d'),
                    'status' => $reservation->getStatus()->getId(),
                    'guest_name' => $reservation->getGuest()->getName(),
                    'guest_id' => $reservation->getGuest()->getId(),
                    'check_in_status' => $reservation->getCheckInStatus(),
                    'check_in_time' => $reservation->getCheckInTime(),
                    'check_out_time' => $reservation->getCheckOutTime(),
                    'checked_in_time' => $reservation->getCheckedInTime(),
                    'received_on' => $reservation->getReceivedOn()->format('Y-m-d H:i:s'),
                    'room_id' => $reservation->getRoom()->getId(),
                    'room_name' => $reservation->getRoom()->getName(),
                    'total_paid' => $totalPayment,
                    'guest_phone_number' => $reservation->getGuest()->getPhoneNumber(),
                    'result_code' => 0
                );
            }


            return $responseArray;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


    public function getReservationByUID($uid)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));
            return $this->em->getRepository(Reservations::class)->findOneBy(array('uid' => $uid, 'status' => $status));
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return null;
    }

    public function getPendingReservations()
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $reservations = null;
        try {
            $datetime = new DateTime('today');
            $datetime->sub(new DateInterval('P1D'));

            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'pending'));

            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
                JOIN r.room a
                JOIN a.property p
            WHERE p.id = " . $_SESSION['PROPERTY_ID'] . "
            and r.checkIn >= '" . $datetime->format('Y-m-d') . "'
            and r.status = '" . $status->getId() . "'
            order by r.checkIn asc")
                ->getResult();

        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );

            $this->logger->error(print_r($responseArray, true));
            return $responseArray;
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);

        if (empty($reservations)) {
            $responseArray[] = array(
                'result_message' => "No reservations found",
                'result_code' => 1
            );
            return $responseArray;
        }
        return $reservations;
    }

    public function getUpComingReservations($roomId = 0, $includeOpened = false, $includeStayOvers = false)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $reservations = null;
        try {
            $roomFilter = "";
            if ($roomId != 0) {
                $roomFilter = " and r.room = $roomId ";
            }
            $now = new DateTime();
            $datetime = new DateTime();
            $maxFutureDate = $datetime->add(new DateInterval('P180D'));
            $confirmedStatus = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));
            $openedStatus = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'opened'));

            $excludeStayOverSql = "and r.checkIn >= '" . $now->format('Y-m-d') . "'";
            if ($includeStayOvers) {
                $excludeStayOverSql = "";
            }

            $includeOpenedSql = "and (r.status = '" . $confirmedStatus->getId() . "') ";
            if ($includeOpened) {
                $includeOpenedSql = "and (r.status = '" . $confirmedStatus->getId() . "' or r.status = '" . $openedStatus->getId() . "' ) ";
            }
            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
                JOIN r.room a
                JOIN a.property p
            WHERE p.id = " . $_SESSION["PROPERTY_ID"] . "
            and r.checkIn <= '" . $maxFutureDate->format('Y-m-d') . "'
            and r.checkOut >= '" . $now->format('Y-m-d') . "'
            $excludeStayOverSql 
            $roomFilter  
            $includeOpenedSql
            order by r.checkIn asc ")
                ->getResult();
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
            return $responseArray;
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);

        if (empty($reservations)) {
            $responseArray[] = array(
                'result_message' => "No reservations found",
                'result_code' => 1
            );
            return $responseArray;
        }
        return $reservations;
    }

    public function getPastReservations()
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $reservations = null;
        try {
            $datetime = new DateTime();
            $now = new DateTime('today midnight');
            $maxPastDate = $now->sub(new DateInterval('P180D'));
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));

            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
                JOIN r.room a
                JOIN a.property p
            WHERE p.id = " . $_SESSION['PROPERTY_ID'] . "
            and r.checkOut < '" . $datetime->format('Y-m-d') . "'
            and r.checkIn > '" . $maxPastDate->format('Y-m-d') . "'
            and r.status = '" . $status->getId() . "'
            order by r.checkOut desc")
                ->getResult();
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
            return $responseArray;
        }


        $this->logger->debug("Ending Method before the return: " . __METHOD__);

        if (empty($reservations)) {
            $responseArray[] = array(
                'result_message' => "No reservations found",
                'result_code' => 1
            );
            return $responseArray;
        }

        return $reservations;
    }

    public function getCheckOutReservation($propertyId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $reservations = null;
        try {
            $datetime = new DateTime();
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));


            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
                JOIN r.room a
                JOIN a.property p
            where p.id = " . $propertyId . "
            and r.checkOut = '" . $datetime->format('Y-m-d') . "'
            and r.status = '" . $status->getId() . "'
            order by r.checkOut desc")
                ->getResult();


        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
            return $responseArray;
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);

        return $reservations;
    }

    public function getReservationsByRoomAndDaysToCheckIn($roomId, $days)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $now = new DateTime('today midnight');
            $checkInDate = $now->add(new DateInterval("P" . $days . "D"));
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));

            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
            WHERE r.checkIn = '" . $checkInDate->format('Y-m-d') . "'
            and r.room = $roomId 
            and r.status = " . $status->getId())
                ->getResult();

            if (empty($reservations)) {
                return null;
            }

            return $reservations;

        } catch (Exception $exception) {
            return null;
        }
    }

    public function getReservationsByRoomAndDaysAfterCheckOut($roomId, $days)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $now = new DateTime('today midnight');
            $checkOutDate = $now->sub(new DateInterval("P" . ($days * -1) . "D"));
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));

            $query = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
            WHERE r.checkOut = '" . $checkOutDate->format('Y-m-d') . "'
            and r.room = $roomId 
            and r.status = " . $status->getId());

            $this->logger->debug("before query " . $query->getDQL());

            $reservations = $query->getResult();


            if (empty($reservations)) {
                return null;
            }

            return $reservations;

        } catch (Exception $ex) {
            $this->logger->debug($ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString());
            return null;
        }
    }

    public function getReservationsByOriginalRoomAndOrigin($roomId, $origin)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $now = new DateTime('today midnight');
            $yesterdayDate = $now->sub(new DateInterval("P1D"));
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));

            $this->logger->debug("query " . $roomId . " " . $origin);

            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
            WHERE r.checkIn > '" . $yesterdayDate->format('Y-m-d') . "'
            and r.originalRoom = $roomId 
            and r.origin = '" . $origin . "'
            and r.status = " . $status->getId())
                ->getResult();

            if (empty($reservations)) {
                return null;
            }

            return $reservations;
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage() . " " . $ex->getTraceAsString());
            return null;
        }
    }

    public function getReservationsByRoom($roomId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $now = new DateTime('today midnight');
            $maxPastDate = $now->sub(new DateInterval("P" . ICAL_PAST_DAYS . "D"));
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));

            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
            WHERE r.checkIn > '" . $maxPastDate->format('Y-m-d') . "'
            and r.room = $roomId 
            and r.status = " . $status->getId())
                ->getResult();

            if (empty($reservations)) {
                return null;
            }

            return $reservations;
        } catch (Exception $exception) {
            return null;
        }
    }

    public function getReservationsByGuest($guestId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            return $this->em->getRepository(Reservations::class)->findOneBy(array('guest' => $guestId));
        } catch (Exception) {
            return null;
        }
    }


    public function getStayOversReservations()
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $reservations = null;
        try {
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));
            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
                JOIN r.room a
                JOIN a.property p
            where p.id = " . $_SESSION['PROPERTY_ID'] . "
            and r.checkIn < CURRENT_DATE() 
            And r.checkOut > CURRENT_DATE() 
            and r.status = " . $status->getId() . "
            order by r.checkIn asc")
                ->getResult();

        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        if (empty($reservations)) {
            return null;
        }

        return $reservations;
    }

    public function updateReservation($reservation): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $this->em->persist($reservation);
            $this->em->flush($reservation);

            $responseArray[] = array(
                'result_code' => 0,
                'result_message' => 'Successfully updated reservation'
            );

        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateReservationDate($reservation, $checkInDate, $checkOutDate, $blockedRoomApi): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $roomApi = new RoomApi($this->em, $this->logger);
            $isRoomAvailable = $roomApi->isRoomAvailable($reservation->getRoom()->getId(), $checkInDate, $checkOutDate, $reservation->getId());
            if ($isRoomAvailable) {
                $reservation->setCheckIn(new DateTime($checkInDate));
                $reservation->setCheckOut(new DateTime($checkOutDate));
                $reservation->setCheckOut(new DateTime($checkOutDate));
                $reservation->setUid(uniqid() . "@" . SERVER_NAME);

                $this->em->persist($reservation);
                $this->em->flush($reservation);

                //update blocked room
                $blockedRoomApi->updateBlockedRoomByReservation($reservation->getId(), $checkInDate, $checkOutDate);
            } else {
                return array(
                    'result_code' => 1,
                    'result_message' => 'Selected dates not available'
                );
            }

            $responseArray = array(
                'result_code' => 0,
                'result_message' => 'Successfully updated reservation'
            );

        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateReservationRoom($reservation, $roomId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $roomApi = new RoomApi($this->em, $this->logger);
            $isRoomAvailable = $roomApi->isRoomAvailable($roomId, $reservation->getCheckIn()->format("Y-m-d"), $reservation->getCheckOut()->format("Y-m-d"), $reservation->getId());
            if ($isRoomAvailable) {
                $room = $roomApi->getRoom($roomId);
                if ($room == null) {
                    return array(
                        'result_message' => "Room not found",
                        'result_code' => 1
                    );
                }
                $reservation->setRoom($room);
                $this->em->persist($reservation);
                $this->em->flush($reservation);
            } else {
                return array(
                    'result_code' => 1,
                    'result_message' => 'Selected dates not available'
                );
            }

            $responseArray = array(
                'result_code' => 0,
                'result_message' => 'Successfully updated reservation'
            );

        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


    public function updateReservationOriginUrl($reservation, $confirmationCode): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $reservation->setOriginUrl($confirmationCode);
            $this->em->persist($reservation);
            $this->em->flush($reservation);

            $responseArray[] = array(
                'result_code' => 0,
                'result_message' => 'Successfully updated reservation'
            );

        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function uploadReservations($reservationsString, $request)
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $reservations = explode("\n", trim($reservationsString));
        $this->logger->info("array lines: " . sizeof($reservations));
        $responseArray = array();
        foreach ($reservations as $reservation) {
            $checkIn = trim(substr($reservation, 52, 8));
            $checkOut = trim(substr($reservation, 62, 8));
            $guestName = trim(substr($reservation, 70, 36));
            $phoneNumber = trim(substr($reservation, 106, 18));
            $origin = trim(substr($reservation, 124, 46));
            $originURL = trim(substr($reservation, 170, 46));
            $uid = trim(substr($reservation, 216, 44));
            $roomId = intval(trim(substr($reservation, 407, 6)));
            $adultGuests = intval(trim(substr($reservation, 423, 2)));
            $childGuests = intval(trim(substr($reservation, 425, 2)));
            $smoker = (trim(substr($reservation, 427, 3)));

            $this->logger->info("checkIn field: " . $checkIn);
            $this->logger->info("checkOut field: " . $checkOut);
            $this->logger->info("guestName field: " . $guestName);
            $this->logger->info("phoneNumber field: " . $phoneNumber);
            $this->logger->info("origin field: " . $origin);
            $this->logger->info("originURL field: " . $originURL);
            $this->logger->info("uid field: " . $uid);
            $this->logger->info("roomId field: " . $roomId);
            $this->logger->info("adults: " . $adultGuests);
            $this->logger->info("children: " . $childGuests);
            $this->logger->info("somker: " . $smoker);

            $response = $this->createReservation($roomId, $guestName, $phoneNumber, "", $checkIn, $checkOut, $request, $adultGuests, $childGuests, $uid, false, $origin, $originURL, $smoker);
            $responseArray[] = array(
                'result_code' => $response['result_code'],
                'result_message' => $response['result_message'],
            );

        }

        return $responseArray;
    }


    public function importFTPReservations($request): array|Response
    {
        $this->logger->info("Starting Method: " . __METHOD__);

        $ftpConnection = ftp_connect('ftp.hotelrunner.co.za');

        ftp_login($ftpConnection, 'reservations@hotelrunner.co.za', '-DDij,n&zk(p');

        ftp_pasv($ftpConnection, true);

        $h = fopen('php://temp', 'r+');
        $response = array();
        try {
            ftp_fget($ftpConnection, $h, '/reservations.dat', FTP_BINARY, 0);
        } catch (\Exception $ex) {
            $this->logger->info($ex->getMessage());
            $response = array("result_code" => 1,
                "result_message" => $ex->getMessage());
            $this->writeFTPResults($response);
        }

        $fstats = fstat($h);
        fseek($h, 0);
        if ($fstats['size'] < 1) {
            $this->logger->info("No file found");
            $response = array("result_code" => 1,
                "result_message" => "No file found");
            $this->writeFTPResults($response);
        }
        $contents = fread($h, $fstats['size']);

        fclose($h);

        if (strlen($contents) < 1) {
            $this->logger->info("No file found");
            $response = array("result_code" => 1,
                "result_message" => "No file found");
            $this->writeFTPResults($response);
        }

        $this->logger->info("File : " . $contents);

        $response = $this->uploadReservations($contents, $request);

        //rename the input file for backup

        if (ftp_rename($ftpConnection, '/reservations.dat', '/reservations_' . date("YmdGis") . '.dat')) {
            $this->logger->info("successfully backed up file");
        } else {
            $this->logger->info("There was a problem backing up up file");
        }
        $this->writeFTPResults($response);
        return $response;
    }

    private function writeFTPResults($response): void
    {
        //write results to result.log
        try {
            $ftpConnection = ftp_connect('ftp.hotelrunner.co.za');

            ftp_login($ftpConnection, 'reservations@hotelrunner.co.za', '-DDij,n&zk(p');

            ftp_pasv($ftpConnection, true);

            $wfile = fopen('result.log', 'w');
            $this->logger->info(json_encode(($response)));
            fwrite($wfile, json_encode(($response)));
            fclose($wfile);

            $cfile = fopen('result.log', 'r');
            // try to upload $file
            if (ftp_fput($ftpConnection, 'result.log', $cfile, FTP_ASCII)) {
                $this->logger->info("Successfully uploaded 'result.log'\n");
            } else {
                $this->logger->error("There was a problem while uploading 'result.log'\n");
            }
            fclose($cfile);
            ftp_close($ftpConnection);
        } catch (\Exception $exception) {
            ftp_close($ftpConnection);
            $this->logger->error("Failed to write to the result log. " . $exception->getMessage());
        }
    }

    public function createReservation($roomIds, $guestName, $phoneNumber, $email, $checkInDate, $checkOutDate, $request = null, $adultGuests = null, $childGuests = null, $uid = null, $isImport = false, $origin = "website", $originUrl = "website", $smoker = "no"): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $this->logger->debug("child" . $childGuests);
        $this->logger->debug("adult" . $adultGuests);
        $responseArray = array();
        $blockRoomApi = new BlockedRoomApi($this->em, $this->logger);
        $room = null;
        try {
            //validate guest name
            if (strlen($guestName) > 50 || strlen($guestName) < 4) {
                return array(
                    'result_code' => 1,
                    'result_message' => 'Guest name should be min 4 characters and max 50 characters ' . strlen($guestName)
                );
            }

            //validate phone number
            if (strlen($phoneNumber) !== 12 || !str_starts_with($phoneNumber, "+")) {
                return array(
                    'result_code' => 1,
                    'result_message' => 'Phone number should be 12 characters and must start with +'
                );
            }

            //validate phone number is numbers only
            $phoneNumberWithoutPlus = str_replace("+", "", $phoneNumber);
            if (!is_numeric($phoneNumberWithoutPlus)) {
                return array(
                    'result_code' => 1,
                    'result_message' => 'Phone number is not numeric'
                );
            }

            //validate email
            if (strlen($email) > 0) {
                if (strlen($email) > 50 || strlen($email) < 4) {
                    return array(
                        'result_code' => 1,
                        'result_message' => 'Email should be min 4 characters and max 50 characters'
                    );
                }

                $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                if (!preg_match($pattern, $email)) {
                    return array(
                        'result_code' => 1,
                        'result_message' => 'Email is not valid'
                    );
                }
            }

            //validate adults
            if (strlen($adultGuests) > 2 || strlen($adultGuests) == 0 || !is_numeric($adultGuests) || intval($adultGuests) < 1) {
                return array(
                    'result_message' => "Number of adult guests length should be between 1 and 2 and should be a positive number",
                    'result_code' => 1
                );
            }

            //validate kids
            if (strlen($childGuests) > 2 || strlen($childGuests) == 0 || !is_numeric($childGuests) || intval($childGuests) < 0) {
                return array(
                    'result_message' => "Number of child guests length should be between 1 and 2 and should be a positive number",
                    'result_code' => 1
                );
            }


            //validate smoker
            if (strcmp($smoker, "yes") !== 0 && strcmp($smoker, "no") !== 0) {
                return array(
                    'result_message' => "The smoker value is invalid",
                    'result_code' => 1
                );
            }

            //validate dates

            if (!DateTime::createFromFormat('Y-m-d', $checkOutDate)) {
                return array(
                    'result_message' => "Check-out date invalid",
                    'result_code' => 1
                );
            }

            if (!DateTime::createFromFormat('Y-m-d', $checkInDate)) {
                return array(
                    'result_message' => "Check-in date invalid",
                    'result_code' => 1
                );
            }



            $checkInDateDateObject = new DateTime($checkInDate);
            $checkOutDateDateObject = new DateTime($checkOutDate);

            //validate number of nights
            $totalNights = intval($checkInDateDateObject->diff($checkOutDateDateObject)->format('%a'));
            if ($totalNights > 30) {
                return array(
                    'result_message' => "The maximum number of nights is 30",
                    'result_code' => 1
                );
            }

            //validate checkin dates
            if (strlen($checkInDate) < 1 || strlen($checkOutDate) < 1) {
                return array(
                    'result_message' => "Check-in and check-out date is mandatory",
                    'result_code' => 1
                );
            }

            //validate checkin dates
            if (!$this->defectApi->isDefectEnabled("create_reservation_10")) {
                if (strcmp($checkInDate, $checkOutDate) == 0) {
                    return array(
                        'result_message' => "Check-in and check-out date can not be the same",
                        'result_code' => 1
                    );
                }
            }


            //validate checkin dates
            if ($checkInDateDateObject > $checkOutDateDateObject) {
                return array(
                    'result_message' => "Check-in date can not be after check-out date",
                    'result_code' => 1
                );
            }

            $now = new DateTime('today midnight');
            if (!$this->defectApi->isDefectEnabled("create_reservation_11")) {
                if ($checkInDateDateObject < $now) {
                    return array(
                        'result_message' => "Check-in date can not be in the past",
                        'result_code' => 1
                    );
                }
            }




            //get property Id
            $roomIds = str_replace('[', "", $roomIds);
            $roomIds = str_replace(']', "", $roomIds);
            $roomIds = str_replace('"', "", $roomIds);
            $roomIdsArray = explode(",", $roomIds);
            $reservationIds = array();
            $roomApi = new RoomApi($this->em, $this->logger);
            $roomsCapacity = 0;

            //validate the room capacity vs number of guests
            foreach ($roomIdsArray as $roomId) {
                $room = $roomApi->getRoom($roomId);
                if ($room == null) {
                    $responseArray = array(
                        'result_code' => 1,
                        'result_message' => 'Room not found, id: ' . $roomId
                    );
                    return $responseArray;
                }
                $roomsCapacity += intval($room->getSleeps());
            }


            $totalGuests = intval($adultGuests) + intval($childGuests);
            if (!$this->defectApi->isDefectEnabled("create_reservation_12")) {
                if ($totalGuests > $roomsCapacity) {
                    return array(
                        'result_code' => 1,
                        'result_message' => 'The selected rooms can not accommodate the number of guests'
                    );
                }
            }


            //validate that the number of guests is not less than the number of rooms booked
            $this->logger->debug("number of rooms " . sizeof($roomIdsArray));
            if (sizeof($roomIdsArray) > $totalGuests) {
                return array(
                    'result_code' => 1,
                    'result_message' => 'The number of guests can not be less than the number of rooms booked'
                );
            }

            foreach ($roomIdsArray as $roomId) {
                $this->logger->debug("room id " . $roomId);

                //get guest
                $guestApi = new GuestApi($this->em, $this->logger);
                $guest = null;
                //get room

                $room = $roomApi->getRoom($roomId);

                //validate that children are not booked in a no kids room
                $kidsAllowed = $room->isKids();
                if (!$kidsAllowed && intval($childGuests) > 0) {
                    $responseArray = array(
                        'result_code' => 1,
                        'result_message' => 'Kids not allowed for one of the rooms'
                    );
                    return $responseArray;
                }

                if (strcmp($origin, "airbnb.com") === 0) {
                    $guest = $guestApi->getGuestByName("Airbnb Guest");
                } elseif (strcmp($origin, "booking.com") === 0) {
                    if (!empty($phoneNumber)) {
                        $guest = $guestApi->getGuestByPhoneNumber($phoneNumber, $request, $room->getProperty()->getId());
                    }
                } else {
                    $guest = $guestApi->getGuestByPhoneNumber($phoneNumber, $request, $room->getProperty()->getId());
                }


                if ($guest == null) {
                    $this->logger->debug("guest not found, creating a new guest");
                    //create guest
                    $response = $guestApi->createGuest($guestName, $phoneNumber, $email, $origin, $room->getProperty()->getId());
                    if ($response[0]['result_code'] != 0) {
                        $this->logger->debug(print_r($response, true));

                        if ($isImport) {
                            //email admin person
                            if (!$this->isFailedUidRecorded($uid)) {
                                $this->recordFailedUid($uid);

                                $messageBody = "There was a problem creating a reservation. failed to create guest entity. " . $checkInDate . " - " . $room->getName();
                                $SMSHelper = new SMSHelper($this->logger);
                                $SMSHelper->sendMessage("+27837917430", $messageBody);
                                $SMSHelper->sendMessage(str_replace(" ", "", $room->getProperty()->getPhoneNumber()), $messageBody);

                            }

                        }
                        return $response;
                    } else {
                        $guest = $response[0]['guest'];
                    }
                } else {
                    //update guest details
                    $guest->setName($guestName);
                    $guest->setEmail($email);
                    $this->em->persist($guest);
                    $this->em->flush($guest);

                    if (strcmp($guest->getState(), "blocked") === 0) {
                        $responseArray = array(
                            'result_code' => 1,
                            'result_message' => 'Guest blocked for ' . $guest->getComments()
                        );

                        if ($isImport) {
                            //email admin person
                            if (!$this->isFailedUidRecorded($uid)) {
                                $this->recordFailedUid($uid);
                                $communicationApi = new CommunicationApi($this->em, $this->logger);

                                $messageBody = "There was a problem creating a reservation. Guest Blocked. " . $checkInDate . " - " . $room->getName();
                                $SMSHelper = new SMSHelper($this->logger);
                                $SMSHelper->sendMessage("+27837917430", $messageBody);
                                $SMSHelper->sendMessage(str_replace(" ", "", $room->getProperty()->getPhoneNumber()), $messageBody);
                            }
                        }

                        return $responseArray;
                    }
                }

                //check if room is available
                $isRoomAvailable = $roomApi->isRoomAvailable($room->getId(), $checkInDate, $checkOutDate);

                if (!$isRoomAvailable) {
                    $responseArray = array(
                        'result_code' => 1,
                        'result_message' => 'Tried to create a reservation. Room not available for selected dates ' . $checkInDate . " - " . $checkOutDate
                    );

                    if ($isImport) {
                        //email admin person
                        if (!$this->isFailedUidRecorded($uid)) {
                            $this->recordFailedUid($uid);

                            $messageBody = "There was a problem importing a reservation. " . $checkInDate . " - " . $room->getName();
                            $SMSHelper = new SMSHelper($this->logger);
                            $SMSHelper->sendMessage("+27837917430", $messageBody);
                            $SMSHelper->sendMessage(str_replace(" ", "", $room->getProperty()->getPhoneNumber()), $messageBody);
                        }

                    } else {
                        return $responseArray;
                    }
                }


                $reservation = new Reservations();
                $reservation->setRoom($room);
                $reservation->setOriginalRoom($room);
                $reservation->setAdditionalInfo("Guest Name is: " . $guest->getName());
                $reservation->setCheckIn(new DateTime($checkInDate));
                $reservation->setCheckOut(new DateTime($checkOutDate));
                $reservation->setGuest($guest);

                $reservation->setOrigin($origin);
                $reservation->setReceivedOn(new DateTime());
                $reservation->setUpdatedOn(new DateTime());
                $reservation->setCheckedInTime(NULL);
                $reservation->setOriginUrl($originUrl);
                $reservation->setAdults($adultGuests);
                $reservation->setChildren($childGuests);

                if ($isImport) {
                    $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));
                } else {
                    $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'pending'));
                }

                $reservation->setStatus($status);

                if ($uid == null) {
                    $reservation->setUid(uniqid() . "@" . SERVER_NAME);
                } else {
                    $reservation->setUid($uid);
                }

                $this->em->persist($reservation);
                $this->em->flush($reservation);

                //add smoking note
                if (strcmp($smoker, "yes") == 0) {
                    $notesApi = new NotesApi($this->em, $this->logger);
                    if (!$this->defectApi->isDefectEnabled("view_reservation_2")) {
                        $notesApi->addNote($reservation->getId(), "The guest is a smoker");
                    }
                }

                //block connected Room
                if ($isImport) {
                    $this->logger->debug("calling block room to block " . $room->getLinkedRoom() . " for room  " . $room->getName());
                    $blockRoomApi->blockRoom($room->getLinkedRoom(), $checkInDate, $checkOutDate, "Connected Room Booked ", $reservation->getId());
                }

                //check google ads notification
                $now = new DateTime();
                if (strcmp($reservation->getCheckIn()->format("Y-m-d"), $now->format("Y-m-d")) === 0) {
                    $notificationApi = new NotificationApi($this->em, $this->logger);
                    //$notificationApi->updateAdsNotification($room->getProperty()->getId());
                }


                if (!$isImport) {
                    //send SMS
                    if (str_starts_with($reservation->getGuest()->getPhoneNumber(), '0') || str_starts_with($reservation->getGuest()->getPhoneNumber(), '+27')) {
                        $this->logger->debug("this is a south african number " . $reservation->getGuest()->getPhoneNumber());
                        $SMSHelper = new SMSHelper($this->logger);
                        $message = "Hi " . $guest->getName() . ", Your Invoice is ready. Regrettable no children allowed. http://" . $reservation->getRoom()->getProperty()->getServerName() . "/invoice.html?id=" . $reservation->getId() . " - Aluve GH";
                        $SMSHelper->sendMessage($guest->getPhoneNumber(), $message);
                    }

                    //Send email
                    $this->logger->debug("this reservation is not an import");
                    if (!empty($reservation->getGuest()->getEmail())) {
                        $this->logger->debug("user email is not empty sending email" . $reservation->getGuest()->getEmail());
                        $emailBody = file_get_contents(__DIR__ . '/../email_template/new_reservation.html');
                        $emailBody = str_replace("guest_name", $reservation->getGuest()->getName(), $emailBody);
                        $emailBody = str_replace("check_in", $reservation->getCheckIn()->format("d M Y"), $emailBody);
                        $emailBody = str_replace("check_out", $reservation->getCheckOut()->format("d M Y"), $emailBody);
                        $emailBody = str_replace("server_name", $reservation->getRoom()->getProperty()->getServerName(), $emailBody);
                        $emailBody = str_replace("reservation_id", $reservation->getId(), $emailBody);
                        $emailBody = str_replace("property_name", $reservation->getRoom()->getProperty()->getName(), $emailBody);
                        $emailBody = str_replace("room_name", $reservation->getRoom()->getName(), $emailBody);

                        $this->logger->debug("email body" . $emailBody);


                        $communicationApi = new CommunicationApi($this->em, $this->logger);
                        $communicationApi->sendEmailViaGmail(ALUVEAPP_ADMIN_EMAIL, $reservation->getGuest()->getEmail(), $emailBody, $reservation->getRoom()->getProperty()->getName() . '- Thank you for your reservation', $reservation->getRoom()->getProperty()->getName(), $reservation->getRoom()->getProperty()->getEmailAddress());
                        $this->logger->debug("Successfully sent email to guest");
                    } else {
                        $this->logger->debug("user email is empty not sending email" . $reservation->getGuest()->getEmail());
                    }
                    $reservationIds[] = $reservation->getId();
                }
            }
            $responseArray = array(
                'result_code' => 0,
                'result_message' => "Successfully created reservation",
                'reservation_id' => $reservationIds
            );
        } catch
        (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage(),
            );
            $this->logger->debug(print_r($responseArray, true));
            if ($isImport) {
                //email admin person
                if (!$this->isFailedUidRecorded($uid)) {
                    $this->recordFailedUid($uid);

                    $messageBody = "There was an exception creation a reservation. " . $checkInDate . " - " . $room->getName();
                    $SMSHelper = new SMSHelper($this->logger);
                    $SMSHelper->sendMessage("+27837917430", $messageBody);
                    $SMSHelper->sendMessage(str_replace(" ", "", $room->getProperty()->getPhoneNumber()), $messageBody);
                }

            }
        }


        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function recordFailedUid($uid)
    {
        $failedUid = new FailedUids();
        $failedUid->setUid($uid);
        $failedUid->setDate(new DateTime());
        $this->em->persist($failedUid);
        $this->em->flush($failedUid);
    }

    public function isFailedUidRecorded($uid): bool
    {
        $failedUid = $this->em->getRepository(FailedUids::class)->findOneBy(array('uid' => $uid));
        if ($failedUid === null) {
            return false;
        } else {
            return true;
        }
    }


    public function isEligibleForCheckIn($reservation): bool
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $isEligible = true;
        if ($reservation->getGuest()->getIdNumber() == null) {
            $isEligible = false;
        }

        if (strcasecmp($reservation->getGuest()->getPhoneNumber(), "") == 0) {
            $isEligible = false;
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $isEligible;
    }

    public function getAmountDue($reservation): float|int
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        $addOnsApi = new AddOnsApi($this->em, $this->logger);
        $paymentApi = new PaymentApi($this->em, $this->logger);

        $addOns = $addOnsApi->getReservationAddOns($reservation->getId());
        $totalDays = intval($reservation->getCheckIn()->diff($reservation->getCheckOut())->format('%a'));
        $totalPriceForAllAdOns = 0;
        foreach ($addOns as $addOn) {
            $totalPriceForAllAdOns += (intVal($addOn->getAddOn()->getPrice()) * intval($addOn->getQuantity()));
        }

        $roomPrice = 0;
        if (strcasecmp($reservation->getOrigin(), "website") == 0) {
            $roomPrice = $reservation->getRoom()->getPrice();
        }

        $totalPrice = intval($roomPrice) * $totalDays;
        $totalPrice += $totalPriceForAllAdOns;

        //payments
        $payments = $paymentApi->getReservationPayments($reservation->getId());
        $totalPayment = 0;
        foreach ($payments as $payment) {
            $totalPayment += (intVal($payment->getAmount()));
        }

        $due = $totalPrice - $totalPayment;
        $this->logger->debug("Total Add Ons: " . $totalPriceForAllAdOns);
        $this->logger->debug("Room Price: " . $roomPrice);
        $this->logger->debug("Total Days: " . $totalDays);
        $this->logger->debug("Total Price: " . $totalPrice);
        $this->logger->debug("Total Paid: " . $totalPayment);
        $this->logger->debug("due: " . $due);

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $due;
    }


    public function sendReviewRequest($propertyId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $reservations = $this->getCheckOutReservation($propertyId);
            if ($reservations != null) {
                foreach ($reservations as $reservation) {
                    //send email if provided
                    if (!empty($reservation->getGuest()->getEmail())) {
                        $this->sendReviewEmail($reservation);
                    } else {
                        //send sms
                        if (str_starts_with($reservation->getGuest()->getPhoneNumber(), '0') || str_starts_with($reservation->getGuest()->getPhoneNumber(), '+27')) {
                            $this->logger->debug("this is a south african number " . $reservation->getGuest()->getPhoneNumber());
                            $SMSHelper = new SMSHelper($this->logger);
                            $message = "Hi " . $reservation->getGuest()->getName() . ", Thank you for staying with us. Please take a few seconds to give us a 5-star review on Google. " . $reservation->getRoom()->getProperty()->getGoogleReviewLink();
                            $SMSHelper->sendMessage($reservation->getGuest()->getPhoneNumber(), $message);
                        }
                    }

                    $this->logger->debug(print_r($responseArray, true));
                }
            }
        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


    function sendReviewEmail($reservation): bool
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            if ($reservation->getRoom()->getProperty()->getGoogleReviewLink() != null) {
                //send email to guest
                $emailBody = file_get_contents(__DIR__ . '/../email_template/review_request.html');
                $emailBody = str_replace("guest_name", $reservation->getGuest()->getName(), $emailBody);
                $emailBody = str_replace("google_review_link", $reservation->getRoom()->getProperty()->getGoogleReviewLink(), $emailBody);
                $emailBody = str_replace("property_name", $reservation->getRoom()->getProperty()->getName(), $emailBody);

                $communicationApi = new CommunicationApi($this->em, $this->logger);
                $communicationApi->sendEmailViaGmail(ALUVEAPP_ADMIN_EMAIL, $reservation->getGuest()->getEmail(), $emailBody, $reservation->getRoom()->getProperty()->getName() . ' - Please review us', $reservation->getRoom()->getProperty()->getName(), $reservation->getRoom()->getProperty()->getEmailAddress());
            }
            return true;
        } catch (Exception $ex) {
            $this->logger->debug(print_r($ex, true));
            return false;
        }
    }

    public function isAllRoomsBooked($propertyId): bool
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $roomApi = new RoomApi($this->em, $this->logger);


        $rooms = $this->em->getRepository(Rooms::class)->findBy(array('property' => $propertyId, 'status' => 1));
        foreach ($rooms as $room) {
            $now = new DateTime();
            if ($roomApi->isRoomAvailable($room->getId(), $now->format('Y-m-d'), $now->add(new DateInterval("P1D"))->format('Y-m-d'))) {
                return false;
            }
        }
        return true;
    }


}