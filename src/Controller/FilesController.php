<?php

namespace App\Controller;

use App\Helpers\FormatHtml\ConfigEmployeesHTML;
use App\Service\AddOnsApi;
use App\Service\EmployeeApi;
use App\Service\ReservationApi;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class FilesController extends AbstractController
{

    /**
     * @Route("api/files/{name}")
     */
    public function getFile($name, LoggerInterface $logger): BinaryFileResponse | JsonResponse
    {
        $logger->info("Starting Method: " . __METHOD__);
        $documentDir = __DIR__ . '/../../files/';
        try{
            $file = new BinaryFileResponse($documentDir . $name);
        }catch(Exception){
            $responseArray = array(
                'result_message' => "File not found",
                'result_code' => 1
            );
            return new JsonResponse($responseArray , 200, array());

        }
        return $file;
    }
}