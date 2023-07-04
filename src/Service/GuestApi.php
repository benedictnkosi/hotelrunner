<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\Property;
use App\Entity\Reservations;
use App\Entity\ReservationStatus;
use App\Helpers\SMSHelper;
use DateTime;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use phpDocumentor\Reflection\Types\Void_;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Guest;

class GuestApi
{
    private $em;
    private $logger;
    private $defectApi;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->defectApi = new DefectApi($entityManager, $logger);
        if (session_id() === '') {
            $logger->info("Session id is empty");
            session_start();
        }
    }

    public function createGuest($name, $phoneNumber, $email, $origin, $propertyId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $property = $this->em->getRepository(Property::class)->findOneBy(array('id' => $propertyId));
            $guest = new Guest();
            $guest->setName($name);
            $guest->setPhoneNumber($phoneNumber);
            $guest->setEmail($email);
            $guest->setProperty($property);
            $guest->setComments($origin);

            $this->em->persist($guest);
            $this->em->flush($guest);
            $responseArray[] = array(
                'result_code' => 0,
                'result_message' => 'Successfully created guest',
                'guest' => $guest
            );

        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateGuestPhoneNumber($guestId, $phoneNumber): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            if ($guest === null) {
                $responseArray = array(
                    'result_code' => 1,
                    'result_message' => 'Guest not found for id ' . $guestId
                );
            } else {
                $guest->setPhoneNumber($phoneNumber);
                $this->em->persist($guest);
                $this->em->flush($guest);
                $responseArray = array(
                    'result_code' => 0,
                    'result_message' => 'Successfully updated guest phone number'
                );
            }

        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateGuestName($guestId, $name): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            if ($guest === null) {
                $responseArray = array(
                    'result_code' => 1,
                    'result_message' => 'Guest not found for id ' . $guestId
                );
            } else {
                $guest->setName($name);
                $this->em->persist($guest);
                $this->em->flush($guest);
                $responseArray = array(
                    'result_code' => 0,
                    'result_message' => 'Successfully updated guest name'
                );
            }

        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateGuestEmail($guestId, $email): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            if ($guest === null) {
                $responseArray = array(
                    'result_code' => 1,
                    'result_message' => 'Guest not found for id ' . $guestId
                );
            } else {
                $guest->setEmail($email);
                $this->em->persist($guest);
                $this->em->flush($guest);
                $responseArray = array(
                    'result_code' => 0,
                    'result_message' => 'Successfully updated guest email address'
                );
            }

        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function updateGuestRewards($guestId, $flag): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            if ($guest === null) {
                $responseArray = array(
                    'result_code' => 1,
                    'result_message' => 'Guest not found for id ' . $guestId
                );
            } else {
                $guest->setRewards(intval($flag));
                $this->em->persist($guest);
                $this->em->flush($guest);
                $responseArray = array(
                    'result_code' => 0,
                    'result_message' => 'Successfully updated guest rewards flag'
                );
            }

        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    #[ArrayShape(['result_code' => "int", 'result_message' => "string"])]
    public function updateGuestIdNumber($guestId, $IdNumber): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            //check if ID not linked to a blocked guest
            $blockedGuest = $this->em->getRepository(Guest::class)->findOneBy(array('idNumber' => $IdNumber, 'state' => 'blocked'));
            if ($blockedGuest !== null) {
                return array(
                    'result_code' => 1,
                    'result_message' => 'This ID number was blocked for ' . $blockedGuest->getComments() . ". ID is linked to number " . $blockedGuest->getPhoneNumber()
                );
            }

            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            if ($guest === null) {
                return array(
                    'result_code' => 1,
                    'result_message' => 'Guest not found'
                );
            }

            //validate South african ID number
            if (is_numeric($IdNumber) && strlen($IdNumber) === 13) {
                $num_array = str_split($IdNumber);

                // Validate the day and month
                $currentYear = intval(date("Y"));

                $id_year = intval( $num_array[0] . $num_array[1]);
                //if id year is not older than 100 yrs and it is less than or equal to 99. add 19 infront. else i2000
                if($id_year <= 99){
                    $id_year = intval( "19" . $num_array[0] . $num_array[1]);
                    if($id_year <  $currentYear - 100){
                        $id_year = intval( "20" . $num_array[0] . $num_array[1]);
                    }
                }else{
                    $id_year = intval( "20" . $num_array[0] . $num_array[1]);
                }

                $guestAge = $currentYear - $id_year;
                $id_month = $num_array[2] . $num_array[3];

                $id_day = $num_array[4] . $num_array[5];

                //validate year
                if ($id_year > $currentYear) {
                    $this->logger->debug("id year is $id_year current year is $currentYear");

                    return array(
                        'result_code' => 1,
                        'result_message' => 'ID number is invalid'
                    );
                }

                //validate guest age from id
                if ($guestAge < 18) {
                    return array(
                        'result_code' => 1,
                        'result_message' => 'Guest is too young to stay with us'
                    );
                }


                if ($id_month < 1 || $id_month > 12) {
                    return array(
                        'result_code' => 1,
                        'result_message' => 'ID number is invalid'
                    );
                }

                if ($id_day < 1 || $id_day > 31) {
                    return array(
                        'result_code' => 1,
                        'result_message' => 'ID number is invalid'
                    );
                }

                // Validate gender
                $id_gender = $num_array[6] >= 5 ? 'male' : 'female';
                if ($guest->getGender() && strtolower($guest->getGender()) !== $id_gender && !$this->defectApi->isDefectEnabled("view_reservation_16")) {
                    return array(
                        'result_code' => 1,
                        'result_message' => 'ID number is invalid'
                    );
                }

                // citizenship as per id number
                $id_foreigner = $num_array[10];

                // citizenship as per submission
                if (($guest->getCitizenship() || $id_foreigner) && (int)$guest->getCitizenship() !== (int)$id_foreigner) {
                    return array(
                        'result_code' => 1,
                        'result_message' => 'ID number is invalid'
                    );
                }
            } else {
                return array(
                    'result_code' => 1,
                    'result_message' => 'ID number is invalid'
                );
            }

            $guest->setIdNumber($IdNumber);
            $this->em->persist($guest);
            $this->em->flush($guest);
            return array(
                'result_code' => 0,
                'result_message' => 'Successfully updated guest ID number'
            );

        } catch (Exception $ex) {
            return array(
                'result_code' => 1,
                'result_message' => $ex->getMessage(),
            );
        }
    }

    public function createAirbnbGuest($confirmationCode, $name): ?array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            //get property id
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('originUrl' => $confirmationCode));
            if ($reservation === null) {
                $this->logger->debug("Reservation not found");
                return null;
            }

            $property = $reservation->getRoom()->getProperty();

            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('name' => $name,
                'property' => $property->getId(),
                'comments' => 'airbnb'));

            if ($guest === null) {
                $guest = new Guest();
                $guest->setName($name);
                $guest->setComments('airbnb');
                $guest->setProperty($property);
                $this->em->persist($guest);
                $this->em->flush($guest);
            }

            $reservation->setGuest($guest);

            $this->em->persist($reservation);
            $this->em->flush($reservation);
            $responseArray = array(
                'result_code' => 0,
                'result_message' => 'Successfully updated reservation guest'
            );
        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function blockGuest($reservationId, $reason): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {
            $reservation = $this->em->getRepository(Reservations::class)->findOneBy(array('id' => $reservationId));
            if($reservation == null){
                return array(
                    'result_code' => 1,
                    'result_message' => "Reservation not found",
                );
            }
            $guest = $reservation->getGuest();
            $guest->setState("blocked");
            $guest->setComments($reason);
            $this->em->persist($guest);
            $this->em->flush($guest);
            $responseArray = array(
                'result_code' => 0,
                'result_message' => 'Successfully blocked guest'
            );
        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getGuest($filterValue): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            if (strlen($filterValue) == 12) {
                $guest = $this->em->getRepository(Guest::class)->findOneBy(array('phoneNumber' => trim($filterValue), 'state' => 'Active'));
                if ($guest == null) {
                    $guest = $this->em->getRepository(Guest::class)->findOneBy(array('phoneNumber' => str_replace("+27", "0", trim($filterValue))));
                }
            } else {
                $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $filterValue));
            }

            if ($guest === null) {
                $responseArray = array(
                    'result_message' => "Guest not found",
                    'result_code' => 1
                );
            } else {
                if ($this->defectApi->isDefectEnabled("create_reservation_6")) {
                    $guests = $this->em->getRepository(Guest::class)->findAll();
                    $guest = $guests[0];
                }

                $responseArray = array(
                    'id' => $guest->getId(),
                    'name' => $guest->getName(),
                    'image_id' => $guest->getIdImage(),
                    'phone_number' => $guest->getPhoneNumber(),
                    'email' => $guest->getEmail(),
                    'state' => $guest->getState(),
                    'comments' => $guest->getComments(),
                    'id_number' => $guest->getIdNumber(),
                    'stays_count' => $this->getGuestStaysCount($guest->getId()),
                    'nights_count' => $this->getGuestNightsCount($guest->getId()),
                    'result_code' => 0
                );
            }
        } catch (Exception $exception) {
            $responseArray = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function getGuestByPhoneNumber($phoneNumber, $request, $propertyId = null)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $guest = null;
        $responseArray = array();
        try {
            $propertyApi = new PropertyApi($this->em, $this->logger);
            if ($propertyId === null) {
                $propertyId = $propertyApi->getPropertyIdByHost($request);
            }
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('phoneNumber' => $phoneNumber, 'state' => 'Active'));
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $guest;
    }

    public function getGuestByName($name)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $guest = null;
        $responseArray = array();
        try {
            $propertyId = $_SESSION['PROPERTY_ID'];
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('name' => $name, 'property' => $propertyId, 'state' => 'Active'));
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $guest;
    }

    public function getGuestById($guestId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $guest = null;
        $responseArray = array();
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            if($guest == null){
                return array(
                    'result_message' => "Guest not found",
                    'result_code' => 1
                );
            }
        } catch (Exception $exception) {
            $this->logger->error(print_r($responseArray, true));
            return array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $guest;
    }

    function startsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        return substr($haystack, 0, $length) === $needle;
    }

    public function getGuestStaysCount($guestId): int
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $confirmStatus = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));

            $stays = $this->em->getRepository(Reservations::class)->findBy(array('guest' => $guestId,
                'status' => $confirmStatus));

            $numberOfStays = 0;
            $now = new DateTime('tomorrow midnight');
            foreach ($stays as $stay) {
                if ($stay->getCheckIn() < $now) {
                    $numberOfStays++;
                }
            }
            return $numberOfStays;
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return 0;
        }
    }

    public function getGuestNightsCount($guestId): int
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $confirmStatus = $this->em->getRepository(ReservationStatus::class)->findOneBy(array('name' => 'confirmed'));

            $stays = $this->em->getRepository(Reservations::class)->findBy(array('guest' => $guestId,
                'status' => $confirmStatus));

            $numberOfTotalNights = 0;
            $now = new DateTime('tomorrow midnight');
            foreach ($stays as $stay) {
                if ($stay->getCheckIn() < $now) {
                    $numberOfNights = intval($stay->getCheckIn()->diff($stay->getCheckOut())->format('%a'));
                    $numberOfTotalNights += $numberOfNights;
                }
            }
            return $numberOfTotalNights;
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return 0;
        }
    }

    public function getGuestPreviousRooms($guestId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $reservations = $this->em->getRepository(Reservations::class)->findBy(array('guest' => $guestId,
                'status' => 'confirmed'));
            foreach ($reservations as $item) {
                $responseArray[] = array(
                    'rooms' => $item->getRoom(),
                    'result_code' => 0
                );
            }
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function hasGuestStayedInRoom($guestId, $roomId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);

        $responseArray = array();
        try {
            $guestPreviousRooms = $this->getGuestPreviousRooms($guestId);
            foreach ($guestPreviousRooms as $room) {
                if ($room->getId() == $roomId) {
                    $responseArray[] = array(
                        'result_message' => true,
                        'result_code' => 0
                    );
                }
            }
        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function sendBookDirectSMS($guestId)
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $guest = null;
        $responseArray = array();
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId));
            //get room price
            $reservationApi = new ReservationApi($this->em, $this->logger);

            $reservation = $reservationApi->getReservationsByGuest($guestId);
            $roomPrice = $reservation->getRoom()->getPrice();

            $SMSHelper = new SMSHelper($this->logger);
            $message = "Aluve Guesthouse got your number :)  Book directly with us and pay only R$roomPrice per night. Book online aluvegh.co.za or call +27796347610.";
            $SMSHelper->sendMessage($guest->getPhoneNumber(), $message);

        } catch (Exception $exception) {
            $responseArray[] = array(
                'result_message' => $exception->getMessage(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $guest;
    }

    public function getConfigGuests($nameFilter): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        $responseArray = array();
        try {

            if (strcmp($nameFilter, "*") === 0) {
                $nameFilter = "";
            }

            return $this->em
                ->createQuery("SELECT g FROM App\Entity\Guest g 
            WHERE g.comments = 'website'
            and g.name like '%" . $nameFilter . "%'
            and g.state = 'Active'
            order by g.name asc")
                ->getResult();
        } catch (Exception $ex) {
            $responseArray[] = array(
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
                'result_code' => 1
            );
            $this->logger->error(print_r($responseArray, true));
        }
        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }

    public function removeGuest($guestId): array
    {
        $this->logger->debug("Starting Method: " . __METHOD__);
        try {
            $guest = $this->em->getRepository(Guest::class)->findOneBy(array('id' => $guestId, 'state' => 'Active'));
            if ($guest === null) {
                $responseArray = array(
                    'result_code' => 1,
                    'result_message' => 'Guest not found for id ' . $guestId
                );
            } else {
                $guest->setState("removed");
                $this->em->persist($guest);
                $this->em->flush($guest);
                $responseArray = array(
                    'result_code' => 0,
                    'result_message' => 'Successfully removed guest'
                );
            }

        } catch (Exception $ex) {
            $responseArray = array(
                'result_code' => 1,
                'result_message' => $ex->getMessage() . ' - ' . __METHOD__ . ':' . $ex->getLine() . ' ' . $ex->getTraceAsString(),
            );
            $this->logger->error(print_r($responseArray, true));
        }

        $this->logger->debug("Ending Method before the return: " . __METHOD__);
        return $responseArray;
    }


}