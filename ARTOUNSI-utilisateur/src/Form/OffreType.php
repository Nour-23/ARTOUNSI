<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType; 
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType; 
use App\Entity\Offre;
use App\Entity\CategoryOffre;

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'offre',
                'attr' => [
                    'placeholder' => 'Entrez le titre de l\'offre'
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => CategoryOffre::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez une catégorie',
                'label' => 'Catégorie'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrez une description de l\'offre'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut de l\'offre',
                'choices'  => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'Archivé' => 'archivé',  // Ajout du statut archivé
                ],
                'required' => true,
                'empty_data' => 'active',
                'data' => 'active',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offre::class,
        ]);
    }
}

