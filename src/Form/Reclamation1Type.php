<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Reclamation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert;

class Reclamation1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The title cannot be empty.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'The title must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The title cannot be longer than {{ limit }} characters.'
                    ])
                ],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter title']
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The description cannot be empty.']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'The description must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The description cannot be longer than {{ limit }} characters.'
                    ])
                ],
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Enter description']
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'type',
                'placeholder' => 'Select a category',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a category.']),
                ],
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
