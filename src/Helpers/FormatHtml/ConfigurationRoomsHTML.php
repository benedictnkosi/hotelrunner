<?php

namespace App\Helpers\FormatHtml;

use App\Service\DefectApi;
use App\Service\RoomApi;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class ConfigurationRoomsHTML
{
    private $em;
    private $logger;
    private $defectApi;
    private $roomApi;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->defectApi = new DefectApi($entityManager, $logger);

        $this->roomApi = new RoomApi($entityManager, $logger);
    }

    public function formatLeftDivRoomsHtml($rooms): string
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $htmlString = "";
        if ($rooms != null) {
            $this->createFlatFile($rooms, __DIR__ . '/../../../files/' . "rooms.dat");

            if($this->defectApi->isFunctionalityEnabled("download_rooms_flatfile")) {
                $htmlString .= '<a href="/api/files/rooms.dat" target="_blank" class="ClickableButton roomsMenu" >Download Flat File</a>';
            }

            foreach ($rooms as $room) {
                $htmlString .= '<a href="javascript:void(0)" data-roomId="' . $room->getId() . '"
                           class="ClickableButton roomsMenu">' . $room->getName() . '
                        </a>';
            }

            $htmlString .= '<a href="javascript:void(0)"
                           class="ClickableButton roomsMenu" id="add_new_room_button" data-roomId="0">Add New Room
                           
    </a>';


        }
        return $htmlString;
    }

    public function formatComboListHtml($items, $withSelectOption = false): string
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        $htmlString = "";
        if ($withSelectOption) {
            $htmlString .= '<option value="0" >Please Select</option>';
        }

        $i = 0;
        if ($this->defectApi->isDefectEnabled("create_room_2")) {
            $i++;
        }
        foreach ($items as $item) {
            $i++;
            $htmlString .= '<option value="' . $item->getId() . '" >' . $item->getName() . '</option>';
        }

        return $htmlString;
    }

    function createFlatFile($rooms, $fileName): void
    {
        $this->logger->info("Starting Method: " . __METHOD__);
        try {
            $cfile = fopen($fileName, 'w');
            $this->logger->info("file name is: " . $fileName);
            foreach ($rooms as $room) {
                $this->logger->info("Looping rooms: " . $room->getId());
                if (is_array($room)) {
                    return;
                }

                $roomBeds = $this->roomApi->getRoomBeds($room->getId());

                $roomBedsString = "";
                foreach ($roomBeds as $roomBed) {
                    $roomBedsString .= $roomBed["name"] . ",";
                }
                $roomBedsString = substr($roomBedsString, 0, strlen($roomBedsString) - 1);
                $this->logger->debug("rooms string is " . $roomBedsString);

                $amenitiesString =$room->getAmenities();
                $amenitiesString = str_replace('"', "", $amenitiesString);
                $amenitiesString = str_replace('[', "", $amenitiesString);
                $amenitiesString = str_replace(']', "", $amenitiesString);

                $id = str_pad($room->getId(), 6, "0", STR_PAD_LEFT);
                $price = str_pad($room->getPrice(), 4, "0", STR_PAD_LEFT);
                $sleeps = str_pad($room->getSleeps(), 2, "0", STR_PAD_LEFT);
                $status = str_pad($room->getStatus()->getName(), 12);
                $linkedRoom = str_pad($room->getLinkedRoom(), 6, "0", STR_PAD_LEFT);
                $size = str_pad($room->getSize(), 2, "0", STR_PAD_LEFT);
                $roomBedsString = str_pad($roomBedsString, 18);
                $stairs = str_pad($room->getStairs(), 2, "0", STR_PAD_LEFT);
                $tv = str_pad($room->getTv()->getName(), 18);
                $kidsPolicy = str_pad($room->isKids(), 6);
                $amenities = str_pad($amenitiesString, 50);
                $name = str_pad($room->getName(), 50);
                $description = str_pad($room->getDescription(), 500);

                if($this->defectApi->isDefectEnabled("download_room_1")){
                    $name = str_pad(substr($name, 0,10), 50);
                }

                if($this->defectApi->isDefectEnabled("download_room_2")){
                    $size = str_pad("32", 2, "0", STR_PAD_LEFT);
                }

                $row = $id . $price . $sleeps . $status . $linkedRoom . $size . $roomBedsString. $stairs . $tv . $kidsPolicy . $amenities . $name . $description . "\n";
                fwrite($cfile, $row);
                $this->logger->debug("Done writing to file ");
            }


// Closing the file
            fclose($cfile);
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }


    }


}