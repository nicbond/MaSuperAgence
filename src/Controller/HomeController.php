<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class HomeController
{
	/**
	* @var Environment
	*/
	private $twig;
	
	public function __construct(Environment $twig)
    {
		$this->twig = $twig;
    }
	
    public function index(): Response
    {
		return New Response($this->twig->render('pages/home.html.twig'));
    }
}
