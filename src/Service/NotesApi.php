<?php

namespace App\Service;

use App\Entity\ReservationNotes;
use App\Entity\Reservations;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class NotesApi
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        if(session_id() === ''){
            $logger->info("Session id is empty");
            session_start();
        }
    }

    public function addNote($resId, $note)
    {
        $this->logger->debug("Starting Method: " . __METHOD__ );
        $responseArray = array();
        try{
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id'=>$resId));
            $reservationNotes = new ReservationNotes();

            $reservationNotes->setReservation($reservation);
            $reservationNotes->setNote($note);
            $reservationNotes->setDate(new DateTime());
            $this->em->persist($reservationNotes);
            $this->em->flush($reservationNotes);
            $responseArray[] = array(
                'result_code' => 0,
                'result_message' => 'Successfully added note'
            );
            $this->logger->debug("no errors adding note for reservation $resId. note $note");
        }catch(Exception $ex){
            $responseArray[] = array(
                'result_message' => $ex->getMessage() .' - '. __METHOD__ . ':' . $ex->getLine() . ' ' .  $ex->getTraceAsString(),
                'result_code'=> 1
            );
            $this->logger->debug("failed to get payments " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__ );
        return $responseArray;
    }

    public function getReservationNotes($resId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__ );
        $responseArray = array();
        try{
            $notes = $this->em->getRepository(ReservationNotes::class)->findBy(array('reservation'=>$resId));
            $this->logger->debug("no errors finding notes for reservation $resId. notes count " . count($notes));
            return $notes;
        }catch(Exception $ex){
            $responseArray[] = array(
                'result_message' => $ex->getMessage() .' - '. __METHOD__ . ':' . $ex->getLine() . ' ' .  $ex->getTraceAsString(),
                'result_code'=> 1
            );
            $this->logger->debug("failed to get notes " . print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__ );
        return $responseArray;
    }
}