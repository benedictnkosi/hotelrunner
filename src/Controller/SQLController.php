<?php

namespace App\Controller;

use App\Helpers\DatabaseHelper;
use App\Service\PropertyApi;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
require_once(__DIR__ . '/../app/application.php');

class SQLController extends AbstractController
{

    /**
     * @Route("api/sql")
     */
    function updateTable(Request $request, LoggerInterface $logger) {
        try{
            if (!$request->isMethod('put') && $request->get("soap_call") == null) {
                return new JsonResponse("Method Not Allowed" , 405, array());
            }
            $parameters = json_decode($request->getContent(), true);
            if($parameters == null){
                $response = array(
                    'result_code' => 1,
                    'result_message' => "Invalid body string",
                );
                return new JsonResponse($response , 200, array());
            }

            $sql = $parameters['sql'];
            $databaseHelper = new DatabaseHelper($logger);
            //  $sql = "UPDATE `reservations` SET `check_in` = '2023-06-26' WHERE `reservations`.`id` = 803;";
            $databaseHelper->execute($sql);
        }catch(Exception $exception){
            return new JsonResponse( "Failed to delete rooms: " . $exception->getMessage(), 500, array());
        }

        return new JsonResponse( "Success", 200, array());
    }

}