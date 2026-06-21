<?php

namespace App\Form;

use App\Entity\Candidat;
use App\Entity\CandidatExamen;
use App\Entity\Examen;
use App\Service\AutoEcoleManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatExamenType extends AbstractType
{
    public function __construct(private readonly AutoEcoleManager $manager) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('candidat', EntityType::class, [
                'class' => Candidat::class,
                'choice_label' => fn (Candidat $c) => $c->getNomComplet(),
                'label' => 'Candidat',
                'placeholder' => '-- Choisir un candidat --',
            ])
            ->add('examen', EntityType::class, [
                'class' => Examen::class,
                'choice_label' => function (Examen $e): string {
                    $date = $e->getDatePassage()?->format('d/m/Y') ?? 'date à définir';
                    return $e->getTypeLabel().' - '.$date.' - '.($e->getLieu() ?: 'lieu à définir');
                },
                'label' => 'Examen',
                'placeholder' => '-- Choisir un examen --',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => ['Inscrit' => 'inscrit', 'Payé' => 'payé', 'Réussi' => 'réussi', 'Échoué' => 'échoué'],
            ])
            ->add('resteAPayer', MoneyType::class, ['currency' => 'DZD', 'label' => 'Reste à payer examen', 'required' => false]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $ce = $event->getData();
            if ($ce instanceof CandidatExamen && $ce->getCandidat() && $ce->getExamen()) {
                if (!$this->manager->peutPasserExamen($ce->getCandidat(), $ce->getExamen()->getTypeExamen())) {
                    $event->getForm()->addError(new FormError('Progression invalide : le candidat doit valider l’étape précédente.'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CandidatExamen::class]);
    }
}
