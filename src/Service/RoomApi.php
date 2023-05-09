<?php

namespace App\Service;

use App\Entity\Property;
use App\Entity\ReservationStatus;
use App\Entity\RoomBeds;
use App\Entity\RoomBedSize;
use App\Entity\RoomImages;
use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomTv;
use App\Helpers\FormatHtml\ConfigIcalLinksHTML;
use App\Helpers\FormatHtml\ConfigIcalLinksLogsHTML;
use App\Helpers\FormatHtml\RoomImagesHTML;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

require_once(__DIR__ . '/../app/application.php');

class RoomApi
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;

        if (session_id() === '') {
            $logger->info("Session id is empty");
            $session = new Session();
            $session->start();
        }
    }

    public function getAvailableRoomsFromString($availableRoomsString, $request): array
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        $checkIn = trim(substr($availableRoomsString,0,10));
        $checkOut = trim(substr($availableRoomsString,10,10));
        $propertyUid = trim(substr($availableRoomsString,20,36));
        $kids = trim(substr($availableRoomsString,56,2));

        $this->logger->info("checkIn field: " . $checkIn);
        $this->logger->info("checkOut field: " . $checkOut);
        $this->logger->info("propertyUid field: " . $propertyUid);
        $this->logger->info("kids field: " . $kids);

        return $this->getAvailableRooms($checkIn, $checkOut, $request, $kids, $propertyUid);
    }

    public function getAvailableRooms($checkInDate, $checkOutDate, $request, $kids, $propertyUid = 0): ?array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $propertyApi = new PropertyApi($this->em, $this->logger);
            if($propertyUid === 0){
                $propertyId = $_SESSION['PROPERTY_ID'] ?? $propertyApi->getPropertyIdByHost($request);
            }else{
                $property = $this->em->getRepository(Property::class)->findOneBy(
                    array("uid" =>$propertyUid));
                if($property !== null){
                    $propertyId = $property->getId();
                }else{
                    $this->logger->error("Property id not found");
                    return null;
                }
            }

            $rooms = $this->em->getRepository(Rooms::class)->findBy(array('property' => $propertyId, 'status'=>1));
            foreach ($rooms as $room) {
                if ($this->isRoomAvailable($room->getId(), $checkInDate, $checkOutDate)) {
                    if(!$room->isKids() && intval($kids) > 0){
                        $this->logger->info("This room does not allow kids");
                    }else{
                        $responseArray[] = $room;
                    }

                }
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        if (empty($responseArray)) {
            $this->logger->debug("Rooms array empty ");
            return null;
        } else {
            $this->logger->debug("Rooms array not empty ");
            return $responseArray;
        }
    }

    public function isRoomAvailable($roomId, $checkInDate, $checkOutDate, $reservationToExclude = 0): bool|array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        $returnValue = false;
        try {
            $status = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));
            $reservations = $this->em
                ->createQuery("SELECT r FROM App\Entity\Reservations r 
            WHERE 
            (
            (r.checkOut > '" . $checkInDate . "' and r.checkIn <=  '" . $checkInDate . "') 
            or
            (r.checkIn < '" . $checkOutDate . "' and r.checkIn >  '" . $checkInDate . "') 
            )
            And r.status = " . $status->getId() . "
            And r.room = $roomId
            And r.id != $reservationToExclude")
                ->getResult();

            $blockedRooms = $this->em
                ->createQuery("SELECT b FROM App\Entity\BlockedRooms b 
            WHERE 
            (
            (b.toDate > '" . $checkInDate . "' and b.fromDate <=  '" . $checkInDate . "') 
            or
            (b.fromDate < '" . $checkOutDate . "' and b.fromDate >  '" . $checkInDate . "') 
            )
            And b.room = $roomId")
                ->getResult();

            if (count($reservations) < 1 && count($blockedRooms) < 1) {
                $returnValue = true;
                $this->logger->debug("No reservations or blocked rooms found");
            } else {
                $this->logger->debug("reservations or blocked rooms found");
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
            return $responseArray;
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $returnValue;
    }

    public function getRooms($roomId, $request): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $_SESSION['ROOM_ID'] = $roomId;
            $this->logger->debug("room id saved in room as : " . $_SESSION['ROOM_ID']);

            if (strcmp($roomId, "all") === 0) {
                //check if the PROPERTY_ID if not get it from the host
                $propertyApi = new PropertyApi($this->em, $this->logger);
                if (!isset($_SESSION['PROPERTY_ID'])) {
                    $propertyId = $propertyApi->getPropertyIdByHost($request);
                } else {
                    $propertyId = $_SESSION['PROPERTY_ID'];
                }
                $rooms = $this->em->getRepository(Rooms::class)->findBy(array('property' => $propertyId, 'status' => 1));
            } else {
                $rooms = $this->em->getRepository(Rooms::class)->findBy(array('id' => $roomId));
            }


            if (count($rooms) < 1) {
                $responseArray[] = array(
                    'result_message' => "Rooms not found for room id $roomId",
                    'result_code' => 1
                );
                $this->logger->debug("No rooms found");
            } else {
                foreach ($rooms as $room) {
                    $linkedRoom = $room->getLinkedRoom();
                    $linkedRoomId = 0;
                    if (strlen($linkedRoom) > 0) {
                        $linkedRoomId = $room->getLinkedRoom();
                    }

                    $stairs = 0;
                    if ($room->getStairs() === true) {
                        $stairs = 1;
                    }

                    $roomImages = $this->getRoomImages($room->getId());
                    $roomImagesUploadHtml = new RoomImagesHTML($this->em, $this->logger);
                    $imagesHtml = $roomImagesUploadHtml->formatUpdateRoomHtml($roomImages);
                    $roomBeds = $this->getRoomBeds($room->getId());
                    $iCalApi = new ICalApi($this->em, $this->logger);
                    $icalLinks = $iCalApi->getIcalLinks($room->getId());
                    $configIcalHtml = new ConfigIcalLinksHTML($this->em, $this->logger);
                    $icalFormattedHtml = $configIcalHtml->formatHtml($icalLinks);

                    $responseArray[] = array(
                        'id' => $room->GetId(),
                        'name' => $room->GetName(),
                        'price' => $room->GetPrice(),
                        'status' => $room->GetStatus()->getId(),
                        'sleeps' => $room->GetSleeps(),
                        'description' => urldecode($room->getDescription()),
                        'description_html' => $this->replaceWithBold($room->getDescription()),
                        'beds' => json_encode($roomBeds),
                        'stairs' => $stairs,
                        'linked_room' => $linkedRoomId,
                        'room_size' => $room->getSize(),
                        'uploaded_images' => $imagesHtml,
                        'tv' => $room->getTv()->getId(),
                        'tv_name' => $room->getTv()->getName(),
                        'kids_policy' => $room->isKids(),
                        'amenities' => $room->getAmenities(),
                        'ical_links' => $icalFormattedHtml,
                        'export_link' => "https://" . SERVER_NAME . "/public/ical/export/" . $room->GetId(),
                        'result_code' => 0
                    );
                }
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);

        return $responseArray;
    }

    public function replaceWithBold($string): array|string
    {
        $string = str_replace("{", "<b>", $string);
        return str_replace("}", "</b>", $string);
    }

    public function getRoom($roomId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            return $this->em->getRepository(Rooms::class)->findOneBy(array('id' => $roomId));
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getRoomsEntities($roomId = 0, $request = null): ?array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            if ($roomId === 0) {
                if (!isset($_SESSION['PROPERTY_ID'])) {
                    $propertyApi = new PropertyApi($this->em, $this->logger);
                    $propertyId = $propertyApi->getPropertyIdByHost($request);
                } else {
                    $propertyId = $_SESSION['PROPERTY_ID'];
                }
                $rooms = $this->em->getRepository(Rooms::class)->findBy(array('property' => $propertyId, 'status' => 1));
            } else {
                $rooms = $this->em->getRepository(Rooms::class)->findBy(array('id' => $roomId));
            }
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $rooms;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
            return null;
        }
    }

    public function getActiveAndPendingRoomsEntities($roomId = 0, $request = null): ?array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            if ($roomId === 0) {
                if (!isset($_SESSION['PROPERTY_ID'])) {
                    $propertyApi = new PropertyApi($this->em, $this->logger);
                    $propertyId = $propertyApi->getPropertyIdByHost($request);
                } else {
                    $propertyId = $_SESSION['PROPERTY_ID'];
                }
                $rooms = $this->em->getRepository(Rooms::class)->findBy(array('property' => $propertyId));
            } else {
                $rooms = $this->em->getRepository(Rooms::class)->findBy(array('id' => $roomId));
            }
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $rooms;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
            return null;
        }
    }

    public function getRoomImages($roomId): ?array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $room = $this->em->getRepository(Rooms::class)->findOneBy(
                array('id' => $roomId));
            if ($room === null) {
                $this->logger->debug("room is null");
                return null;
            }

            //get room images
            $roomImages = $this->em->getRepository(RoomImages::class)->findBy(
                array('room' => $roomId,
                    'status' => array("active", "default")));

            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $roomImages;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getRoomBeds($roomId): ?array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $room = $this->em->getRepository(Rooms::class)->findOneBy(
                array('id' => $roomId));
            if ($room === null) {
                $this->logger->debug("room is null");
                return null;
            }

            //get room images
            $roomBeds = $this->em->getRepository(RoomBeds::class)->findBy(
                array('room' => $roomId));

            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            if ($roomBeds !== null) {
                foreach ($roomBeds as $roomBed) {
                    $responseArray[] = array(
                        'id' => $roomBed->getBed()->getId(),
                        'name' => $roomBed->getBed()->getName()
                    );
                }
            }
            return $responseArray;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getRoomImagesJson($roomId): ?array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $room = $this->em->getRepository(Rooms::class)->findOneBy(
                array('id' => $roomId));
            if ($room === null) {
                $this->logger->debug("room is null");
                return null;
            }

            //get room images
            $roomImages = $this->em->getRepository(RoomImages::class)->findBy(
                array('room' => $roomId,
                    'status' => array("active", "default")));

            foreach ($roomImages as $roomImage) {
                $responseArray[] = array(
                    'name' => $roomImage->getName(),
                    'size' => "5mb",
                    'status' => $roomImage->getStatus()
                );
            }
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function addImageToRoom($imageName, $roomId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $this->logger->debug("room id is " . $roomId);
        try {
            $roomImage = new RoomImages();
            $room = $this->getRoom($roomId);
            if ($room === null) {
                $responseArray[] = array(
                    'result_message' => "Room not found",
                    'result_code' => 1
                );
                return $responseArray;
            }
            //check if its the first image for the room, if so make it default
            $roomImages = $this->getRoomImages($room->getId());
            if (count($roomImages) < 1) {
                $roomImage->setStatus("default");
            } else {
                $roomImage->setStatus("active");
            }

            $roomImage->setName($imageName);
            $roomImage->setRoom($room);

            $this->em->persist($roomImage);
            $this->em->flush($roomImage);

            $responseArray[] = array(
                'result_message' => "Successfully linked image to the room",
                'result_code' => 0,
                'image_id' => $roomImage->getId()
            );
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getNumberOfRoomImages($roomId): int
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $roomImages = $this->em->getRepository(RoomImages::class)->findBy(array('room' => $roomId, 'status' => array("active", "default")));
            $this->logger->debug("Number of room images: " . sizeof($roomImages));
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return sizeof($roomImages);
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return 0;
        }
    }

    public function getRoomStatuses(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $roomStatuses = $this->em->getRepository(RoomStatus::class)->findAll();
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $roomStatuses;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getRoomBedSizesJson(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $roomBedSizes = $this->em->getRepository(RoomBedSize::class)->findAll();
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            $json = [];
            $i = 1;
            foreach ($roomBedSizes as $roomBedSize) {
                $i++;
                $json[] = $roomBedSize->getName();
            }
            return $json;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getRoomBedSizes(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $roomBedSizes = $this->em->getRepository(RoomBedSize::class)->findAll();
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $roomBedSizes;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getRoomTvs(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $roomTvs = $this->em->getRepository(RoomTv::class)->findAll();
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $roomTvs;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateCreateRoom($id, $name, $price, $sleeps, $status, $linkedRoom, $size, $beds, $stairs, $tv, $description, $kidsPolicy, $amenities): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $room = $this->em->getRepository(Rooms::class)->findOneBy(array('id' => $id));
            if ($room == null && strcmp($id, "0") ==0) {
                $room = new Rooms();
                $successMessage = "Successfully created room";
            }elseif ($room == null && strcmp($id, "0") !==0) {
                $responseArray[] = array(
                    'result_message' => "Room with ID not found",
                    'result_code' => 1
                );
                return $responseArray;
            }else{
                $successMessage = "Successfully updated room";
            }

            //check if room name is available
            if(!$this->isRoomNameAvailable($name, $id)){
                $responseArray[] = array(
                    'result_message' => "Name already used by another room",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check description length
            if(strlen($description) < 50 || strlen($description) > 500){
                $responseArray[] = array(
                    'result_message' => "Description length should be between 50 and 500",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check name length
            if(strlen($name) > 30){
                $responseArray[] = array(
                    'result_message' => "Room name length should have maximum of 30 characters",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check price
            if(strlen($price) > 3 || !is_numeric($price) || intval($price) < 1 ){
                $responseArray[] = array(
                    'result_message' => "Price must be a number greater than 1 and maximum length of 3",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check sleeps
            if(strlen($sleeps) > 2 || !is_numeric($sleeps) || intval($sleeps) < 1){
                $responseArray[] = array(
                    'result_message' => "Sleeps must be a number greater than 1 and maximum length of 2",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check room size
            if(strlen($size) > 3 || !is_numeric($size) || intval($size) < 1){
                $responseArray[] = array(
                    'result_message' => "Room Size must be a number greater than 1 and maximum length of 3",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check kids policy
            if(strlen($kidsPolicy) > 1 || !is_numeric($kidsPolicy) || intval($kidsPolicy) < 0 || intval($kidsPolicy) > 1 ){
                $responseArray[] = array(
                    'result_message' => "Kids policy must be a number between 0 and 1",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check stairs policy
            if(strlen($stairs) > 1 || !is_numeric($stairs) || intval($stairs) < 0 || intval($stairs) > 1 ){
                $responseArray[] = array(
                    'result_message' => "Stairs must be a number between 0 and 1",
                    'result_code' => 1
                );
                return $responseArray;
            }

            $beds = urldecode($beds);
            $beds = trim($beds);
            $bedsNameArray = explode(",", $beds);

            $this->logger->debug("selected beds: " . $beds);
            $tvType = $this->em->getRepository(RoomTv::class)->findOneBy(array('id' => $tv));
            if($tvType == null){
                $responseArray[] = array(
                    'result_message' => "TV not found with id " . $tv,
                    'result_code' => 1,
                );
                return $responseArray;
            }

            $roomStatus = $this->em->getRepository(RoomStatus::class)->findOneBy(array('id' => $status));
            if($roomStatus == null){
                $responseArray[] = array(
                    'result_message' => "Room status not found with id " . $status,
                    'result_code' => 1,
                );
                return $responseArray;
            }

            if (strlen($linkedRoom) > 1) {
                $linkRoom = $this->em->getRepository(Rooms::class)->findOneBy(array('id' => $linkedRoom));
                if($linkRoom == null){
                    $responseArray[] = array(
                        'result_message' => "Linked room id not found. " . $linkedRoom,
                        'result_code' => 1,
                    );
                    return $responseArray;
                }
                $room->setLinkedRoom($linkedRoom);
            }

            $propertyId = $_SESSION['PROPERTY_ID'];
            $property = $this->em->getRepository(Property::class)->findOneBy(array('id' => $propertyId));

            $room->setName($name);
            $room->setPrice($price);
            $room->setKids($kidsPolicy);
            $room->setSleeps($sleeps);
            $room->setStatus($roomStatus);
            $room->setLinkedRoom($linkedRoom);
            $room->setSize($size);
            $room->setStairs($stairs);
            $room->setDescription(urldecode($description));
            $room->setProperty($property);
            $room->setTv($tvType);
            $now = new DateTime();
            $room->setBdcLastExport($now);
            $room->setAirbnbLastExport($now);
            $room->setAmenities($amenities);
            $this->em->persist($room);
            $this->em->flush($room);

            //remove current beds
            $this->logger->debug("getting current beds");
            $currentSelectedBeds = $this->em->getRepository(RoomBeds::class)->findBy(array('room' => $room->getId()));

            if ($currentSelectedBeds !== null) {
                foreach ($currentSelectedBeds as $currentSelectedBed){
                    $this->logger->debug("removing new Bed " .$currentSelectedBed->getBed()->getName() );
                    $this->em->remove($currentSelectedBed);
                    $this->em->flush($currentSelectedBed);
                }
            }

            // add new selected beds
            //update beds

            foreach ($bedsNameArray as $bedName){
                $bed = $this->em->getRepository(RoomBedSize::class)->findOneBy(array('name' => trim($bedName)));
                if($bed == null){
                    $responseArray[] = array(
                        'result_message' => "Failed to find bed",
                        'result_code' => 1
                    );
                    return $responseArray;
                }
                $this->logger->debug("creating new Bed " .$bed->getName() );
                $roomBeds = new RoomBeds();
                $roomBeds->setRoom($room);
                $roomBeds->setBed($bed);
                $this->em->persist($roomBeds);
                $this->em->flush($roomBeds);
            }

            $responseArray[] = array(
                'result_message' => $successMessage,
                'result_code' => 0,
                'room_id' => $room->getId(),
                'room_name' => $room->getName()
            );
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

    public function removeImage($imageId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //get room images
            $roomImage = $this->em->getRepository(RoomImages::class)->findOneBy(array('name' => $imageId));

            if ($roomImage === null) {
                $responseArray[] = array(
                    'result_message' => "image not found",
                    'result_code' => 1
                );
            } else {
                if (strcmp($roomImage->getStatus(), 'default') === 0) {
                    $responseArray[] = array(
                        'result_message' => "Can not delete image as it is set as the default image",
                        'result_code' => 1
                    );
                } else {
                    $roomImage->setStatus("removed");
                    $this->em->persist($roomImage);
                    $this->em->flush($roomImage);

                    $responseArray[] = array(
                        'result_message' => "Successfully removed image",
                        'result_code' => 0
                    );
                }
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function markDefault($imageName): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //get room images
            $roomImage = $this->em->getRepository(RoomImages::class)->findOneBy(array('name' => $imageName));

            if ($roomImage === null) {
                $responseArray[] = array(
                    'result_message' => "image not found",
                    'result_code' => 1
                );
            } else {
                //remove the current default
                $roomDefaultImage = $this->em->getRepository(RoomImages::class)->findOneBy(array('status' => 'default', 'room' => $roomImage->getRoom()->getId()));
                if ($roomDefaultImage != null) {
                    $roomDefaultImage->setStatus("active");
                    $this->em->persist($roomDefaultImage);
                    $this->em->flush($roomDefaultImage);
                }

                $roomImage->setStatus("default");
                $this->em->persist($roomImage);
                $this->em->flush($roomImage);

                $responseArray[] = array(
                    'result_message' => "Successfully marked image as default",
                    'result_code' => 0
                );
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    private function isRoomNameAvailable($name, $id)
    {
        $room = $this->em->getRepository(Rooms::class)->findOneBy(array('name' => $name));
        if($room == null){
            return true;
        }else{
            if($room->getId() == $id){
                return true;
            }else{
                return false;
            }
        }
    }


}