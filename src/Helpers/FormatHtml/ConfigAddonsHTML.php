<?php

namespace App\Helpers\FormatHtml;

use App\Service\DefectApi;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Isset_;
use Psr\Log\LoggerInterface;

class ConfigAddonsHTML
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

    public function formatHtml($addOns): string
    {
        $html = '';
        if ($addOns != null) {
            $this->createFlatFile($addOns, __DIR__ . '/../../../files/' . "addons.dat");

            if($this->defectApi->isFunctionalityEnabled("download_addons_flatfile")) {
                $html .= '<a href="/no_auth/files/dat/addons.dat">Download Flat File</a>';
            }

            foreach ($addOns as $addOn) {
                $html .= '<div class="addon_row">
                        <div class="addon-left-div">
                            <label>Add-ons Name</label>
                            <input type="text" class="addon_field"  maxlength="50" data-addon-id="'.$addOn->getId().'" data-addon-field="name" value="'.$addOn->getName().'"
                                   required/>
                                   <div class="ClickableButton remove_addon_button" data-addon-id="'.$addOn->getId().'" >Remove</div>
                                   
                        </div>
                        <div class="addon-right-div">
                            <label>Price</label>
                            <input type="text" class="addon_field" maxlength="4" data-addon-id="'.$addOn->getId().'" data-addon-field="price" value="'.$addOn->getPrice().'"
                                   required/>
                                   
                        </div>
                        
                        <div class="addon-right-div">
                            <label>Quantity</label>
                            <input type="text" class="addon_field" maxlength="2" data-addon-id="'.$addOn->getId().'" data-addon-field="quantity" value="'.$addOn->getQuantity().'"
                                   required/>
                                   
                        </div>
                    </div>';
            }
        } else {
            $html .= '<h5>No add-ons found</h5>';
        }

        return $html;
    }


    function createFlatFile($addons, $fileName): void
    {
        try {
            $cfile = fopen($fileName, 'w');

            foreach ($addons as $addon) {
                if(is_array($addon)){
                    return;
                }

                $id = str_pad($addon->GetId(), 6,"0", STR_PAD_LEFT);
                $name = str_pad($addon->getName(), 50);
                $price = str_pad($addon->getPrice(), 4,"0", STR_PAD_LEFT);
                $quantity = str_pad($addon->getQuantity(), 2,"0", STR_PAD_LEFT);

                if($this->defectApi->isDefectEnabled("download_addon_1")){
                    $name = str_pad(substr($addon->getName(), 0,10), 50);
                }

                if($this->defectApi->isDefectEnabled("download_addon_2")){
                    $quantity += 1;
                }

                $row = $id. $name. $price . $quantity .  "\n";
                fwrite($cfile, $row);
            }

        // Closing the file
            fclose($cfile);
        } catch (\Exception $exception) {
            fclose($cfile);
            $this->logger->debug($exception->getMessage());
        }


    }

}