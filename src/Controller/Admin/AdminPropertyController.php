<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use App\Form\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class AdminPropertyController extends AbstractController
{
	/**
     * @var PropertyRepository
     */
	private $repository;
	/**
     * @var EntityManagerInterface
     */
	private $entityManager;
	
	public function __construct(PropertyRepository $repository, EntityManagerInterface $entityManager)
	{
		$this->repository = $repository;
		$this->entityManager = $entityManager;
	}

	/**
     * @Route("/admin", name="admin.property.index")
	 * @return Response
     */
    public function index(): Response
    {
		$properties = $this->repository->findAll();

		return $this->render('admin/property/index.html.twig', compact('properties'));
    }
	
	/**
     * @Route("/admin/property/create", name="admin.property.new")
	 * @param Request $request
	 * @return Response
     */
    public function new(Request $request): Response
    {
		$property = new Property();
		
		$form = $this->createForm(PropertyType::class, $property);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			$this->entityManager->persist($property);
			$this->entityManager->flush();
			
			return $this->redirectToRoute('admin.property.index');
		}
		
		return $this->render('admin/property/new.html.twig',  [
			'property' => $property,
			'form' => $form->createView()
			]);
    }
		
	/**
     * @Route("/admin/property/{id}", name="admin.property.edit")
	 * @param Request $request
	 * @return Response
     */
    public function edit(Request $request, $id): Response
    {
		$property = $this->repository->find($id);
		
		$form = $this->createForm(PropertyType::class, $property);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			$this->entityManager->flush();
			
			return $this->redirectToRoute('admin.property.index');
		}
		
		return $this->render('admin/property/edit.html.twig',  [
			'property' => $property,
			'form' => $form->createView()
			]);
    }
}
