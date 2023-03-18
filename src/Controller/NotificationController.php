<?php

namespace App\Controller;

use App\Service\CleaningApi;
use App\Service\NotificationApi;
use App\Service\OccupancyApi;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class NotificationController  extends AbstractController
{
    /**
     * @Route("api/notifications")
     */
    public function getNotifications( LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, NotificationApi $notificationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $notifications = $notificationApi->getNotificationsToAction();
        $callback = $request->get('callback');
        $response = new JsonResponse($notifications , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("no_auth/notifications/ads/{propertyId}")
     */
    public function updateAdsNotification($propertyId, LoggerInterface $logger, Request $request, NotificationApi $notificationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $notificationApi->updateAdsNotificationAction("Pause Google Ads", true);
        $html = $notificationApi->updateAdsNotification($propertyId);
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 201, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/notifications/action/{name}")
     */
    public function updateNotificationAsActioned($name, LoggerInterface $logger, Request $request, NotificationApi $notificationApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $html = $notificationApi->updateAdsNotificationAction($name, true);
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 201, array());
        $response->setCallback($callback);
        return $response;
    }

}