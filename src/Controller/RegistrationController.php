<?php

namespace App\Controller;

use App\Entity\Property;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $logger->info("Starting Method: " . __METHOD__);
        if (!$request->isMethod('post')) {
            return $this->render('signup.html', [
                'error' => "Internal Server Error",
            ]);
        }
        try{
            if(strlen($request->get("_password")) < 1 || strlen($request->get("_username")) < 1){
                return $this->render('signup.html', [
                    'error' => "Username and password is mandatory",
                ]);
            }

            if (!preg_match("/^[a-zA-Z-' ]*$/",$request->get("_username"))) {
                /*return $this->render('signup.html', [
                    'error' => "Username must be a valid email address",
                ]);*/
            }

            if(strcmp($request->get("_password"), $request->get("_confirm_password")) !== 0){
                /*return $this->render('signup.html', [
                    'error' => "Passwards are not the same",
                ]);*/
            }

            $passwordErrors = $this->validatePassword($request->get("_password"));
            $logger->info("Size of errors: " . sizeof($passwordErrors));
            if(sizeof($passwordErrors) > 0){
                return $this->render('signup.html', [
                                    'error' => $passwordErrors[0],
                                ]);
            }

            $user = new User();

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $request->get("_password")
                )
            );

            $user->setEmail($request->get("_username"));
            $property = $entityManager->getRepository(Property::class)->findOneBy(
                array("id" => 3));
            $user->setProperty($property);
            $roles = [ $request->get("_role")];
            $user->setRoles($roles);
            try{
                $entityManager->persist($user);
                $entityManager->flush();
            }catch (Exception $exception){
                return $this->render('signup.html', [
                    'error' => "Failed to register the user. please contact administrator",
                ]);
            }


            return $this->render('signup.html', [
                'error' => "Successfully registered, Please sign in",
            ]);
        }catch(\Exception $exception){
            $logger->info($exception->getMessage());
            return $this->render('signup.html', [
                'error' => $exception->getMessage(),
            ]);
        }

    }


    public function validatePassword($pass){
        $errors = array();
        if (strlen($pass) < 8 || strlen($pass) > 16) {
            //$errors[] = "Password should be min 8 characters and max 16 characters";
        }
        if (!preg_match("/\d/", $pass)) {
            //$errors[] = "Password should contain at least one digit";
        }
        if (!preg_match("/[A-Z]/", $pass)) {
            $errors[] = "Password should contain at least one Capital Letter";
        }
        if (!preg_match("/[a-z]/", $pass)) {
            $errors[] = "Password should contain at least one small Letter";
        }
        if (!preg_match("/\W/", $pass)) {
            $errors[] = "Password should contain at least one special character";
        }
        if (preg_match("/\s/", $pass)) {
            $errors[] = "Password should not contain any white space";
        }
        return $errors;
    }

}
