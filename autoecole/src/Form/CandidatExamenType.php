<?php

namespace App\Form;

use App\Entity\CandidatExamen;
use App\Entity\Candidat;
use App\Entity\Examen;
use App\Service\AutoEcoleManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatExamenType extends AbstractType
{
    public function __construct(private AutoEcoleManager $manager) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('candidat', EntityType::class, [
                'class' => Candidat::class,
                'choice_label' => fn(Candidat $c) => $c->getPrenom().' '.$c->getNom(),
                'label' => 'Candidat',
                'placeholder' => '-- Choisir un candidat --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('examen', EntityType::class, [
                'class' => Examen::class,
                'choice_label' => fn(Examen $e) => ucfirst($e->getTypeExamen()).' - '.$e->getDatePassage()->format('d/m/Y'),
                'label' => 'Examen',
                'placeholder' => '-- Choisir un examen --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Inscrit' => 'inscrit',
                    'Réussi' => 'réussi',
                    'Échoué' => 'échoué',
                ],
                'placeholder' => '-- Sélectionner un statut --',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ]);

        // Validation dynamique
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $ce = $event->getData();
            if ($ce instanceof CandidatExamen && $ce->getCandidat() && $ce->getExamen()) {
                if (!$this->manager->peutPasserExamen($ce->getCandidat(), $ce->getExamen()->getTypeExamen())) {
                    $form = $event->getForm();
                    $form->addError(new \Symfony\Component\Form\FormError("Ce candidat ne peut pas encore passer cet examen !"));
                }
            }
        });
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidatExamen::class,
        ]);
    }
}
