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

class CommandsController extends AbstractController
{

    /**
     * @Route("no_auth/runcommand/clear")
     */
    public function clearSymfony(LoggerInterface $logger): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if(function_exists('exec')) {
            echo "exec is enabled";
        }else{
            echo "exec is not enabled";
        }

        $command = 'php ../bin/console doctrine:cache:clear-metadata';
        $result = $this->execute($command);
        $responseArray[] = array(
            'command' =>  $command,
            'result_message' => print_r($result, true),
            'result_code' => 0
        );

        $command = 'php ../bin/console doctrine:cache:clear-query';
        $result = $this->execute($command);
        $responseArray[] = array(
            'command' =>  $command,
            'result_message' => print_r($result, true),
            'result_code' => 0
        );

        $command = 'php ../bin/console doctrine:cache:clear-result';
        $result = $this->execute($command);
        $responseArray[] = array(
            'command' =>  $command,
            'result_message' => print_r($result, true),
            'result_code' => 0
        );
        return new JsonResponse( $responseArray, 200, array());
    }

    /**
     * @Route("no_auth/runcommand/downlaod/dependencies")
     */
    public function downloadDependencies(LoggerInterface $logger): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if(function_exists('exec')) {
            echo "exec is enabled";
        }else{
            echo "exec is not enabled";
        }

        $command = 'php composer.phar install';
        $result = $this->execute($command);
        $responseArray[] = array(
            'command' =>  $command,
            'result_message' => print_r($result, true),
            'result_code' => 0
        );
        return new JsonResponse( $responseArray, 200, array());
    }

    /**
     * @Route("no_auth/runcommand/phpmemory")
     */
    public function checkPHPMemory(LoggerInterface $logger): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $command = 'php -i | grep "memory_limit"';
        $result = $this->execute($command);
        $responseArray[] = array(
            'command' =>  $command,
            'result_message' => print_r($result, true),
            'result_code' => 0
        );

        return new JsonResponse( $responseArray, 200, array());
    }

    /**
     * @Route("no_auth/runcommand/gitversion")
     */
    public function gitVersion(LoggerInterface $logger): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $logger->info("Server name: " . $_SERVER['SERVER_NAME']);
        $command = 'git --version';
        $result = $this->execute($command);
        $responseArray[] = array(
            'command' =>  $command,
            'result_message' => print_r($result, true),
            'result_code' => 0
        );

        return new JsonResponse( $responseArray, 200, array());
    }

    /**
     * @Route("no_auth/runcommand/gitpull")
     */
    public function gitPull(LoggerInterface $logger): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        try{
            $command = 'git config --global user.email nkosi.benedict@gmail.com';
            $result = $this->execute($command);
            $responseArray[] = array(
                'command' =>  $command,
                'result_message_auto' => print_r($result, true),
                'result_code' => 0
            );

            $command = 'git config --global user.name nkosibenedict';
            $result = $this->execute($command);
            $responseArray[] = array(
                'command' =>  $command,
                'result_message_auto' => print_r($result, true),
                'result_code' => 0
            );


            $command = 'git stash';
            $result = $this->execute($command);
            $responseArray[] = array(
                'command' =>  $command,
                'result_message_auto' => print_r($result, true),
                'result_code' => 0
            );

            $command = 'git fetch --all';
            $result = $this->execute($command);
            $responseArray[] = array(
                'command' =>  $command,
                'result_message_auto' => print_r($result, true),
                'result_code' => 0
            );


            $server = $_SERVER['SERVER_NAME'];

            if(str_contains($server,"staging")){
                $command = 'git reset --hard origin/staging-environment';
            }elseif (str_contains($server,"ete")){
                $command = 'git reset --hard origin/ete-environment';
            }else{
                $command = 'git reset --hard origin/main';
            }


            $result = $this->execute($command);
            $responseArray[] = array(
                'command' =>  $command,
                'result_message_auto' => print_r($result, true),
                'result_code' => 0,
                'server' => $server
            );
            return new JsonResponse( $responseArray, 200, array());
        }catch(Exception $ex){
            $logger->error($ex->getMessage() .' - '. __METHOD__ . ':' . $ex->getLine() . ' ' .  $ex->getTraceAsString());
        }
        return new JsonResponse( $responseArray, 200, array());
    }

    /**
     * @Route("no_auth/runcommand/gitstash")
     */
    public function gitStash(LoggerInterface $logger): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $responseArray = array();
        $command = 'git stash';
        $result = $this->execute($command);
        $responseArray[] = array(
            'command' =>  $command,
            'result_message_auto' => print_r($result, true),
            'result_code' => 0
        );

        return new JsonResponse( $responseArray, 200, array());
    }


    /**
     * @Route("no_auth/runcommand/phpinfo")
     */
    public function phpinfo(LoggerInterface $logger): Response
    {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        ob_start();
        phpinfo();
        $str = ob_get_contents();
        ob_get_clean();

        return new Response($str);
    }

    /**
     * @Route("no_auth/runcommand/mysqldump")
     */
    public function mysql(LoggerInterface $logger): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        $command = 'mysql --version';
        $result = $this->execute($command);
        $responseArray[] = array(
            'command' =>  $command,
            'result_message' => print_r($result, true),
            'result_code' => 0
        );

        return new JsonResponse( $responseArray, 200, array());
    }

    /**
     * Executes a command and reurns an array with exit code, stdout and stderr content
     * @param string $cmd - Command to execute
     * @param string|null $workdir - Default working directory
     * @return string[] - Array with keys: 'code' - exit code, 'out' - stdout, 'err' - stderr
     */
    function execute($cmd, $workdir = null) {

        if (is_null($workdir)) {
            $workdir = __DIR__;
        }

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w"),  // stderr
        );

        $process = proc_open($cmd, $descriptorspec, $pipes, $workdir, null);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return [
            'code' => proc_close($process),
            'out' => trim($stdout),
            'err' => trim($stderr),
        ];
    }

    /**
     * @Route("no_auth/runcommand/deleteallrooms")
     */

    function cleanDatabase(LoggerInterface $logger) {
        try{
            $databaseHelper = new DatabaseHelper($logger);
            $sql = "TRUNCATE TABLE `reservation_notes`;";
            $databaseHelper->execute($sql);
            $sql = "TRUNCATE TABLE `reservation_add_ons`;";
            $databaseHelper->execute($sql);
            $sql = "TRUNCATE TABLE `payments`;";
            $databaseHelper->execute($sql);
            $sql = "TRUNCATE TABLE `cleaning`;";
            $databaseHelper->execute($sql);
            $sql = "delete FROM `reservations` where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete FROM `guest` where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from room_images where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from room_beds where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from blocked_rooms where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from ical where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from schedule_messages where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from rooms where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from add_ons where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from employee where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from message_template where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from schedule_messages where id > 0;";
            $databaseHelper->execute($sql);
            $sql = "delete from user where id > 0;";
            $databaseHelper->execute($sql);

            //delete the images
            $files = glob(__DIR__ . '/../../public/room/image/*'); // get all file names
            foreach($files as $file){ // iterate files
                if(is_file($file)) {
                    unlink($file); // delete file
                }
            }

            //delete the logs
            $files = glob(__DIR__ . '/../../var/log/*'); // get all file names
            foreach($files as $file){ // iterate files
                if(is_file($file)) {
                    unlink($file); // delete file
                }
            }
        }catch(Exception $exception){
            return new JsonResponse( "Failed to delete rooms: " . $exception->getMessage(), 500, array());
        }

        return new JsonResponse( "Success", 200, array());
    }

}