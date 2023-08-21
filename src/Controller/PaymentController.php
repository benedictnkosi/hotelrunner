<?php

namespace App\Controller;

use App\Service\EmployeeApi;
use App\Service\PaymentApi;
use App\Service\ReservationApi;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class PaymentController extends AbstractController
{
    /**
     * @Route("api/payment/add")
     */
    public function addPayment(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $response = $paymentApi->addPayment($request->get('id'), $request->get('amount'), str_replace("_","/",$request->get('reference')), $request->get('channel'));
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 201, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/payment/json/add")
     */
    public function addPaymentJson(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
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
        $response = $paymentApi->addPayment($parameters['reservation_id'], $parameters['amount'], str_replace("_","/",$parameters['reference']), $parameters['channel']);
        if ($response[0]['result_code'] === 0) {
            $response = new JsonResponse($response , 201, array());
        }else{
            $response = new JsonResponse($response , 200, array());
        }

        return $response;
    }

    /**
     * @Route("admin_api/payment/{paymentId}/delete")
     */
    public function removePayment($paymentId, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('delete')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $paymentApi->removePayment($paymentId);
        if ($response['result_code'] === 0) {
            $response = new JsonResponse($response , 204, array());
        }else{
            $response = new JsonResponse($response , 200, array());
        }
        return $response;
    }

    /**
     * @Route("api/discount/add")
     */
    public function addDiscount(LoggerInterface $logger, Request $request, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);

        if (!$request->isMethod('post')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $response = $paymentApi->addDiscount($request->get('id'), $request->get('amount'), "discount");
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 201, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/json/discount/add")
     */
    public function addDiscountJson(LoggerInterface $logger, Request $request, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);

        if (!$request->isMethod('post')) {
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
        $response = $paymentApi->addDiscount($parameters['id'], $parameters['amount'], "discount");
        return new JsonResponse($response , 201, array());
    }

    /**
     * @Route("no_auth/payfast_notify")
     * @throws \Exception
     */
    public function payfast_notify(Request $request, LoggerInterface $logger, EntityManagerInterface $entityManager, ReservationApi $reservationApi, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $logger->info("reservation ID: " . $request->get('item_description'));
        $logger->info("amount paid: " . $request->get('amount_gross'));
        $reservationId = $request->get('item_description');
        $amount = $request->get('amount_gross');

        $response = $paymentApi->addPayment($reservationId, $amount, "payfast", "payfast");
        $callback = $request->get('callback');
        $response = new JsonResponse($response, 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("api/payment/total/cash/{startDate}/{endDate}/{channel}")
     */
    public function getTotalCashPayment($startDate, $endDate, $channel, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $paymentApi->getCashReport($startDate, $endDate, $channel);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/payment/total/cashtransactions/{startDate}/{endDate}/{channel}/{isGroup}")
     */
    public function getTotalCashPaymentByDay($startDate, $endDate,$channel, $isGroup, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        if (strcmp($isGroup, "true") === 0) {
            $response = $paymentApi->getCashReportByDay($startDate, $endDate, $channel);
        }else{
            $response = $paymentApi->getCashReportAllTransactions($startDate, $endDate, $channel);
        }

        $response = array(
            'html' => $response,
        );

        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/json/payment/total/transactions")
     */
    public function getTotalCashPaymentByDayJson(LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, PaymentApi $paymentApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);

        $parameters = json_decode($request->getContent(), true);
        if($parameters == null){
            $response = array(
                'result_code' => 1,
                'result_message' => "Invalid body string",
            );
            return new JsonResponse($response , 200, array());
        }

        $logger->info("soap call: " . $parameters['soap_call']);

        if (!$request->isMethod('get') && $parameters['soap_call'] == null) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        if (strcmp($parameters['group'], "true") == 0) {
            $response = $paymentApi->getCashReportByDayJson($parameters['start_date'], $parameters['end_date'], $parameters['channel']);
        }else if(strcmp($parameters['group'], "false") == 0) {
            $response = $paymentApi->getCashReportAllTransactionsJson($parameters['start_date'], $parameters['end_date'], $parameters['channel']);
        }else{
            $response = array(
                'result_code' => 1,
                'result_message' => "Invalid group parameter",
            );
        }

        return new JsonResponse($response , 200, array());
    }

    /**
     * @Route("api/json/payment/{id}")
     */
    public function getPaymentJson( $id, LoggerInterface $logger, Request $request,PaymentApi $api): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $payment = $api->getPayment($id);

        if($payment == null){
            $payment = array(
                'result_code' => 1,
                'result_message' => 'Payment not found'
            );
        }
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($payment, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }


    /**
     * @Route("api/json/reservations/{id}/payments")
     */
    public function getReservationPayments( $id, LoggerInterface $logger, Request $request,PaymentApi $api): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $payment = $api->getReservationPayments($id);

        if($payment == null){
            $payment[] = array(
                'result_code' => 1,
                'result_message' => 'Payment not found'
            );
        }
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($payment, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
    }
}