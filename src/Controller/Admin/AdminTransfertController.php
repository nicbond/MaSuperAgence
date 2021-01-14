<?php

namespace App\Controller\Admin;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Service\XmlWriter;
use App\Entity\Option;
use App\Exception;

class AdminTransfertController extends AbstractController
{
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @Route("/admin/transfert", name="admin.transfert.index")
     * @param Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request,  UserInterface $user): Response
    {
        if ($request->getMethod() == 'POST') {
            $form = $this->getTransfertForm();
            $form->handleRequest($request);
            $data = $form->getData();

            if ($form->isValid()) {
                if ($form->get('validate')->isClicked()) {
                    $this->generatePain($data, $user);
                }
            }
        }

        if ($request->get('year') && $request->get('month')) {
            $year = $request->get('year');
            $month = $request->get('month');
            $today = '01'.'/'.$request->get('month').'/'.$request->get('year');
            $this->setPeriod($today);
        } else {
            $today = new \DateTime('now');
            $this->setPeriod($today->format('d/m/Y'));
        }

        return $this->render('admin/transfert/index.html.twig', array(
            'transfertForm'   => $this->getTransfertForm()->createView(),
            'period'          => $this->getPeriod()
        ));
    }

    private function getTransfertForm()
    {
        $today = new \DateTime();
        return $this->createFormBuilder(
            null,
            array( 'attr' =>
                    array('onSubmit'=> 'return confirm("Merci de confirmer le transfert SEPA");')
                )
            )
        ->setAction($this->generateUrl('admin.transfert.index', array()))
        ->add('option', EntityType::class, array(
          'class' => Option::class,
          'choice_label' => 'name',
          'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
              return $er->createQueryBuilder('Option');
          },
          'attr'=>array('class'=> 'Option-container','checked' => 'checked'),
        ))
        ->add('value', TextType::class, array(
                'label'=>false,
                'constraints' => array(new Assert\NotBlank())
              ))
        ->add('date', TextType::class, array(
                'data'=> $today->format('d/m/Y'),
                'disabled'=>true, //If you don’t want a user to modify the value of a field, you can set the disabled option to true
                'label'=>false,
                'constraints' => array(new Assert\NotBlank())
              ))
        ->add('validate', SubmitType::class, array('label'=>'Valider', 'attr' => array('class' => 'btn btn-success btn-block')))
        ->getForm();
    }

    /**
     * @Route("/admin/transfert/{year}-{month}", name="admin.transfert.date")
     * @param Request $request
     * @param UserInterface $user
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function changeDate(Request $request, UserInterface $user): Response
    {
        return $this->index($request, $user);
    }

    public function setPeriod(string $period): Void
    {
        $this->get('session')->set('date', $period);
    }

    /**
     * @return $periods
     */
    public function getPeriod()
    {
        $data = new \stdClass();
        if ($this->get('session')->get('date', 'notSet') == 'notSet') {
            return $data;
        }
        $period = \DateTime::createFromFormat('d/m/Y', $this->get('session')->get('date'));

        $data->month = $period->format('m');
        $data->year  = $period->format('Y');

        if (date('Y') == $data->year && date('m') == $data->month) {
            $data->nextMonth = null;
            $period->modify('+1 month');
        } else {
            $data->nextMonth = array(
              'url' => $this->generateUrl('admin.transfert.date', array('year'=> $period->modify('+1 month')->format('Y'),'month'=> $period->format('m'))),
              'text' => $period->format('m').'/'.$period->format('Y')
            );
        }
        $data->lastMonth = array(
            'url' => $this->generateUrl('admin.transfert.date', array('year'=> $period->modify('-2 month')->format('Y'),'month'=> $period->format('m'))),
            'text' => $period->format('m').'/'.$period->format('Y')
        );

        return $data;
    }

    private function generatePain($data, $user)
    {
        $dataReglement = $this->setData($data['value'], $data['option']);
        $attachment = $this->generateSepaFile($dataReglement, date('Ymd').'_'.str_replace(' ', '', $data['option']->getName()).'_'.'painTransfert'.'.xml');

        $body = 'Virement de <b>'.$data['value'].' €</b> demandé par '.strtoupper($user->getUsername()).'<br/>
            pour le partenaire : '.$data['option']->getName().'<br/>';

        dump($body);
        /*$destination = $this->params->get('upload');
        @unlink($destination.$attachment['url']);*/ //Si le fichier n'existe pas, aucune erreur n'est relévée
    }

    private function setData($amount, $option)
    {
        $dataReglement['header'] = new \stdClass();
        $dataReglement['header']->msgId                 = 'EBICSWIREREGLEMENT/'.date('Y-m-d');
        $dataReglement['header']->creDtTm               = date('Y-m-d\TH:i:s');
        $dataReglement['header']->nbOfTxs               = 1;
        $dataReglement['header']->initgPty              = 'Treezor';
        $dataReglement['header']->debitorBic            = 'CMBRFR2SXXX';
        $dataReglement['header']->debitorName           = 'Treezor';
        $dataReglement['header']->debitorIban           = 'FR7615589589000065402604117';
        $dataReglement['header']->pmtInfId              = 'PURCHASE REGLEMENT/'.date('Y-m-d');
        $dataReglement['header']->settlementDate        = date('Y-m-d');

        $dataReglement['transaction'][0]                = new \stdClass();
        $dataReglement['transaction'][0]->endToEndId    = $option->getName().'-'.date('Y-m-d');
        $dataReglement['transaction'][0]->amount        = $amount;
        $dataReglement['transaction'][0]->creditorBic   = 'BCMAFRTTXXX';
        $dataReglement['transaction'][0]->creditorName  = $option->getName();

        if ($option->getName() != 'MONISNAP') { 
            $dataReglement['transaction'][0]->chrgBr    = 'SHAR';
        }
        $dataReglement['transaction'][0]->creditorIban  = 'FR7623890000021700009586041';

        return $dataReglement;
    }

    private function generateSepaFile($data, $name)
    {
        $xmlWriter = new \App\Service\XmlWriter();
        $file = $this->params->get('file');
        $destination = $this->params->get('upload');

        try {
            $xmlWriter->setData($data);
            $xmlWriter->setFileName($name);
            $xmlWriter->setXsdFile($file);
            $xmlWriter->save($destination);
            $xmlWriter->validate();
            $attachment = array('url'=>$xmlWriter->getFileName(), 'type'=>'text/xml');
        } catch (\App\Exception $ex) {
            $sError = '';
            foreach ($ex->getErrors() as $error) {
                $sError .= sprintf(
                    'XML error "%s" [%d] (Code %d) in %s on line %d column %d' . "<br /><br />",
                    $error->message,
                    $error->level,
                    $error->code,
                    $xmlWriter->getFileName(),
                    $error->line,
                    $error->column
                );
            }
        }

        if ($attachment) {
            return $attachment;
        }
    }
}
