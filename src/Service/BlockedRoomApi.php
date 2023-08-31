<?php

namespace App\Service;

use App\Entity\BlockedRooms;
use App\Entity\Reservations;
use DateInterval;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class BlockedRoomApi
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        if (session_id() === '') {
            $logger->info("Session id is empty" . __METHOD__);
            session_start();
        }
    }

    public function blockRoom($roomId, $fromDate, $toDate, $comments, $reservationId = null): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $this->logger->debug("blocking room: " . $roomId);

        $responseArray = array();
        try {

            if (strlen($comments) > 50 || strlen($comments) == 0) {
                $responseArray[] = array(
                    'result_message' => "Note length should be between 1 and 50",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //validate dates

            if (!DateTime::createFromFormat('Y-m-d', $fromDate)) {
                return array(
                    'result_message' => "From date invalid",
                    'result_code' => 1
                );
            }

            if (!DateTime::createFromFormat('Y-m-d', $toDate)) {
                return array(
                    'result_message' => "To date invalid",
                    'result_code' => 1
                );
            }

            $checkInDateDateObject = new DateTime($fromDate);
            $checkOutDateDateObject = new DateTime($toDate);

            //validate checkin dates
            if (strlen($fromDate) < 1 || strlen($toDate) < 1) {
                return array(
                    'result_message' => "From and to date is mandatory",
                    'result_code' => 1
                );
            }

            if (strcmp($fromDate, $toDate) == 0) {
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

            $now = new DateTime('today midnight');

            if ($checkInDateDateObject < $now) {
                return array(
                    'result_message' => "From date can not be in the past",
                    'result_code' => 1
                );
            }

            //get the room
            $roomApi = new RoomApi($this->em, $this->logger);
            $room = $roomApi->getRoom($roomId);

            $isRoomAvailable = $roomApi->isRoomAvailable($room->getId(), $fromDate, $toDate, $reservationToExclude = 0);
            if (!$isRoomAvailable) {
                return array(
                    'result_message' => "Room can not be blocked because it is already booked or blocked",
                    'result_code' => 1
                );
            }

            if ($room == null) {
                $responseArray = array(
                    'result_code' => 1,
                    'result_message' => "Room not found for id $roomId"
                );
                $this->logger->debug("Ending Method before the return: " . __METHOD__);
                return $responseArray;
            } else {
                $this->logger->debug("Room is not null");
            }

            $date = new DateTime();
            $toDateDateTime = new DateTime($toDate);
            $fromDateDateTime = new DateTime($fromDate);

            //check if there is a room blocked for reservation
            if ($reservationId !== null) {
                $comments .= "reservation - $reservationId";
                $blockRoom = $this->em->getRepository(BlockedRooms::class)->findOneBy(array('linkedResaId' => $reservationId));
                if ($blockRoom === null) {
                    $blockRoom = new BlockedRooms();
                }
            } else {
                $blockRoom = new BlockedRooms();
            }



            $blockRoom->setRoom($room);
            $blockRoom->setComment($comments);
            $blockRoom->setFromDate($fromDateDateTime);
            $blockRoom->setToDate($toDateDateTime);
            $blockRoom->setCreatedDate($date);
            $blockRoom->setLinkedResaId($reservationId);
            $blockRoom->setUid(uniqid() . "@" . SERVER_NAME);
            $this->em->persist($blockRoom);
            $this->em->flush($blockRoom);

            $responseArray = array(
                'result_code' => 0,
                'result_message' => 'Successfully blocked room',
                'id' => $blockRoom->getId()
            );
            $this->logger->debug(print_r($responseArray, true));
        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }


        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getBlockedRoomsByRoomId($roomId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $now = new DateTime('today midnight');

            $blockedRooms = $this->em
                ->createQuery("SELECT b FROM App\Entity\BlockedRooms b 
            JOIN b.room r
            WHERE b.room = r.id
            and b.toDate >= '" . $now->format('Y-m-d') . "' 
            and b.room = $roomId 
            order by b.fromDate asc ")
                ->getResult();

            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $blockedRooms;
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage() . " - " . $exception->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->debug(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return null;
    }

    public function getBlockedRoomsByProperty()
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $now = new DateTime('today midnight');

            $blockedRooms = $this->em
                ->createQuery("SELECT b FROM App\Entity\BlockedRooms b
            WHERE b.toDate >= '" . $now->format('Y-m-d') . "'
            order by b.fromDate asc ")
                ->getResult();

            //$blockedRooms = $this->em->getRepository(BlockedRooms::class)->findAll();
            $this->logger->debug("rooms found : " . sizeof($blockedRooms));


            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $blockedRooms;
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage() . " - " . $exception->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->debug(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return null;
    }

    public function deleteBlockedRoom($blockedRoomId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $blockedRoom = $this->em->getRepository(BlockedRooms::class)->findOneBy(array('id' => $blockedRoomId));
            if ($blockedRoom == null) {
                $responseArray = array(
                    'result_message' => "No blocked room found for id $blockedRoomId",
                    'result_code' => 1
                );
                $this->logger->debug("No blocked room found for id $blockedRoomId");
                return $responseArray;
            }
            $this->em->remove($blockedRoom);
            $this->em->flush();
            $responseArray = array(
                'result_message' => "Successfully deleted blocked room",
                'result_code' => 0
            );
        } catch (Exception $exception) {
            $responseArray = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->debug(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function deleteBlockedRoomByReservation($reservationId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $blockedRoom = $this->em->getRepository(BlockedRooms::class)->findOneBy(array('linkedResaId' => $reservationId));
            if ($blockedRoom != null) {
                $this->em->remove($blockedRoom);
                $this->em->flush();
                $responseArray[] = array(
                    'result_message' => "Successfully deleted blocked room",
                    'result_code' => 0
                );
            }
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->debug(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateBlockedRoomByReservation($reservationId, $fromDate, $toDate)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $blockedRoom = $this->em->getRepository(BlockedRooms::class)->findOneBy(array('linkedResaId' => $reservationId));
            if ($blockedRoom != null) {
                $toDateDateTime = new DateTime($toDate);
                $fromDateDateTime = new DateTime($fromDate);
                $blockedRoom->setFromDate($fromDateDateTime);
                $blockedRoom->setToDate($toDateDateTime);
                $blockedRoom->setUid(uniqid() . "@" . SERVER_NAME);

                $this->em->persist($blockedRoom);
                $this->em->flush($blockedRoom);

                $responseArray[] = array(
                    'result_code' => 0,
                    'result_message' => 'Successfully updated blocked room',
                    'block_id' => $blockedRoom->getId()
                );
                $this->logger->debug(print_r($responseArray, true));
            } else {
                $responseArray[] = array(
                    'result_message' => "No blocked room found for reservation",
                    'result_code' => 1
                );
                $this->logger->debug("No blocked room found for reservation");
            }
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


    public function getBlockedRoom($blockedRoomId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            return $this->em->getRepository(BlockedRooms::class)->findOneBy(array('id' => $blockedRoomId));
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->debug(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


}