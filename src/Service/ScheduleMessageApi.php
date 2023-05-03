<?php

namespace App\Service;

use App\Entity\MessageTemplate;
use App\Entity\MessageVariables;
use App\Entity\Property;
use App\Entity\Rooms;
use App\Entity\ScheduleMessages;
use App\Entity\ScheduleTimes;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class ScheduleMessageApi
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        if (session_id() === '') {
            $logger->info("Session id is empty");
            session_start();
        }
    }

    public function createScheduleMessage($messageId, $scheduleId, $rooms): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $messageTemplate = $this->em->getRepository(MessageTemplate::class)->findOneBy(array('id' => $messageId));

            if ($messageTemplate === null) {
                $responseArray[] = array(
                    'result_message' => "Message template not found",
                    'result_code' => 1
                );
                return $responseArray;
            }
            $scheduleTime = $this->em->getRepository(ScheduleTimes::class)->findOneBy(array('id' => $scheduleId));
            if ($scheduleTime === null) {
                $responseArray[] = array(
                    'result_message' => "Message schedule not found",
                    'result_code' => 1
                );
                return $responseArray;
            }

            $roomsArray = explode(",", $rooms);
            foreach ($roomsArray as $roomId) {
                //check if duplicate
                $this->logger->debug("sql for checking duplicate");
                $scheduleMessage = $this->em->getRepository(ScheduleMessages::class)->findOneBy(
                    array('messageSchedule' => $scheduleId,
                        'messageTemplate' => $messageId,
                        'room' => $roomId));

                $room = $this->em->getRepository(Rooms::class)->findOneBy(
                    array('id' => $roomId));
                if ($room != null) {
                    if ($scheduleMessage === null) {
                        $scheduleMessage = new ScheduleMessages();
                        $scheduleMessage->setMessageSchedule($scheduleTime);
                        $scheduleMessage->setMessageTemplate($messageTemplate);
                        $scheduleMessage->setRoom($room);
                        $this->em->persist($scheduleMessage);
                        $this->em->flush($scheduleMessage);

                        $responseArray[] = array(
                            'result_message' => "Successfully created schedule message",
                            'result_code' => 0
                        );
                    } else {
                        $responseArray[] = array(
                            'result_message' => "Schedule message with the same template and schedule already created",
                            'result_code' => 1
                        );
                    }
                }
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getScheduleTimes(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $scheduleTimes = $this->em->getRepository(ScheduleTimes::class)->findAll();
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            foreach ($scheduleTimes as $scheduleTime) {
                $responseArray[] = array(
                    'name' => $scheduleTime->getName(),
                    'id' => $scheduleTime->getId()
                );
            }

        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getScheduleTemplates(): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $html = "";
        try {
            $propertyApi = new PropertyApi($this->em, $this->logger);
            $propertyId = $_SESSION['PROPERTY_ID'];
            $messageTemplates = $this->em->getRepository(MessageTemplate::class)->findBy(array('property' => $propertyId));
            foreach ($messageTemplates as $messageTemplate) {
                $html .= '<option value="' . $messageTemplate->getId() . '" class="template_option">' . $messageTemplate->getName() . '</option>';
            }
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $html;
        } catch (Exception $ex) {

            $this->logger->error($ex->getMessage());
            return $ex->getMessage();
        }

    }

    public function getScheduledMessages(): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $html = '<table id="scheduled_messages_table">
                            <tr>
                                <th>Template</th>
                                <th>Schedule</th>
                                <th>Rooms</th>
                                <th>Delete</th>
                            </tr>';
        try {
            $propertyApi = new PropertyApi($this->em, $this->logger);
            $propertyId = $_SESSION['PROPERTY_ID'];
            $rooms = $this->em->getRepository(Rooms::class)->findBy(array('property' => $propertyId));
            foreach ($rooms as $room) {
                $scheduleMessages = $this->em->getRepository(ScheduleMessages::class)->findBy(array('room' => $room->getId()));

                foreach ($scheduleMessages as $scheduleMessage) {
                    $html .= '<tr>
                                <td>' . $scheduleMessage->getMessageTemplate()->getName() . '</td>
                                <<td>' . $scheduleMessage->getMessageSchedule()->getName() . '</td>
                                <td>' . $room->getName() . '</td>
                                <td><input type="submit" value="Delete" class="deleteScheduledMessage" data-id="' . $scheduleMessage->getId() . '"></td>
                            </tr>
                           ';
                }
            }

            $html .= '</table>';


            $this->logger->debug("Ending Method before the return: " . __METHOD__);
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $html;
    }

    public function getMessageVariables(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $messageVariables = $this->em->getRepository(MessageVariables::class)->findAll();
            foreach ($messageVariables as $messageVariable) {
                $responseArray[] = array("name" => $messageVariable->getName());
            }
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $responseArray;
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function sendScheduledMessages($scheduleTimeName): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $scheduleTime = $this->em->getRepository(ScheduleTimes::class)->findOneBy(array('name' => $scheduleTimeName));
            $scheduleMessages = $this->em->getRepository(ScheduleMessages::class)->findBy(array('messageSchedule' => $scheduleTime));
            $reservationApi = new ReservationApi($this->em, $this->logger);

            if ($scheduleMessages !== null) {
                foreach ($scheduleMessages as $scheduleMessage) {
                    $this->logger->debug("found schedule message " . $scheduleMessage->getId());
                    $checkInReservations = null;
                    $checkOutReservations = null;
                    if($scheduleTime->getDays()>-1){
                        $checkInReservations = $reservationApi->getReservationsByRoomAndDaysToCheckIn($scheduleMessage->getRoom()->getId(), $scheduleTime->getDays());
                    }else{
                        $checkOutReservations = $reservationApi->getReservationsByRoomAndDaysAfterCheckOut($scheduleMessage->getRoom()->getId(), $scheduleTime->getDays());
                    }
                    $reservations = array();
                    if($checkInReservations !== null){
                        foreach ($checkInReservations as $reservation) {
                            $reservations[] = $reservation;
                        }
                    }

                    if($checkOutReservations !== null){
                        foreach ($checkOutReservations as $reservation) {
                            $reservations[] = $reservation;
                        }
                    }


                    if ($reservations !== null) {
                        foreach ($reservations as $reservation) {
                            $this->logger->debug("found reservation ID " . $reservation->getId());
                            $message = $this->ReplaceMessageVariables($reservation, $scheduleMessage->getMessageTemplate());
                            $email = $reservation->getGuest()->getEmail();
                            if (!empty($email)) {
                                $message = wordwrap($message, 70);
                                $communicationApi = new CommunicationApi($this->em, $this->logger);
                                $communicationApi->sendEmailViaGmail(ALUVEAPP_ADMIN_EMAIL, $email, $message, $reservation->getRoom()->getProperty()->getName() . " - Message", $reservation->getRoom()->getProperty()->getName(), $reservation->getRoom()->getProperty()->getEmailAddress());
                                $this->logger->debug("Sending email for " . $message);
                                $responseArray[] = array(
                                    'result_message' => 'Successfully sent scheduled message for ' . $message,
                                    'result_code' => 0
                                );
                            } else {
                                $this->logger->debug("Email not found for guest");
                                $responseArray[] = array(
                                    'result_message' => 'Email not found for guest ' . $message,
                                    'result_code' => 1
                                );
                            }
                        }
                    } else {
                        $responseArray[] = array(
                            'result_message' => 'reservations not found',
                            'result_code' => 1
                        );
                        $this->logger->debug("found reservation ID " . print_r($responseArray));
                        return $responseArray;
                    }
                }
            } else {
                $responseArray[] = array(
                    'result_message' => 'Schedule Messages not found ',
                    'result_code' => 1
                );
                $this->logger->debug("found reservation ID " . print_r($responseArray));
                return $responseArray;
            }

            $this->logger->debug("Ending Method before the return: " . __METHOD__);
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    function ReplaceMessageVariables($reservation, $messageTemplate): array|string|null
    {
        try {
            $template = $messageTemplate->getMessage();
            $template = str_replace("guest_name", $reservation->getGuest()->getName(), $template);
            $template = str_replace("check_in", $reservation->getCheckIn()->format("Y-m-d"), $template);
            $template = str_replace("check_out", $reservation->getCheckOut()->format("Y-m-d"), $template);
            return str_replace("room_name", $reservation->getRoom()->getName(), $template);
        } catch (Exception) {
            return null;
        }
    }

    public function deleteScheduledMessages($scheduleMessageId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $scheduleMessage = $this->em->getRepository(ScheduleMessages::class)->findOneBy(array('id' => $scheduleMessageId));
            if ($scheduleMessage === null) {
                $responseArray[] = array(
                    'result_message' => "Schedule message with id $scheduleMessageId not found",
                    'result_code' => 1
                );
            } else {
                $this->em->remove($scheduleMessage);
                $this->em->flush($scheduleMessage);

                $responseArray[] = array(
                    'result_message' => "Successfully removed the scheduled message",
                    'result_code' => 0
                );
            }


            $this->logger->debug("Ending Method before the return: " . __METHOD__);
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error($ex->getMessage());
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function createMessageTemplate($name, $message): array
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
                $propertyApi = new PropertyApi($this->em, $this->logger);
                $propertyId = $_SESSION['PROPERTY_ID'];
                $messageTemplate = $this->em->getRepository(MessageTemplate::class)->findOneBy(array('name' => $name, 'property' => $propertyId));

                if ($messageTemplate != null) {
                    $responseArray[] = array(
                        'result_message' => "Message template with the same name found",
                        'result_code' => 1
                    );
                    return $responseArray;
                }
                $property = $this->em->getRepository(Property::class)->findOneBy(array('id' => $_SESSION['PROPERTY_ID']));
                $messageTemplate = new MessageTemplate();
                $messageTemplate->setName($name);
                $messageTemplate->setMessage($message);
                $messageTemplate->setProperty($property);
                $this->em->persist($messageTemplate);
                $this->em->flush($messageTemplate);
                $responseArray[] = array(
                    'result_message' => 'Successfully created message template',
                    'result_code' => 0
                );
            }

        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() ,
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getTemplateMessage($templateId): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $template = $this->em->getRepository(MessageTemplate::class)->findOneBy(array('id' => $templateId));
            $this->logger->debug("Ending Method before the return: " . __METHOD__);
            return $template->getMessage();
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return $ex->getMessage();
        }
    }
}