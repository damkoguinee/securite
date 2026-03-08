<?php

namespace App\Service\Pdf;

use App\Entity\Site;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PdfAssetsService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {}

    public function getEntrepriseLogoBase64(Site $site): ?string
    {
        $entreprise = $site->getEntreprise();

        if (!$entreprise || !$entreprise->getLogo()) {
            return null;
        }

        $logoPath = $this->projectDir.'/public/images/img_logos/'.$entreprise->getLogo();

        if (!file_exists($logoPath)) {
            return null;
        }

        return base64_encode(file_get_contents($logoPath));
    }

    public function getFiligraneBase64(): ?string
    {
        $path = $this->projectDir.'/public/images/watermark/logescom_filigrane.png';

        if (!file_exists($path)) {
            return null;
        }

        return base64_encode(file_get_contents($path));
    }
}