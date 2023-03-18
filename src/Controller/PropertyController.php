<?php

namespace App\Controller;

use App\Service\PropertyApi;
use App\Service\RoomApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class PropertyController extends AbstractController
{
    /**
     * @Route("no_auth/property/terms")
     */
    public function getPropertyTerms( LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PropertyApi $propertyApi, RoomApi $roomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $propertyApi->getPropertyTerms($roomApi,  $request);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("admin_api/property/terms/update/{terms}")
     */
    public function updatePropertyTerms( $terms, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PropertyApi $propertyApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $propertyApi->updatePropertyTerms( $terms);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 201, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/property/propertyuid")
     */
    public function getPropertyUid( LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PropertyApi $propertyApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $propertyApi->getPropertyUid();
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("no_auth/property_details/{uid}")
     */
    public function getPropertyDetails($uid,  LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PropertyApi $propertyApi, RoomApi $roomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $propertyApi->getPropertyDetailsByUid($uid);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

}