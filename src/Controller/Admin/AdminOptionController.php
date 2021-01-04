<?php

namespace App\Controller\Admin;

use App\Entity\Option;
use App\Form\OptionType;
use App\Repository\OptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/admin/option")
 */
class AdminOptionController extends AbstractController
{
    /**
     * @Route("/", name="admin.option.index", methods={"GET"})
     */
    public function index(OptionRepository $optionRepository, UserInterface $user): Response
    {
        $bool = self::ControlRights($user);

        if ($bool == false) {
            return $this->render('admin/role.html.twig');
        } else {
            return $this->render('admin/option/index.html.twig', [
                'current_options' => 'options',
                'options' => $optionRepository->findAll()
            ]);
        }
    }

    /**
     * @Route("/new", name="admin.option.new", methods={"GET","POST"})
     */
    public function new(Request $request, UserInterface $user): Response
    {
        $bool = self::ControlRights($user);

        if ($bool == false) {
            return $this->render('admin/role.html.twig');
        } else {
            $option = new Option();
            $form = $this->createForm(OptionType::class, $option);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($option);
                $entityManager->flush();

                return $this->redirectToRoute('admin.option.index');
            }

            return $this->render('admin/option/new.html.twig', [
                'option' => $option,
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/{id}/edit", name="admin.option.edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Option $option): Response
    {
        $form = $this->createForm(OptionType::class, $option);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin.option.index');
        }

        return $this->render('admin/option/edit.html.twig', [
            'option' => $option,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin.option.delete", methods={"DELETE"})
     */
    public function delete(Request $request, Option $option): Response
    {
        if ($this->isCsrfTokenValid('admin/delete'.$option->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($option);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin.option.index');
    }

    private function ControlRights($user)
    {
        if ($user->getUsername() != 'nicolas') {
            $response = false;
        } else {
            $response = true;
        }
        return $response;
    }
}
