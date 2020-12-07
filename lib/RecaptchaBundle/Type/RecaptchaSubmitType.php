<?php

namespace Nicbond\RecaptchaBundle\Type;

use Nicbond\RecaptchaBundle\Constraints\Recaptcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecaptchaSubmitType extends AbstractType
{
    /**
     * @var string
     */
    private $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'constraints' => new Recaptcha()
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['label'] = false;
        $view->vars['key'] = $this->key;
        $view->vars['button'] = $options['label'];
    }

    public function getBlockPrefix()
    {
        return 'recaptcha_submit';
    }

    public function getParent()
    {
        return TextType::class;
    }
}
