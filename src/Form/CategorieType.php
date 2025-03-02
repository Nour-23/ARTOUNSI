<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;



use Symfony\Component\Form\Extension\Core\Type\TextType;

class CategorieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The category type cannot be empty.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'The category type must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The category type cannot be longer than {{ limit }} characters.'
                    ])
                ],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter category type']
            ])
            ->add('sousCategorie', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The subcategory cannot be empty.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'The subcategory must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The subcategory cannot be longer than {{ limit }} characters.'
                    ])
                ],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter subcategory']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
        ]);
    }
}