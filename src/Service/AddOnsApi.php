<?php

namespace App\Service;

use App\Entity\AddOns;
use App\Entity\Payments;
use App\Entity\Property;
use App\Entity\ReservationAddOns;
use App\Entity\Reservations;
use App\Helpers\SMSHelper;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\PaymentApi;

class AddOnsApi
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

    public function getAddOn($addOnName)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $securityApi = new SecurityApi($this->em, $this->logger);
            if (!$securityApi->isLoggedInBoolean()) {
                $responseArray[] = array(
                    'result_message' => "Session expired, please logout and login again",
                    'result_code' => 1
                );
            } else {
                $propertyId = $_SESSION['PROPERTY_ID'];
                return $this->em->getRepository(AddOns::class)->findOneBy(
                    array("name" => $addOnName,
                        'property' => $propertyId));
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

    public function getAddOns()
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $propertyId = $_SESSION['PROPERTY_ID'];
            return $this->em->getRepository(AddOns::class)->findBy(array('property' => $propertyId, 'status' => 'live'));
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return null;
    }

    public function getAddOnsJson($addOnId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $addOn = $this->em->getRepository(AddOns::class)->findOneBy(array('id' => $addOnId));
            if ($addOn === null) {
                $responseArray[] = array(
                    'result_message' => "Add on not found for id $addOnId",
                    'result_code' => 1
                );
            } else {
                $responseArray[] = array(
                    'id' => $addOn->getId(),
                    'name' => $addOn->getName(),
                    'price' => $addOn->getPrice(),
                    'property' => $addOn->getProperty()->getId(),
                    'status' => $addOn->getStatus(),
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

    public function getReservationAddOns($resId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $addOns = $this->em->getRepository(ReservationAddOns::class)->findBy(array('reservation' => $resId));
            $this->logger->debug("no errors finding add ons for reservation $resId. add on count " . count($addOns));
            return $addOns;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error("failed to get add ons " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function addAdOnToReservation($resId, $adOnId, $quantity): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $addOn = $this->em->getRepository(AddOns::class)->findOneBy(array('id' => intval($adOnId)));
            if ($addOn == null) {
                $responseArray[] = array(
                    'result_message' => "Please select a valid add on item",
                    'result_code' => 1
                );
                return $responseArray;
            }
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id' => intval($resId)));

            $resAddOn = new ReservationAddOns();
            $resAddOn->setAddOn($addOn);
            $resAddOn->setQuantity(intval($quantity));
            $resAddOn->setReservation($reservation);
            $resAddOn->setDate(new DateTime());

            $this->em->persist($resAddOn);
            $this->em->flush($resAddOn);

            //update add-on quantity
            if ($addOn->getQuantity() !== 0) {
                $currentQuantity = $addOn->getQuantity();
                $newQuantity = $currentQuantity - intval($quantity);
                $addOn->setQuantity($newQuantity);
                $this->em->persist($addOn);
                $this->em->flush($addOn);

                if ($newQuantity < $addOn->getMinimum() || $newQuantity == $addOn->getMinimum()) {
                    $messageBody = "Stock low for " . $addOn->getName() . ". Quantity: " . $newQuantity;
                    $SMSHelper = new SMSHelper($this->logger);
                    $SMSHelper->sendMessage("+27837917430", $messageBody);
                }
            }


            $responseArray[] = array(
                'result_message' => 'Successfully added add on to the reservation',
                'result_code' => 0
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

    public function updateAddOn($addOnId, $field, $newValue)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $addOn = $this->em->getRepository(AddOns::class)->findOneBy(array("id" => $addOnId));
            if ($addOn === null) {
                $responseArray[] = array(
                    'result_message' => "Addon not found",
                    'result_code' => 1
                );
                $this->logger->debug(print_r($responseArray, true));
                return $responseArray;
            }

            switch ($field) {
                case "price":
                    if (strlen($newValue) > 4 || strlen($newValue) == 0) {
                        $responseArray[] = array(
                            'result_message' => "Add-on price Length should be between 1 and 4",
                            'result_code' => 1
                        );
                        return $responseArray;
                    }
                    $addOn->setPrice($newValue);
                    break;
                case "name":
                    if (strlen($newValue) > 50 || strlen($newValue) == 0) {
                        $responseArray[] = array(
                            'result_message' => "Add-on name Length should be between 1 and 500",
                            'result_code' => 1
                        );
                        return $responseArray;
                    }
                    $addOn->setName($newValue);
                    break;
                case "quantity":
                    if (strlen($newValue) > 2 || strlen($newValue) == 0) {
                        $responseArray[] = array(
                            'result_message' => "Add-on quantity Length should be between 1 and 2",
                            'result_code' => 1
                        );
                        return $responseArray;
                    }
                    $addOn->setQuantity(intval($newValue));
                    break;
                default:
                    $responseArray[] = array(
                        'result_message' => "field not found",
                        'result_code' => 1
                    );
                    break;
            }
            $this->em->persist($addOn);
            $this->em->flush($addOn);

            $responseArray[] = array(
                'result_message' => "Successfully updated add-on",
                'result_code' => 0
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

    public function deleteAddOn($addOnId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $addOn = $this->em->getRepository(AddOns::class)->findOneBy(array("id" => $addOnId, "status"=>"live"));
            if ($addOn === null) {
                $responseArray[] = array(
                    'result_message' => "Addon not found",
                    'result_code' => 1
                );
                $this->logger->debug(print_r($responseArray, true));
            } else {
                $addOn->setStatus("deleted");
                $this->em->persist($addOn);
                $this->em->flush();
                $responseArray[] = array(
                    'result_message' => "Successfully deleted add-on",
                    'result_code' => 0
                );
            }
        } catch (Exception $ex) {
            $this->logger->debug($ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString());
            $responseArray[] = array(
                'result_message' => "Failed to delete employee",
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function createAddOn($addOnName, $addOnPrice)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $this->logger->debug("attempting to talk to db");
            //check if add-on with the same name does not exist
            $existingAddOn = $this->em->getRepository(AddOns::class)->findBy(array('name' => $addOnName, 'status' => 'live'));
            $this->logger->debug("db connect done success");
            if ($existingAddOn != null) {
                $responseArray[] = array(
                    'result_message' => "Add on with the same name already exists",
                    'result_code' => 1
                );
                return $responseArray;

            }
            if (strlen($addOnName) > 50 || strlen($addOnName) == 0 ) {
                $responseArray[] = array(
                    'result_message' => "Add-on name Length should be between 1 and 500",
                    'result_code' => 1
                );
                return $responseArray;
            }

            if (strlen($addOnPrice) > 4 || strlen($addOnPrice) == 0 || !is_numeric($addOnPrice)) {
                $responseArray[] = array(
                    'result_message' => "Add-on price Length should be between 1 and 4 and should be a number",
                    'result_code' => 1
                );
                return $responseArray;
            }

            $property = $this->em->getRepository(Property::class)->findOneBy(array('id' => $_SESSION['PROPERTY_ID']));
            $addOn = new AddOns();
            $addOn->setPrice($addOnPrice + 10);
            $addOn->setName($addOnName);
            $addOn->setProperty($property);
            $this->em->persist($addOn);
            $this->em->flush($addOn);
            $responseArray[] = array(
                'result_message' => "Successfully created add on",
                'result_code' => 0,
                'add_on_id' => $addOn->getId()
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

    public function getAddOnsForInvoice($resId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $html = "";
        try {
            $addOns = $this->em->getRepository(ReservationAddOns::class)->findBy(array('reservation' => $resId));
            $this->logger->debug("number of add ons " . count($addOns));
            foreach ($addOns as $addOn) {
                $totalPriceForAllAdOns = (intVal($addOn->getAddOn()->getPrice()) * intval($addOn->getQuantity()));
                $html .= '<tr class="item">
					<td>' . $addOn->getAddOn()->getName() . '</td>
					<td>' . $addOn->getQuantity() . '</td>
					<td>R' . number_format((float)$addOn->getAddOn()->getPrice() + 1, 2, '.', '') . '</td>
					<td>R' . number_format((float)$totalPriceForAllAdOns + 1, 2, '.', '') . '</td>
				</tr>';
            }
            $this->logger->debug($html);
            return $html;
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return $html;
        }
    }

    public function getAddOnsTotal($resId): float|int
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $html = "";
        try {
            $addOns = $this->em->getRepository(ReservationAddOns::class)->findBy(array('reservation' => $resId));
            $totalPriceForAllAdOns = 0;
            foreach ($addOns as $addOn) {
                $totalPriceForAllAdOns += (intVal($addOn->getAddOn()->getPrice()) * intval($addOn->getQuantity()));
            }
            $this->logger->debug($html);
            return $totalPriceForAllAdOns;
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return 0;
        }
    }


    public function removeAddOnFromReservation($addOnId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $reservationAddOn = $this->em->getRepository(ReservationAddOns::class)->findOneBy(array('id' => $addOnId));
            $this->em->remove($reservationAddOn);
            $this->em->flush($reservationAddOn);


            //update add-on quantity
            $addOn = $reservationAddOn->getAddOn();
            $quantity = $reservationAddOn->getQuantity();
            if ($addOn->getQuantity() !== 0) {
                $currentQuantity = $addOn->getQuantity();
                $newQuantity = $currentQuantity + intval($quantity);
                $addOn->setQuantity($newQuantity);
                $this->em->persist($addOn);
                $this->em->flush($addOn);
            }

            $responseArray[] = array(
                'result_message' => "Successfully removed reservation Add-on",
                'result_code' => 0
            );

        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
        }

        return $responseArray;
    }

}