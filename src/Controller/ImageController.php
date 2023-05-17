<?php

namespace App\Controller;

use App\Service\FileUploaderApi;
use App\Service\RoomApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ImageController extends AbstractController
{

    /**
     * @Route("api/configuration/removeimage/{imageName}")
     */
    public function removeImage($imageName, LoggerInterface $logger,Request $request, EntityManagerInterface $entityManager, RoomApi $roomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('delete')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $result = $roomApi->removeImage($imageName);
        $callback = $request->get('callback');
        $response = new JsonResponse($result , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/configuration/markdefault/{imageName}")
     */
    public function markDefault($imageName, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, RoomApi $roomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('put')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $roomApi->markDefault($imageName);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 201, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("api/configuration/room/images/{roomId}")
     */
    public function getRoomImagesJson($roomId, LoggerInterface $logger, Request $request,EntityManagerInterface $entityManager, RoomApi $roomApi): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $response = $roomApi->getRoomImagesJson($roomId);
        $callback = $request->get('callback');
        $response = new JsonResponse($response , 200, array());
        $response->setCallback($callback);
        return $response;
    }

    /**
     * @Route("no_auth/room/image/{fileName}", name="getFile")
     */
    public function getFile($fileName, Request $request): Response
    {
        if (!$request->isMethod('get')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $filePath = __DIR__ . '/../../public/room/image/'. $fileName;
        if(file_exists($filePath)){
            return new BinaryFileResponse($filePath);
        }else{
            return new JsonResponse("file does not exist or is not readable" , 404, array());
        }
    }

    /**
     * @Route("api/configuration/image/upload")
     */
    public function uploadImage(LoggerInterface $logger, Request $request, FileUploaderApi $uploader): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return new JsonResponse("Internal server error" , 500, array());
        }
        $file = $request->files->get('file');
        if (empty($file))
        {
            $logger->info("No file specified");
            return new Response("No file specified",
                Response::HTTP_UNPROCESSABLE_ENTITY, ['content-type' => 'text/plain']);
        }

        $uploadDir = __DIR__ . '/../../public/room/image/';
        $uploader->setDir($uploadDir);
        $uploader->setExtensions(array('jpeg','png'));  //allowed extensions list//
        $uploader->setMaxSize(1);                          //set max file size to be allowed in MB//

        if(!$uploader->uploadFile()){
            //upload failed
            header("HTTP/1.1 500 Internal Server Error");
            return new Response($uploader->getMessage(),
                Response::HTTP_NOT_ACCEPTABLE, ['content-type' => 'text/plain']);
        }
        return new Response("File uploaded",  201,
            ['content-type' => 'text/plain']);
    }


}