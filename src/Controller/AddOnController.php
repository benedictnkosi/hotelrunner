<?php

namespace App\Controller;

use App\Helpers\FormatHtml\ConfigAddonsHTML;
use App\Service\AddOnsApi;
use App\Service\GuestApi;
use App\Service\PaymentApi;
use App\Service\SecurityApi;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class AddOnController extends AbstractController
{
    /**
     * @Route("api/addon/{addonid}/reservation/{reservationId}/quantity/{quantity}")
     */
    public function addAdOnToReservation($addonid, $reservationId, $quantity, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $addOnsApi->addAdOnToReservation($reservationId,$addonid, $quantity);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/addons/")
     */
    public function getConfigAddOns(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $addOns = $addOnsApi->getAddOns();
        $configAddonsHTML = new ConfigAddonsHTML( $entityManager, $logger);
        $formattedHtml = $configAddonsHTML->formatHtml($addOns);
        $callback = $request->get('callback');
        $response = new JsonResponse($formattedHtml , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/addon/{addOnId}")
     */
    public function getAddOn($addOnId, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $addOns = $addOnsApi->getAddOnsJson($addOnId);
        $callback = $request->get('callback');
        $response = new JsonResponse($addOns , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("admin_api/createaddon")
     */
    public function createAddon(Request $request,LoggerInterface $logger, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $addOnsApi->createAddOn($request->get('name'), $request->get('price'));
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("admin_api/addon/delete/{addOnId}")
     */
    public function deleteAddOn($addOnId, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('remove')) {
            return new JsonResponse("Internal server error" , 500, array());
        }

        $response = $addOnsApi->deleteAddOn($addOnId);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("admin_api/addon/update/{addOnId}/{field}/{newValue}")
     */
    public function updateAddOn($addOnId, $field, $newValue, Request $request,LoggerInterface $logger, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $addOnsApi->updateAddOn($addOnId, $field, $newValue);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/json/addOn/{name}")
     */
    public function getAddOnJson( $name, Request $request,LoggerInterface $logger, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $addOn = $addOnsApi->getAddOn($name);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($addOn, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }


}