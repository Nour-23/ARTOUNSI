<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Formation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le nom'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom de la catégorie ne doit pas être vide.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez une description'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description ne doit pas être vide.']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('formations', EntityType::class, [
                'class' => Formation::class,
                'choice_label' => 'nom', // Assurez-vous que "nom" existe dans Formation
                'multiple' => true,
                'expanded' => false, // Change à `true` si tu veux des cases à cocher
                'required' => false,
                'mapped' => false, // Important pour éviter un problème de persistance automatique
                'attr' => ['class' => 'form-select'],
                'label' => 'Choisir les formations associées',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
