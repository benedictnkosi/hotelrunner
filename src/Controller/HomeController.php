<?php

namespace App\Controller;


use App\Entity\Functionality;
use App\Service\DefectApi;
use App\Service\GuestApi;
use App\Service\PropertyApi;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HomeController extends AbstractController
{

    /**
     * @Route("/admin/", name="app_admin")
     */
    public function app_admin(LoggerInterface $logger): Response
    {
        if($this->getUser() !== null){
            $logger->info("Session: " . print_r($_SESSION, true));
            $logger->info("user roles: " . print_r($this->getUser()->getRoles(), true));
            $logger->info("property name is: " . $this->getUser()->getProperty()->getId());
            $_SESSION["PROPERTY_ID"] = $this->getUser()->getProperty()->getId();
            $logger->info("new session: " . print_r($_SESSION, true));
            return $this->render('admin.html');
        }else{
            return $this->redirectToRoute("index");
        }
    }


    /**
     * @Route("/portal/", name="portal")
     */
    public function portal(LoggerInterface $logger): Response
    {
        if($this->getUser() !== null){
            $logger->info("Session: " . print_r($_SESSION, true));
            $logger->info("user roles: " . print_r($this->getUser()->getRoles(), true));
            $logger->info("property name is: " . $this->getUser()->getProperty()->getId());
            $_SESSION["PROPERTY_ID"] = $this->getUser()->getProperty()->getId();
            $logger->info("new session: " . print_r($_SESSION, true));
            return $this->render('admin.html');
        }else{
            return $this->redirectToRoute("index");
        }
    }


    /**
     * @Route("/index.html", name="index")
     */
    public function index(): Response
    {
        return $this->render("index.html");
    }

    /**
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        return $this->render("index.html");
    }

    #[Route('/signup', name: 'app_signup')]
    public function signup(): Response

    {
        return $this->render('signup.html', [
            'error' => "",
        ]);

    }

    #[Route('/booking', name: 'booking')]
    public function booking(): Response
    {

        return $this->render("booking.html");
    }


    #[Route('/confirmation', name: 'confirmation')]
    public function confirmation(): Response
    {
        return $this->render("confirmation.html");
    }

    #[Route('/cancelled', name: 'payment_cancelled')]
    public function payment_cancelled(): Response
    {
        return $this->render("cancelled.html");
    }

    #[Route('/thankyou', name: 'thank_you_for_payment')]
    public function thank_you_for_payment(): Response
    {
        return $this->render("thankyou.html");
    }

    #[Route('/invoice', name: 'invoice')]
    public function invoice(): Response
    {
        return $this->render("invoice.html");
    }

    #[Route('/room', name: 'room')]
    public function room(): Response
    {
        return $this->render("room.html");
    }

    #[Route('/manage_functionality038a753a-08e3-11ee-be56-0242ac120002', name: 'manage_functionality')]
    public function manage_functionality(): Response
    {
        return $this->render("manage_functionality038a753a-08e3-11ee-be56-0242ac120002.html");
    }


    /**
     * @Route("no_auth/userloggedin")
     */
    public function isUserLoggedIn(LoggerInterface $logger, Request $request, GuestApi $guestApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__ );
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        if(isset($_SESSION["PROPERTY_ID"])){
            $response = array("logged_in" => "true");
        }else{
            $response = array("logged_in" => "false");
        }
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("no_auth/getEnabledFuntionality")
     */
    public function getEnabledFunctionality(LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager): Response
    {
        $logger->info("Starting Method: " . __METHOD__ );
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $enabledFunctionality = $entityManager->getRepository(Functionality::class)->findBy(array('enabled' => true, 'type'=>'menu'));
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($enabledFunctionality, 'json');

        $logger->info($jsonContent);
        return new JsonResponse($jsonContent , 200, array(), true);
        return $response;
    }

    /**
     * @Route("no_auth/getFunctionality")
     */
    public function getFunctionality(LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, DefectApi $defectApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__ );
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $html = $defectApi->getFunctionality();

        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }


    /**
     * @Route("no_auth/getDefects")
     */
    public function getDefects(LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, DefectApi $defectApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__ );
        if (!$request->isMethod('get')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }

        $html = $defectApi->getDefects();

        $response = array(
            'html' => $html,
        );
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("no_auth/defect/update/{id}/{enabled}")
     */
    public function updateDefect($id, $enabled, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, DefectApi $defectApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__ );
        if (!$request->isMethod('put')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $defectApi->updateDefectEnabled($id, $enabled);
        return new JsonResponse($response , 200, array());
    }

    /**
     * @Route("no_auth/functionality/update/{id}/{enabled}")
     */
    public function updateFunctionality($id, $enabled, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager, DefectApi $defectApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__ );
        if (!$request->isMethod('put')) {
            return new JsonResponse("Method Not Allowed" , 405, array());
        }
        $response = $defectApi->updateFunctionalityEnabled($id, $enabled);
        return new JsonResponse($response , 200, array());
    }


}