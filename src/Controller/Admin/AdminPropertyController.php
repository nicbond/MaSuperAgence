<?php

namespace App\Controller\Admin;

use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class AdminPropertyController extends AbstractController
{
	/**
     * @var PropertyRepository
     */
	private $repository;
	
	public function __construct(PropertyRepository $repository)
	{
		$this->repository = $repository;
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
}
