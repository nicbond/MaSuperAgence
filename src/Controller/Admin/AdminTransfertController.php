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
    public function index(Request $request): Response
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

        return $this->render('admin/transfert/index.html.twig', array(
            'transfertForm'   => $this->getTransfertForm()->createView()
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
}
