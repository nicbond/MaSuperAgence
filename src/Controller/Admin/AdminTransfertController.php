<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Option;

class AdminTransfertController extends AbstractController
{
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
}
