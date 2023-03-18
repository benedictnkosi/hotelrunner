<?php

namespace App\Controller;

use App\Service\NotesApi;
use App\Service\OccupancyApi;
use App\Service\StatsApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class OccupancyController extends AbstractController
{
    /**
     * @Route("api/occupancy/{days}")
     */
    public function getOccupancy($days,  LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, StatsApi $statsApi, OccupancyApi $occupancyApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $occupancyApi->getOccupancy($days);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/occupancy/perroom/{days}")
     */
    public function getOccupancyPerRoom($days,  LoggerInterface $logger,Request $request, EntityManagerInterface $entityManager, OccupancyApi $occupancyApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $occupancyApi->getOccupancyPerRoom($days);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

}