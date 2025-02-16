<?php

namespace App\Form;

use App\Entity\Formation;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la formation',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le nom'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom ne doit pas être vide.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 20,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez une description', 'rows' => 4],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description ne doit pas être vide.']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d') // Désactive les dates passées
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début ne doit pas être vide.']),
                    new Assert\GreaterThan([
                        'value' => 'today',
                        'message' => 'La date doit être supérieure ou égale à aujourd\'hui.',
                    ]),
                ],
            ])
            
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (jours)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Durée en jours'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La durée ne doit pas être vide.']),
                    new Assert\Positive(['message' => 'La durée doit être un nombre positif.']),
                ],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le lieu'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le lieu ne doit pas être vide.']),
                ],
            ])
            ->add('formateur', TextType::class, [
                'label' => 'Formateur',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le nom du formateur'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du formateur ne doit pas être vide.']),
                ],
            ])
            ->add('tarif', NumberType::class, [
                'label' => 'Tarif',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le tarif'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le tarif ne doit pas être vide.']),
                    new Assert\Positive(['message' => 'Le tarif doit être un nombre positif.']),
                ],
            ])                     
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'nom',
                'required' => false, // Permet que la catégorie soit optionnelle
                'placeholder' => 'Aucune catégorie',
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
