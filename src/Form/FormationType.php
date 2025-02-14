<?php

namespace App\Form;

use App\Entity\Formation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    //new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
                'attr' => ['placeholder' => 'Entrez le nom']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
                'attr' => ['placeholder' => 'Entrez la description (optionnel)']
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => true, // Rend le champ obligatoire
                'input' => 'datetime_immutable',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est obligatoire.']),
                    new Assert\Type(type: \DateTimeInterface::class, message: 'Veuillez entrer une date valide.'),
                    new Assert\GreaterThan([
                        'value' => 'today', 
                        'message' => 'La date de début doit être supérieure à la date actuelle.'
                    ]),
                ],
            ])
            
            
            
            
            ->add('duree', NumberType::class, [
                'label' => 'Durée (en jours)',
                'constraints' => [
                    //new Assert\NotBlank(['message' => 'La durée est obligatoire.']),
                    new Assert\Positive(['message' => 'La durée doit être un nombre positif.']),
                ],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'constraints' => [
                    //new Assert\NotBlank(['message' => 'Le lieu est obligatoire.']),
                ],
            ])
            ->add('formateur', TextType::class, [
                'label' => 'Formateur',
                'constraints' => [
                   // new Assert\NotBlank(['message' => 'Le formateur est obligatoire.']),
                ],
            ])
            ->add('tarif', NumberType::class, [
                'label' => 'Tarif',
                'constraints' => [
                   // new Assert\NotBlank(['message' => 'Le tarif est obligatoire.']),
                    new Assert\PositiveOrZero(['message' => 'Le tarif ne peut pas être négatif.']),
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
