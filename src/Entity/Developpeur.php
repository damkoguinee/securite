<?php

namespace App\Entity;

use App\Repository\DeveloppeurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeveloppeurRepository::class)]
class Developpeur extends User
{
    
}
