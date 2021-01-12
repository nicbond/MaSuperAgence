<?php

namespace App\Menu; 
 
use Knp\Menu\FactoryInterface; 
use Symfony\Component\HttpFoundation\RequestStack; 
use Knp\Menu\Renderer\ListRenderer;
 
class Builder 
{ 
    private $factory; 
 
    /** 
     * @param FactoryInterface $factory 
     */ 
    public function __construct(FactoryInterface $factory) 
    { 
        $this->factory = $factory; 
    } 
 
    public function MyMenu(RequestStack $requestStack) 
    { 
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav nav-list');
        //$menu->setChildrenAttributes(array('id' => 'main_navigation', 'class' => 'nav navbar-nav'));
        //$menu->addChild('Tableau de bord', array('route' => 'home'))->setAttribute('icon', 'menu-icon fa fa-tachometer');
 
    $menu->addChild('Administration', array('uri' => '#'));
        $menu['Administration']->setAttribute('icon', 'menu-icon fa fa-tachometer');
        $menu['Administration']->setAttribute('arrow', 'fa fa-angle-down');
        $menu['Administration']->setAttribute('span', 'menu-text');
        $menu['Administration']->setLinkAttribute('class', 'dropdown-toggle');
        $menu['Administration']->setChildrenAttribute('class', 'submenu');
        $menu['Administration']->addChild('Administration', array('route' => 'admin.user.index'));
        $menu['Administration']['Administration']->setAttribute('icon', 'menu-icon fa fa-caret-right');
        $menu['Administration']->addChild('Ajouter', array('route' => 'admin.user.index'));
        $menu['Administration']['Ajouter']->setAttribute('icon', 'menu-icon fa fa-caret-right');
 
    $menu->addChild('Sport', array('uri' => '#'));
        $menu['Sport']->setAttribute('icon', 'menu-icon fa fa-tachometer');
        $menu['Sport']->setAttribute('arrow', 'fa fa-angle-down');
        $menu['Sport']->setAttribute('span', 'menu-text');
        $menu['Sport']->setLinkAttribute('class', 'dropdown-toggle');
        $menu['Sport']->setChildrenAttribute('class', 'submenu');
        $menu['Sport']->addChild('Football', array('uri' => 'https://www.eurosport.fr/football/'));
            $menu['Sport']['Football']->setAttribute('icon', 'menu-icon fa fa-caret-right');
        $menu['Sport']->addChild('Tennis', array('uri' => 'https://www.eurosport.fr/tennis/'));
            $menu['Sport']['Tennis']->setAttribute('icon', 'menu-icon fa fa-caret-right');
 
    $menu->addChild('Médias', array('uri' => '#'));
        $menu['Médias']->setAttribute('icon', 'menu-icon fa fa-tachometer');
        $menu['Médias']->setAttribute('arrow', 'fa fa-angle-down');
        $menu['Médias']->setAttribute('span', 'menu-text');
        $menu['Médias']->setLinkAttribute('class', 'dropdown-toggle');
        $menu['Médias']->setChildrenAttribute('class', 'submenu');
        $menu['Médias']->addChild('Cinéma', array('uri' => 'https://www.allocine.fr/'));
            $menu['Médias']['Cinéma']->setAttribute('icon', 'menu-icon fa fa-caret-right');
        $menu['Médias']->addChild('Ajouter', array('route' => 'admin.user.index'));
            $menu['Médias']['Ajouter']->setAttribute('icon', 'menu-icon fa fa-caret-right');
 
        return $menu;
    } 
}