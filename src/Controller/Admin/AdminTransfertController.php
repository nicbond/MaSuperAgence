<?php

namespace App\Controller\Admin;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Notification\MailNotification;
use App\Repository\PropertyRepository;
use App\Service\XmlWriter;
use App\Entity\Option;
use App\Exception;

class AdminTransfertController extends AbstractController
{
    public function __construct(ParameterBagInterface $params, PropertyRepository $repository)
    {
        $this->params = $params;
        $this->repository = $repository;
    }

    /**
     * @Route("/admin/transfert", name="admin.transfert.index")
     * @param Request $request
     * @param UserInterface $user
     * @param MailNotification $notification
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request,  UserInterface $user, MailNotification $notification): Response
    {
        if ($request->getMethod() == 'POST') {
            $form = $this->getTransfertForm();
            $form->handleRequest($request);
            $data = $form->getData();

            if ($form->isValid()) {
                if ($form->get('validate')->isClicked()) {
                    $this->generatePain($data, $user, $notification);
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
     * @param MailNotification $notification
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function changeDate(Request $request, UserInterface $user, MailNotification $notification): Response
    {
        return $this->index($request, $user, $notification);
    }

    public function setPeriod(string $period): Void
    {
        $this->get('session')->set('date', $period);
    }

    /**
     * @return $periods
     */
    public function getPeriod(): \stdClass
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

    private function generatePain($data, $user, $notification): void
    {
        $dataReglement = $this->setData($data['value'], $data['option']);
        $attachment = $this->generateSepaFile($dataReglement, date('Ymd').'_'.str_replace(' ', '', $data['option']->getName()).'_'.'painTransfert'.'.xml');

        $body = 'Virement de '.$data['value'].' € '.'</br>'.'demandé par '.strtoupper($user->getUsername()).'</br>'.' pour le partenaire : '.$data['option']->getName();

        $destination = $this->params->get('upload');
        $fileAttachment = $destination.$attachment['url'];
        $notification->notifyMail($data, $body, $fileAttachment);
    }

    private function setData($amount, $option): array
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

    private function generateSepaFile($data, $name): array
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

    /**
     * @Route("/admin/transfert/excel", name="admin.biens.excel")
     * @param Request $request
     * @return Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function generateExcel(Request $request): BinaryFileResponse
    {
        $properties = $this->repository->findAllByPriceDesc();
        $x = 2;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet = $spreadsheet->createSheet(0);

        $sheet->setCellValueByColumnAndRow(1, 1, "Surface");
        $sheet->setCellValueByColumnAndRow(2, 1, "Pièces");
        $sheet->setCellValueByColumnAndRow(3, 1, "Chambres");
        $sheet->setCellValueByColumnAndRow(4, 1, "Etages");
        $sheet->setCellValueByColumnAndRow(5, 1, "Chauffage");
        $sheet->setCellValueByColumnAndRow(6, 1, "Ville");
        $sheet->setCellValueByColumnAndRow(7, 1, "Code Postal");
        $sheet->setCellValueByColumnAndRow(8, 1, "Prix");

        foreach ($properties as $property) {
            $surface = $property->getSurface();
            $rooms = $property->getRooms();
            $bedrooms = $property->getBedrooms();
            $floor = $property->getFloor();
            $heat = $property->getHeatType();
            $city = $property->getCity();
            $postal_code = str_replace(' ', '', $property->getPostalCode());
            $price = $property->getFormattedPrice();

            $sheet->setCellValueByColumnAndRow(1, $x, $surface);
            $sheet->setCellValueByColumnAndRow(2, $x, $rooms);
            $sheet->setCellValueByColumnAndRow(3, $x, $bedrooms);
            $sheet->setCellValueByColumnAndRow(4, $x, $floor);
            $sheet->setCellValueByColumnAndRow(5, $x, $heat);
            $sheet->setCellValueByColumnAndRow(6, $x, $city);
            $sheet->setCellValueByColumnAndRow(7, $x, $postal_code);
            $sheet->setCellValueByColumnAndRow(8, $x, $price);
            $x++;
        }

        $sheet->setTitle("Biens non vendus");

        // Ces deux lignes permettent de créer une nouvelle feuille
        $sheet = $spreadsheet->createSheet(1);
        $sheet->setTitle("My Second Worksheet");

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // Create a Temporary file in the system
        $fileName = 'my_first_excel_symfony4.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
