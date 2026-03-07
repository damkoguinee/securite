<?php

namespace App\Controller\Logescom\Contact;

use App\Entity\ConfigurationSms;
use App\Entity\ForfaitSms;
use App\Form\ConfigurationSmsType;
use App\Repository\ConfigForfaitSmsRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigurationSmsRepository;
use App\Repository\ForfaitSmsRepository;
use App\Repository\SmsEnvoyesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/contact/configuration/sms')]
class ConfigurationSmsController extends AbstractController
{
    #[Route('/', name: 'app_logescom_contact_configuration_sms_index', methods: ['GET'])]
    public function index(ConfigurationSmsRepository $configurationSmsRepository, EntrepriseRepository $entrepriseRep, ConfigForfaitSmsRepository $configForfaitRep, ForfaitSmsRepository $forfaitSmsRep): Response
    {
        return $this->render('logescom/contact/configuration_sms/index.html.twig', [
            'configuration_sms' => $configurationSmsRepository->findAll(),
            'achat_forfaits' => $forfaitSmsRep->findBy([], ['id' => 'DESC']),
            'forfaits' => $configForfaitRep->findBy([]),
            'entreprise' => $entrepriseRep->findOneBy([]),

        ]);
    }

    #[Route('/new', name: 'app_logescom_contact_configuration_sms_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configurationSm = new ConfigurationSms();
        $form = $this->createForm(ConfigurationSmsType::class, $configurationSm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configurationSm);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_contact_configuration_sms_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/contact/configuration_sms/new.html.twig', [
            'configuration_sm' => $configurationSm,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),

        ]);
    }

    #[Route('/{id}', name: 'app_logescom_contact_configuration_sms_show', methods: ['GET'])]
    public function show(ConfigurationSms $configurationSm, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/contact/configuration_sms/show.html.twig', [
            'configuration_sm' => $configurationSm,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_contact_configuration_sms_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigurationSms $configurationSm, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigurationSmsType::class, $configurationSm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_contact_configuration_sms_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/contact/configuration_sms/edit.html.twig', [
            'configuration_sm' => $configurationSm,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),

        ]);
    }

    #[Route('/{id}', name: 'app_logescom_contact_configuration_sms_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigurationSms $configurationSm, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configurationSm->getId(), $request->request->get('_token'))) {
            $entityManager->remove($configurationSm);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_contact_configuration_sms_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/show/achat/forfait/{id}', name: 'app_logescom_contact_configuration_sms_show_achat_forfait', methods: ['GET'])]
    public function showAchatForfait(ForfaitSms $forfaitSms, SmsEnvoyesRepository $smsRep, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/contact/sms/show.html.twig', [
            'achat' => $forfaitSms,
            'smsEnvoyes' => $smsRep->findBy(['forfait' => $forfaitSms], ['id' => 'DESC']),
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }
}
