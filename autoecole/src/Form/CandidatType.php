<?php

namespace App\Form;

use App\Entity\Candidat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de naissance',
            ])
            ->add('telephone', TextType::class, [
                'required' => false,
                'label' => 'Téléphone',
            ])
            ->add('email', EmailType::class, [
                'required' => false, // ✅ facultatif
                'empty_data' => 'non spécifié', // ✅ valeur par défaut
                'label' => 'Email',
            ])
            ->add('lieuNaissance', TextType::class, [
                'required' => false,
                'label' => 'Lieu de naissance',
            ])
            ->add('groupeSanguin', ChoiceType::class, [
                'required' => false,
                'label' => 'Groupe sanguin',
                'choices' => [
                    'Non spécifié' => null,
                    'A+' => 'A+',
                    'A-' => 'A-',
                    'B+' => 'B+',
                    'B-' => 'B-',
                    'AB+' => 'AB+',
                    'AB-' => 'AB-',
                    'O+' => 'O+',
                    'O-' => 'O-',
                ],
                'placeholder' => 'Sélectionnez un groupe sanguin',
            ])
            ->add('prixPermis', IntegerType::class, [
                'label' => 'Prix du permis',
            ])
            ->add('statutPaiement', ChoiceType::class, [
                'label' => 'Statut paiement',
                'choices' => [
                    'En cours' => 'en cours',
                    'Soldé' => 'soldé',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidat::class,
        ]);
    }
}
