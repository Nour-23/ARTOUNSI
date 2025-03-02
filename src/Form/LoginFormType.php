<?php namespace App\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'mapped' => false, // Ajouté pour éviter que Symfony ignore la valeur
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Email'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer une adresse email.'
                    ]),
                    new Assert\Email([
                        'message' => 'Veuillez entrer un email valide.'
                    ])
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Mot de passe'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer votre mot de passe.'
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Se connecter',
                'attr' => ['class' => 'btn btn-primary w-100']
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void  // Ajout de ": void"
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id'   => 'authenticate',
        ]);
    }
}
