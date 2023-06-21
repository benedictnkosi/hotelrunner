<?php

namespace App\Service;

use App\Entity\Defect;
use App\Entity\Functionality;
use App\Entity\ReservationNotes;
use App\Entity\Reservations;
use App\Entity\Rooms;
use App\Entity\ScheduleMessages;
use DateTime;
use Doctrine\ORM\Query\Expr\Func;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class DefectApi
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        if (session_id() === '') {
            $logger->info("Session id is empty");
            session_start();
        }
    }


    public function isDefectEnabled($defectName): bool
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $defect = $this->em->getRepository(Defect::class)->findOneBy(array('name' => $defectName));
            return $defect->isEnabled();
        } catch (Exception $ex) {
            $this->logger->debug("failed to get defect " . $ex->getMessage());
            return false;
        }
    }

    public function getDefects(): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $html = '<table id="defects-table" class="any-table">
                            <tr>
                                <th>Area</th>
                                <th>Name</th>
                                <th>Message</th>
                                <th>Enabled</th>
                            </tr>';
        try {


            $defects = $this->em->getRepository(Defect::class)->findBy(array(), array('area' => 'ASC', 'name' => 'ASC'));
            foreach ($defects as $defect) {
                $checked = "";
                if ($defect->isEnabled()) {
                    $checked = "checked";
                }
                $html .= '<tr>
                                <td>' . $defect->getArea() . '</td>
                                <td>' . $defect->getName() . '</td>
                                <td>' . $defect->getMessage() . '</td>
                                <td><input type="checkbox" value="Delete" class="defect_checkbox" data-id="' . $defect->getId() . '" ' . $checked . '/></td>
                            </tr>
                           ';
            }

            $html .= '</table>';


            $this->logger->debug("Ending Method before the return: " . __METHOD__);
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $html;
    }

    public function isFunctionalityEnabled($name): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        try {
            $functionality = $this->em->getRepository(Functionality::class)->findOneBy(array('name'=>$name));
            return $functionality->isEnabled();
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return false;
        }
    }

    public function getFunctionality(): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $html = '<table id="defects-table" class="any-table">
                            <tr>
                                <th>Area</th>
                                <th>Name</th>
                                <th>Enabled</th>
                            </tr>';
        try {


            $functionalities = $this->em->getRepository(Functionality::class)->findBy(array(), array('area' => 'ASC', 'name' => 'ASC'));
            foreach ($functionalities as $functionality) {
                $checked = "";
                if ($functionality->isEnabled()) {
                    $checked = "checked";
                }
                $html .= '<tr>
                                 <td>' . $functionality->getArea() . '</td>
                                <td>' . $functionality->getName() . '</td>
                                <td><input type="checkbox" value="state" class="functionality_checkbox" data-id="' . $functionality->getFunctionalityId() . '" ' . $checked . '/></td>
                            </tr>
                           ';
            }

            $html .= '</table>';


            $this->logger->debug("Ending Method before the return: " . __METHOD__);
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $html;
    }


    public function updateDefectEnabled($defectId, $enabled): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {

            $defect = $this->em->getRepository(Defect::class)->findOneBy(array("id" => $defectId));

            if ($defect === null) {
                $responseArray = array(
                    'result_message' => "Defect not found",
                    'result_code' => 1
                );
                $this->logger->debug(print_r($responseArray, true));
                return $responseArray;
            } else {
                $defect->setEnabled($enabled);
                $this->em->persist($defect);
                $this->em->flush($defect);

                $responseArray = array(
                    'result_message' => "Successfully updated defect",
                    'result_code' => 0
                );
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


    public function updateFunctionalityEnabled($functionalityId, $enabled): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {

            $functionality = $this->em->getRepository(Functionality::class)->findOneBy(array("functionalityId" => $functionalityId));

            if ($functionality === null) {
                $responseArray = array(
                    'result_message' => "Functionality not found",
                    'result_code' => 1
                );
                $this->logger->debug(print_r($responseArray, true));
                return $responseArray;
            } else {
                $functionality->setEnabled($enabled);
                $this->em->persist($functionality);
                $this->em->flush($functionality);

                $responseArray = array(
                    'result_message' => "Successfully updated functionality",
                    'result_code' => 0
                );
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

}