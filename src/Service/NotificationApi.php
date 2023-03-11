<?php

namespace App\Service;

use App\Entity\Notification;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class NotificationApi
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

    public function updateAdsNotification($propertyId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $reservationApi = new ReservationApi($this->em, $this->logger);
            $isAllRoomsBooked = $reservationApi->isAllRoomsBooked($propertyId);
            if ($isAllRoomsBooked) {
                $now = new DateTime();
                $notification = $this->em->getRepository(Notification::class)->findOneBy(array('name' => 'Pause Google Ads'));
                $notification->setActioned(false);
                $notification->setDate($now);
                $this->em->persist($notification);
                $this->em->flush($notification);
                return "Successfully updated ads notification";
            }
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return $ex->getMessage();
        }
    }

    public function updateAdsNotificationAction($name, $action): string
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $now = new DateTime();
            $notification = $this->em->getRepository(Notification::class)->findOneBy(array('name' => $name));
            $notification->setActioned($action);
            $notification->setDate($now);
            $this->em->persist($notification);
            $this->em->flush($notification);
            return "Successfully updated ads notification";
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return $ex->getMessage();
        }
    }

    public function getNotificationsToAction(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $responseArray = array();

            $notifications = $this->em->getRepository(Notification::class)->findBy(array('actioned' => false));
            foreach ($notifications as $notification){
                $responseArray[] = array(
                    'name' => $notification->getName(),
                    'link' =>  $notification->getLink(),
                    'message' =>  $notification->getMessage()
                );
            }

            return $responseArray;
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            return array();
        }
    }
}