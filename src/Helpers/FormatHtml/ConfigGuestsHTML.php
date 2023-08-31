<?php

namespace App\Helpers\FormatHtml;

use App\Service\GuestApi;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Isset_;
use Psr\Log\LoggerInterface;

class ConfigGuestsHTML
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    public function formatHtml($guests): string
    {
        $guestApi = new GuestApi($this->em,$this->logger );
        $html = '';
        if ($guests != null) {
            $html = "<tr><th>Guest Name</th><th>Phone Number</th><th>Email Address</th><th>Stays</th><th>Total Nights</th><th>Rewards</th><th>Delete</th></tr>";
            foreach ($guests as $guest) {
                $numberOfStays = $guestApi->getGuestStaysCount($guest->getId());
                $numberOfNights = $guestApi->getGuestNightsCount($guest->getId());

                $rewards = '';
                if ($guest->isRewards()) {
                    $rewards = 'checked';
                }

                $html .= '<tr>
<td><input type="text" class="guest_field" data-guest-id="' . $guest->getId() . '" data-guest-field="name" value="' . $guest->getName() . '"
                                   required/></td>

                                   <td><input type="text" class="guest_field" data-guest-id="' . $guest->getId() . '" data-guest-field="phoneNumber" value="' . $guest->getPhoneNumber() . '"
                                   required/></td>
                                   
<td><input type="text" class="guest_field" data-guest-id="' . $guest->getId() . '" data-guest-field="email" value="' . $guest->getEmail() . '"
                                   required/></td>
                                   
                                   <td>'.$numberOfStays.'</td>
                                   
                                   <td>'.$numberOfNights.'</td>
                                   
                                   <td><input type="checkbox" id="rewards_' . $guest->getId() . '"  class="guest_field" data-guest-id="' . $guest->getId() . '" data-guest-field="rewards" name="rewards" value="Rewards" ' . $rewards . '>
                             </td>
                             
                                   <td><div class="ClickableButton remove_guest_button" data-guest-id="' . $guest->getId() . '" >Remove</div></td>
                                   
                                   </tr>';

            }
        } else {
            $html .= '<h5>No Guests found</h5>';
        }

        return $html;
    }
}