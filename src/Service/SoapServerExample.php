<?php

namespace App\Service;

use App\Entity\Property;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class SoapServerExample
{
    protected $wsdl;
    protected $ns;

    public function __construct(string $wsdl, string $ns)
    {
        $this->wsdl = $wsdl;
        $this->ns = $ns;
    }

    public function greet($name)
    {
        return "Hello, $name!";
    }

    public function handleRequest()
    {
        // Create a new instance of the SoapServer class
        $server = new \SoapServer($this->wsdl);

        // Register the functions with the SOAP server
        $server->addFunction('App\Service\greet');

        // Set the namespace for the SOAP server
        $server->setClass($this);

        // Handle the SOAP request
        $server->handle();
    }
}