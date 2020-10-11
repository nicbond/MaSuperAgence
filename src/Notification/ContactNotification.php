<?php

namespace App\Notification;

use App\Entity\Contact;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class ContactNotification
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    public function notify(Contact $contact)
    {
          $email = new TemplatedEmail(); 
          $email ->subject('Agence : ' . $contact->getProperty()->getTitle()) 
                 ->from('noreply@mon-agence.local')
                 ->to('contact@mon-agence.local') 
                 ->replyTo($contact->getEmail()) 
                 ->htmlTemplate('emails/contact.html.twig') 
                 //->textTemplate('emails/contact.txt.twig') 
                 ->context(['contact' => $contact]) ;

          $this->mailer->send($email);
    }
}
