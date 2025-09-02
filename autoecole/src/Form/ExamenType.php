<?php

namespace App\Form;

use App\Entity\Examen;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeExamen', ChoiceType::class, [
                'choices' => [
                    'Code' => 'code',
                    'Créneau' => 'creneau',
                    'Conduite' => 'conduite',
                ],
                'label' => 'Type d\'examen',
            ])
            ->add('datePassage', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de passage',
            ])
            ->add('frais', MoneyType::class, [
                'currency' => 'EUR',
                'label' => 'Frais',
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu de l\'examen',
            ])
            ->add('statutExamen', CheckboxType::class, [
                'required' => false,
                'label' => 'Payé à temps ?',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Examen::class,
        ]);
    }
}
