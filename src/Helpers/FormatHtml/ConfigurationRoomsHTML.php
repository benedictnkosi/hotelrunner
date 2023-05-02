<?php

namespace App\Helpers\FormatHtml;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class ConfigurationRoomsHTML
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    public function formatLeftDivRoomsHtml($rooms): string
    {
        $htmlString = "";

        foreach ($rooms as $room) {
            $htmlString .='<a href="javascript:void(0)" data-roomId="'.$room->getId().'"
                           class="ClickableButton roomsMenu">'.$room->getName().'
                        </a>';
        }

        $htmlString .='<a href="javascript:void(0)"
                           class="ClickableButton roomsMenu" id="add_new_room_button" data-roomId="0">Add New Room
                           
    </a>';

        return $htmlString;
    }

    public function formatComboListHtml($items, $withSelectOption = false): string
    {
        $htmlString = "";
        if($withSelectOption){
            $htmlString .='<option value="0" >Please Select</option>';
        }

        $i = 0;
        foreach ($items as $item) {
            $i++;
            $htmlString .='<option value="'.$item->getId().'" >'.$item->getName().'</option>';
        }

        return $htmlString;
    }


}