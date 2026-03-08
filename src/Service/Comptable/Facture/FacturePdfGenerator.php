<?php

namespace App\Service\Comptable\Facture;

use App\Entity\Site;
use App\Service\Pdf\PdfAssetsService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class FacturePdfGenerator extends AbstractController
{

    public function __construct(
        private PdfAssetsService $pdfAssetsService,
        private FacturePdfDataBuilder $dataBuilder
    ) {}

    public function generate(array $facturesGroup, Site $site, array $banques): Response
    {
        $logoBase64 = $this->pdfAssetsService->getEntrepriseLogoBase64($site);
        $filigraneBase64 = $this->pdfAssetsService->getFiligraneBase64();

        // 🔹 Préparer les données calculées
        foreach ($facturesGroup as &$bloc) {

            $preparedFactures = [];

            foreach ($bloc['factures'] as $facture) {

                $facturePdfData = $this->dataBuilder->build($facture);

                $preparedFactures[] = [
                    'entity' => $facture,
                    'pdf' => $facturePdfData
                ];
            }

            $bloc['factures'] = $preparedFactures;
        }
        
        $html = $this->renderView(
            'logescom/comptable/facture/facture_pdf.html.twig',
            [
                'facturesRegroupees' => $facturesGroup,
                'logoPath' => $logoBase64,
                'filigrane' => $filigraneBase64,
                'site' => $site,
                'banques' => $banques
            ]
        );

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="facture.pdf"',
            ]
        );
    }
}