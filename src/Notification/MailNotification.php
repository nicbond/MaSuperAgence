<?php

namespace App\Notification;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class MailNotification
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    public function notifyMail(array $data, $body, $file)
    {
          $email = new TemplatedEmail(); 
          $email ->subject('Pain') 
                 ->from('noreply@mon-agence.local')
                 ->to('contact@mon-agence.local') 
                 ->html($body)
                 ->attachFromPath($file); 

          $this->mailer->send($email);
    }
}
