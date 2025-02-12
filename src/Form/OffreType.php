<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType; 
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Entity\Offre;
use App\Entity\CategoryOffre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType; 

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title',TextType::class,[
                'label'=> 'Enter title',
                'attr' => [
                    'placeholder'=> 'title'
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => CategoryOffre::class,  // Classe de l'entité liée
                'choice_label' => 'name',         // Propriété à afficher dans la liste déroulante
                'placeholder' => 'Select a category', // Optionnel, pour afficher un texte par défaut
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Select status',
                'choices'  => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                ],
                'required' => true,
                'empty_data' => 'active',
                'data' => 'active',
            ])
           
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offre::class,
            // Configure your form options here
        ]);
    }
}
