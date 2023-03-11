<?php

namespace App\Service;

use App\Entity\Property;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

require_once(__DIR__ . '/../app/application.php');

class SecurityApi
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    public function login($pin): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $property = $this->em->getRepository(Property::class)->findOneBy(array('secret' => $pin));
            if ($property != null) {
                if (session_id() === '') {
                    session_start();
                }

                $responseArray[] = array(
                    'property_id' => $property->getId(),
                    'property_uid' => $property->getUid(),
                    'result_message' => "Success",
                    'result_code' => 0
                );
                return $responseArray;
            } else {
                $responseArray[] = array(
                    'result_message' => "Failed to authenticate the pin $pin",
                    'result_code' => 1
                );
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() .' - '. __METHOD__ . ':' . $ex->getLine() . ' ' .  $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function isLoggedInBoolean(): bool
    {
        $result = $this->isLoggedIn();
        return $result[0]['logged_in'];
    }

    public function isLoggedIn(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $property = $this->em->getRepository(Property::class)->findOneBy(array('id' => $_SESSION['PROPERTY_ID']));
            if ($property != null) {
                $responseArray[] = array(
                    'logged_in' => true
                );
            } else {
                $responseArray[] = array(
                    'logged_in' => false
                );
            }

        } catch (Exception $ex) {
            $responseArray[] = array(
                'logged_in' => false,
                'exception' => $ex->getMessage()
            );
            $this->logger->error(print_r($responseArray, true));
        }
        return $responseArray;
    }
}