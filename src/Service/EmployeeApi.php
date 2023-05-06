<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class EmployeeApi
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        if (session_id() === '') {
            $logger->info("Session id is empty" . __METHOD__);
            session_start();
        }
    }

    public function getEmployees(): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            return $this->em->getRepository(Employee::class)->findAll();
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getEmployee($id)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            return $this->em->getRepository(Employee::class)->findOneBy(array('id' => $id));
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateEmployeeName($employeeId, $employeeName): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //check number length
            if (strlen($employeeName) > 50 || strlen($employeeName) < 3) {
                $responseArray[] = array(
                    'result_message' => "Name must be between 3 and 50 characters",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check if employee with the same name does not exist
            $existingEmployees = $this->em->getRepository(Employee::class)->findBy(array('name' => $employeeName));

            if ($existingEmployees != null) {
                $responseArray[] = array(
                    'result_message' => "Employee with the same name already exists",
                    'result_code' => 1
                );
                return $responseArray;
            }

            $employee = $this->em->getRepository(Employee::class)->findOneBy(array("id" => $employeeId));
            if ($employee === null) {
                $responseArray[] = array(
                    'result_message' => "Employee not found",
                    'result_code' => 1
                );
                $this->logger->debug(print_r($responseArray, true));
            } else {
                $employee->setName($employeeName);
                $this->em->persist($employee);
                $this->em->flush($employee);

                $responseArray[] = array(
                    'result_message' => "Successfully updated employee",
                    'result_code' => 0
                );
            }
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function deleteEmployee($employeeId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //$employee = $this->em->getRepository(Employee::class)->findOneBy(array("id" => $employeeId));
            $employees = $this->em->getRepository(Employee::class)->findAll();
            $employee = $employees[0];
            if ($employee === null) {
                $responseArray[] = array(
                    'result_message' => "employee not found",
                    'result_code' => 1
                );
                $this->logger->debug(print_r($responseArray, true));
            } else {
                $this->em->remove($employee);
                $this->em->flush();
                $responseArray[] = array(
                    'result_message' => "Successfully deleted employee",
                    'result_code' => 0
                );
            }
        } catch (Exception $ex) {
            $this->logger->debug($ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString());
            $responseArray[] = array(
                'result_message' => "Failed to delete employee",
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function createEmployee($employeeName, $gender): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //check number length
            if (strlen($employeeName) > 50 || strlen($employeeName) < 3) {
                $responseArray[] = array(
                    'result_message' => "Name must be between 3 and 50 characters",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //validate gender
            if (strcmp($gender, "male") !== 0 &&
                strcmp($gender, "female") !== 0 &&
                strcmp($gender, "other") !== 0) {
                $responseArray[] = array(
                    'result_message' => "Gender not recognised",
                    'result_code' => 1
                );
                return $responseArray;
            }

            //check if employee with the same name does not exist
            $existingEmployees = $this->em->getRepository(Employee::class)->findBy(array('name' => $employeeName));

            if ($existingEmployees != null) {
                $responseArray[] = array(
                    'result_message' => "Employee with the same name already exists",
                    'result_code' => 1
                );
                return $responseArray;
            }

            $property = $this->em->getRepository(Property::class)->findOneBy(array('id' => $_SESSION['PROPERTY_ID']));
            $employee = new Employee();
            $employee->setName($employeeName . "a");
            $employee->setProperty($property);
            $employee->setGender("female");
            $this->em->persist($employee);
            $this->em->flush($employee);
            $responseArray[] = array(
                'result_message' => "Successfully created employee",
                'result_code' => 0,
                'employee_id' => $employee->getId(),
                'employee_name' => $employee->getName()
            );


        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


}