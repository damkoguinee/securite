<?php

namespace App\Controller\Logescom\Contact;


use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;


#[Route('/email/contact')]
class ContactController extends AbstractController
{
    #[Route('/', name: 'app_email_contact_index', methods: ['GET'])]
    public function index(ContactRepository $contactRepository): Response
    {
        return $this->render('email/contact/index.html.twig', [
            'contacts' => $contactRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_email_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $contact->setDateContact(new \DateTime('now'));
            $entityManager->persist($contact);
            $entityManager->flush();

            // Récupérez les données du formulaire
            $form_data = $form->getData();
            $phone = $form_data->getTelephone();
            $email = $form_data->getEmail();
            $prenom = $form_data->getPrenom();
            $nom = $form_data->getNom();
            $message = $form_data->getMessage();

            // Créez le contenu de l'e-mail
            $emailContent = sprintf(
                "Téléphone: %s\nEmail: %s\nPrénom: %s\nNom: %s\nMessage: %s",
                $phone,
                $email,
                $prenom,
                $nom,
                $message
            );
            try {
                $email = (new Email())
                    ->from(new Address('responsable-commercial@damkocompany.com', 'koulamatco'))
                    ->to('responsable-commercial@damkocompany.com')
                    ->subject('Demande client')
                    ->text($emailContent);
            
                $mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                // Affichez les détails de l'erreur SMTP
                dd($e->getDebug(), $e->getMessage());
            }
            dd($email);

            $this->addFlash("success", "Votre demande a été transmise. Nous reviendrons vers vous très prochainement. Merci de votre confiance");
        
            return $this->redirectToRoute('app_home', ['_fragment' => 'contact']);
        }


        return $this->render('email/contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_email_contact_show', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        return $this->render('email/contact/show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_email_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_email_contact_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('email/contact/edit.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_email_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contact);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_email_contact_index', [], Response::HTTP_SEE_OTHER);
    }
}
