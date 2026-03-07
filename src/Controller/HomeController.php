<?php

namespace App\Controller;

use App\Entity\Site;
use App\Repository\EntrepriseRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\Mapping\Id;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request ;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(EntrepriseRepository $entrepriseRep, SiteRepository $siteRep): Response
    {
        $entreprise = $entrepriseRep->findOneBy([]);
       
        if ($this->getUser()){
            if($this->getUser()->getTypeUser() == 'developpeur' ) { 
                $sites = $siteRep->findAll();
            } else {
                $sites = $this->getUser()->getSite();
            }
            
            return $this->render('accueil.html.twig', [
                'entreprise' => $entreprise,
                'sites' => $sites,
            ]);
        }
        return $this->render('base.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }
    #[Route('/logescom/home', name: 'app_logescom_home')]
    public function logescomHome(EntrepriseRepository $entrepriseRep, SiteRepository $siteRep): Response
    {
        $entreprise = $entrepriseRep->findOneBy([]);
        if ($this->getUser()){
            if($this->getUser()->getTypeUser() == 'developpeur' ) { 
                $sites = $siteRep->findAll();
            } else {
                $sites = $this->getUser()->getSite();
            }
           
            return $this->render('logescom/accueil.html.twig', [
                'entreprise' => $entreprise,
                'sites' => $sites,
            ]);
        }
        return $this->render('base.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/logescom/accueil/site/{site}', name: 'app_logescom_accueil_site')]
    public function logescomAccueilSite(Site $site): Response
    {       
        return $this->render('logescom/accueil_site.html.twig', [
            'entreprise' => $site->getEntreprise(),
            'site' => $site,
        ]);
    }

    #[Route('/logescom/home/categorie/site/{site}', name: 'app_logescom_home_service')]
    public function logescomHomeCategorie(Site $site): Response
    {
        
        return $this->render('logescom/accueil_service.html.twig', [
            'entreprise' => $site->getEntreprise(),
            'site' => $site
        ]);
    }

    #[Route('/logescom', name: 'app_logescom_accueil_sorties')]
    public function logescomAccueilVente(EntrepriseRepository $entrepriseRep, SiteRepository $siteRep): Response
    {
          
        return $this->render('logescom/accueil_sorties.html.twig', [
            'entreprise' => $entrepriseRep->findOneBy([]),
            'site' => $siteRep->findOneBy([]),
        ]);
    }
}
