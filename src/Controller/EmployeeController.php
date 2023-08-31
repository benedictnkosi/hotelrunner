<?php

namespace App\Controller;

use App\Helpers\FormatHtml\ConfigEmployeesHTML;
use App\Service\AddOnsApi;
use App\Service\EmployeeApi;
use App\Service\ReservationApi;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class EmployeeController extends AbstractController
{

    /**
     * @Route("api/config/employees")
     */
    public function getConfigEmployees( LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, EmployeeApi $employeeApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $employees = $employeeApi->getEmployees();
        $configEmployeesHTML = new ConfigEmployeesHTML( $entityManager, $logger);
        $html = $configEmployeesHTML->formatHtml($employees);
        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/config/json/employees")
     */
    public function getJsonEmployees( LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, EmployeeApi $employeeApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $employees = $employeeApi->getEmployees();
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($employees, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }

    /**
     * @Route("admin_api/createemployee")
     */
    public function createEmployee(LoggerInterface $logger, Request $request,EmployeeApi $employeeApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $response = $employeeApi->createEmployee($request->get('name'), $request->get('gender'));
        if ($response[0]['result_code'] === 0) {
            $response = new JsonResponse($response , 201, array());
        }else{
            $response = new JsonResponse($response , 200, array());
        }
        return $response;
    }

    /**
     * @Route("admin_api/employee/delete/{employeeId}")
     */
    public function deleteEmployee($employeeId, LoggerInterface $logger, Request $request,EmployeeApi $employeeApi): Response
    {
        $logger->info("Starting Method: " . $request->getMethod());
        if (!$request->isMethod('delete')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $employeeApi->deleteEmployee($employeeId);
        if ($response[0]['result_code'] === 0) {
            $response = new JsonResponse($response , 204, array());
        }else{
            $response = new JsonResponse($response , 200, array());
        }
        return $response;
    }

    /**
     * @Route("admin_api/employee/update/{employeeId}/{name}")
     */
    public function updateEmployees($employeeId, $name, LoggerInterface $logger,Request $request, EntityManagerInterface $entityManager, EmployeeApi $employeeApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put') && $request->get("soap_call") == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $employeeApi->updateEmployeeName($employeeId, $name);
        return new JsonResponse($response , 200, array());
    }

    /**
     * @Route("api/json/employee/{id}")
     */
    public function getEmployeeJson( $id, LoggerInterface $logger, Request $request,EmployeeApi $employeeApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $employee = $employeeApi->getEmployee($id);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($employee, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }
}