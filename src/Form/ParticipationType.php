<?php
namespace App\Form;

use App\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('response', ChoiceType::class, [
                'choices' => [
                    'Yes' => 1,
                    'No' => 0
                ],
                'label' => 'Will you participate?',
            ])
            ->add('feedback', TextareaType::class, [
                'required' => false,
                'label' => 'Provide Feedback (optional)',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Submit Participation']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
        ]);
    }
}
