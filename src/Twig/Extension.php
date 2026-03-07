<?php
namespace App\Twig;

use App\Entity\User;
use App\Service\NumberToWordsService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class Extension extends AbstractExtension
{
    private $parametres;
    private $numberToWords;

    public function __construct(ParameterBagInterface $parametres, NumberToWordsService $numberToWords)
    {
        $this->parametres = $parametres;
        $this->numberToWords = $numberToWords;
    }

    public function extrait(string $texte = null, int $longeueur)
    {
        return strlen($texte) > $longeueur ? substr($texte, 0, $longeueur) . "[...]" : $texte;
    }

    public function estNumerique($variable): bool
    {
        return is_numeric($variable);
    }

    public function roles(User $user): string
    {
        $text ="";
        foreach ($user->getRoles() as $role) {
            $text .= $text ? ", " : "";
            switch ($role) {
                case 'ROLE_ADMIN': $text .= "Administrateur"; break;
                case 'ROLE_VENDEUR': $text .= "Vendeur"; break;
                case 'ROLE_LOGISTIQUE': $text .= "Logistique"; break;
                case 'ROLE_COMPTABLE': $text .= "Comptable"; break;
                case 'ROLE_COMMUNICATION': $text .= "Communication"; break;
                case 'ROLE_RH': $text .= "RH"; break;
                case 'ROLE_STOCK': $text .= "Stock"; break;
                case 'ROLE_GESTIONNAIRE': $text .= "Gestionnaire"; break;
                case 'ROLE_DIRECTION': $text .= "Direction"; break;
                case 'ROLE_RESPONSABLE': $text .= "Responsable"; break;
                case 'ROLE_ACTIONNAIRE': $text .= "Actionnaire"; break;
                case 'ROLE_DEVELOPPEUR': $text .= "Dévéloppeur"; break;
                case 'ROLE_SUPPRESSION': $text .= "Suppression"; break;
                case 'ROLE_MODIFICATION': $text .= "Modification"; break;
                default: $text .= ""; break;
            }
        }
        return $text;
    }

    public function baliseImg($imageName, $classes = "", $alt ="")
    {
        return "<img src='".$this->parametres->get("chemin_images")."$imageName' class='$classes' alt='$alt'>";
    }

    public function strtoupperFilter($value)
    {
        return strtoupper($value);
    }

    public function strtolowerFilter($value)
    {
        return strtolower($value);
    }

    public function ucwordsFilter($value)
    {
        return ucwords($value);
    }

    public function ucfirstFilter($value)
    {
        return ucfirst($value);
    }

    public function calculateAgeFilter(\DateTime $birthDate)
    {
        $currentDate = new \DateTime();
        $ageInterval = $currentDate->diff($birthDate);

        return [
            'age' => $ageInterval->y,
            'isBaby' => $ageInterval->m < 12,
            'ageInMonths' => $ageInterval->m,
            'ageInWeeks' => $ageInterval->m * 4 + $ageInterval->d / 7,
        ];
    }

    public function nomJour(int $numero): string
    {
        $jours = [
            1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
            5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
        ];

        return $jours[$numero] ?? 'Inconnu';
    }

    /**
     * Enregistrer les fonctions Twig
     */
    public function getFunctions()
    {
        return [
            new TwigFunction("extrait", [$this, "extrait"])
        ];
    }

    /**
     * Enregistrer les filtres Twig
     */
    public function getFilters()
    {
        return [
            new TwigFilter("extrait", [$this, "extrait"]),
            new TwigFilter("autorisations", [$this, "roles"]),
            new TwigFilter("img", [$this, "baliseImg"]),
            new TwigFilter("strtoupper", [$this, "strtoupperFilter"]),
            new TwigFilter("ucwords", [$this, "ucwordsFilter"]),
            new TwigFilter("ucfirst", [$this, "ucfirstFilter"]),
            new TwigFilter("strtolower", [$this, "strtolowerFilter"]),
            new TwigFilter("calculateAge", [$this, "calculateAgeFilter"]),
            new TwigFilter("nom_jour", [$this, "nomJour"]),

            // ⭐ AJOUT DU FILTRE EN LETTRES ⭐
            new TwigFilter("en_lettres", [$this, "toWords"]),
        ];
    }

    /**
     * Test Twig
     */
    public function getTests()
    {
        return [
            new TwigTest("numeric", [$this, "estNumerique"])
        ];
    }

    /**
     * Convertir un montant en lettres
     */
    public function toWords($number)
    {
        return ucfirst($this->numberToWords->convert($number));
    }
}
