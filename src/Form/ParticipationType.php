<?php

namespace App\Form;

use App\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add a "response" field that will allow the user to choose whether they want to participate
        $builder
            ->add('response', ChoiceType::class, [
                'choices' => [
                    'Yes' => 1, // Participate
                    'No' => 0,  // Do not participate
                ],
                'expanded' => true, // Render as radio buttons
                'multiple' => false, // Single choice
                'label' => 'Do you want to participate?', // Label for the response field
            ])
            // Add a "feedback" field for optional feedback about the event
            ->add('feedback', TextType::class, [
                'required' => false,  // Feedback is optional
                'label' => 'Your Feedback', // Label for the feedback field
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class, // Link the form to the Participation entity
        ]);
    }
}
