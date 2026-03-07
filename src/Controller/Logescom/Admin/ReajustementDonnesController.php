<?php

namespace App\Controller\Logescom\Admin;


use App\Entity\CommandeProduct;
use App\Repository\CaisseRepository;
use App\Repository\CommandeProductRepository;
use App\Repository\DecaissementRepository;
use App\Repository\DepensesRepository;
use App\Repository\UserRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\FacturationRepository;
use App\Repository\ModePaiementRepository;
use App\Repository\MouvementCaisseRepository;
use App\Repository\MouvementCollaborateurRepository;
use App\Repository\MouvementProductRepository;
use App\Repository\ProductsRepository;
use App\Repository\StockRepository;
use App\Repository\VersementRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/reajustement/donnees')]
class ReajustementDonnesController extends AbstractController
{
    

    #[Route('/maj/accent', name: 'app_logescom_admin_reajustement_donnees_maj_accent')]
    public function MajAccentCollaborateur(UserRepository $userRep, EntityManagerInterface $em): Response
    {       
        $maj_client = $userRep->findAll();
        foreach ($maj_client as $client) {
            $client->setNom(str_replace('&eacute;', 'é', $client->getNom()));
            $client->setNom(str_replace('&Eacute;', 'é', $client->getNom()));
            $client->setNom(str_replace('&iuml;', 'ï', $client->getNom()));
            $client->setNom(str_replace('&agrave;', 'à', $client->getNom()));
            $client->setNom(str_replace('&ecirc;', 'ê', $client->getNom()));
            $client->setNom(str_replace('&ccedil;', 'ç', $client->getNom()));
            $client->setNom(str_replace('&ocirc;', 'ô', $client->getNom()));
            $client->setNom(str_replace('&times;', 'x', $client->getNom()));
            $client->setNom(str_replace('&euml;', 'ë', $client->getNom()));


            $client->setPrenom(str_replace('&eacute;', 'é', $client->getPrenom()));
            $client->setPrenom(str_replace('&Eacute;', 'é', $client->getPrenom()));
            $client->setNom(str_replace('&iuml;', 'ï', $client->getPrenom()));
            $client->setPrenom(str_replace('&agrave;', 'à', $client->getPrenom()));
            $client->setPrenom(str_replace('&ecirc;', 'ê', $client->getPrenom()));
            $client->setPrenom(str_replace('&ccedil;', 'ç', $client->getPrenom()));
            $client->setPrenom(str_replace('&ocirc;', 'ô', $client->getPrenom()));
            $client->setPrenom(str_replace('&times;', 'x', $client->getPrenom()));
            $client->setPrenom(str_replace('&euml;', 'ë', $client->getPrenom()));
            $em->persist($client);
        }
        $em->flush();

        return $this->redirectToRoute('app_logescom_home', [], Response::HTTP_SEE_OTHER);

    }

   
   
    
}
