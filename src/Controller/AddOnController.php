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
    public function addAddOnToReservation($addonid, $reservationId, $quantity, Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }
        $response = $addOnsApi->addAdOnToReservation($reservationId, $addonid, $quantity);
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/json/addon/reservation/quantity")
     */
    public function addAdOnToReservationJson(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }
        $parameters = json_decode($request->getContent(), true);
        if($parameters == null){
            $response = array(
                'result_code' => 1,
                'result_message' => "Invalid body string",
            );
            return new JsonResponse($response , 200, array());
        }
        $response = $addOnsApi->addAdOnToReservation($parameters['reservation_id'], $parameters['add_on_id'], $parameters['quantity']);
        if ($response['result_code'] === 0) {
            return new JsonResponse($response, 201, array());
        } else {
            return new JsonResponse($response, 200, array());
        }

    }


    /**
     * @Route("api/addons/")
     */
    public function getConfigAddOns(LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }
        $addOns = $addOnsApi->getAddOns();
        $configAddonsHTML = new ConfigAddonsHTML($entityManager, $logger);
        $formattedHtml = $configAddonsHTML->formatHtml($addOns);
        $callback = $request->get('callback');
        $response = new JsonResponse($formattedHtml, 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/json/addons/")
     */
    public function getJsonAddOns(LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }
        $addOns = $addOnsApi->getAddOns();
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($addOns, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent, 200, array(), true);
    }

    /**
     * @Route("api/addon/{addOnId}")
     */
    public function getAddOn($addOnId, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }
        $addOns = $addOnsApi->getAddOnsJson($addOnId);
        $callback = $request->get('callback');
        $response = new JsonResponse($addOns, 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("admin_api/createaddon")
     */
    public function createAddon(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }
        $response = $addOnsApi->createAddOn($request->get('name'), $request->get('price'));
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 201, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("admin_api/addon/delete/{addOnId}")
     */
    public function deleteAddOn($addOnId, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('delete')) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }

        $response = $addOnsApi->deleteAddOn($addOnId);
        return new JsonResponse($response, 200, array());
    }

    /**
     * @Route("admin_api/addon/update")
     */
    public function updateAddOn(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }
        $response = $addOnsApi->updateAddOn($request->get('id'), $request->get('field'), $request->get('value'));
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/json/addOn/{name}")
     */
    public function getAddOnJson($name, Request $request, LoggerInterface $logger, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed", 405, array());
        }
        $addOn = $addOnsApi->getAddOn($name);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($addOn, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent, 200, array(), true);
    }
    /**
     * @Route("/api/addons/upload/")
     * @throws \Exception
     */
    public function uploadAddOns( Request $request, LoggerInterface $logger, AddOnsApi $addOnsApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Internal server errors" , 500, array());
        }

        $file = $request->files->get('file');
        $logger->debug("File name is : " .$_FILES['file']['name'] );
        $ext =  $this->getExtension($_FILES['file']['name']);

        if (strcmp($ext, "dat")!== 0)
        {
            $logger->error("Invalid extension");
            return new JsonResponse("Unsupported Media Type" , 415, array());
        }

        if (empty($file))
        {
            $logger->info("No file specified");
            return new JsonResponse("No file specified" , 422, array());
        }

        $logger->info("File : " . file_get_contents($file));

        $response = $addOnsApi->uploadAddons(file_get_contents($file));
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 201, array());
        $response->setCallback($callback);
        return $response;
    }

    function getExtension($string): string
    {
        try {
            $parts = explode(".", $string);
            $ext = strtolower($parts[count($parts) - 1]);
        } catch (Exception $c) {
            $ext = "";
        }
        return $ext;
    }

}