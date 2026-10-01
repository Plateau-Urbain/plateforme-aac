<?php

namespace App\Form;

use App\Entity\SpaceLocation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class SpaceLocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom du site',
                'attr' => ['class' => 'form-control'],
                'error_bubbling' => false,
            ])
            ->add('address', null, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'error_bubbling' => false,
            ])
            ->add('zipCode', null, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-control js-location-zip', 'inputmode' => 'numeric'],
                'required' => false,
                'error_bubbling' => false,
            ])
            ->add('city', null, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control js-location-city'],
                'error_bubbling' => false,
            ])
            ->add('latitude', HiddenType::class, [
                'attr' => ['class' => 'js-location-lat'],
                'required' => false,
                'empty_data' => null,
            ])
            ->add('longitude', HiddenType::class, [
                'attr' => ['class' => 'js-location-lng'],
                'required' => false,
                'empty_data' => null,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descriptif du projet',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'required' => 'required'],
                'required' => true,
                'error_bubbling' => false,
            ])
            ->add('activityDescription', TextareaType::class, [
                'label' => 'Activités recherchées',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false,
                'help' => 'Obligatoire pour publier.',
                'help_attr' => ['class' => 'help-block'],
                'error_bubbling' => false,
            ])
            ->add('availability', null, [
                'label' => 'Durée du projet',
                'attr' => ['class' => 'form-control', 'placeholder' => '1 an, 6 mois…', 'required' => 'required'],
                'required' => true,
                'error_bubbling' => false,
            ])
            ->add('aacDocument', VichFileType::class, $this->documentOptions(
                "Document d'appel à candidature (facultatif)",
                'PDF, DOC ou DOCX, 10 Mo maximum.',
                static fn (SpaceLocation $location): ?string => $location->getAacDocumentDisplayName(),
            ))
            ->add('planDocument', VichFileType::class, $this->documentOptions(
                'Répartition des espaces (facultatif)',
                'PDF, DOC ou DOCX, 10 Mo maximum.',
                static fn (SpaceLocation $location): ?string => $location->getPlanDocumentDisplayName(),
            ))
            ->add('faqDocument', VichFileType::class, $this->documentOptions(
                'F.A.Q (facultatif)',
                'PDF, DOC ou DOCX, 10 Mo maximum.',
                static fn (SpaceLocation $location): ?string => $location->getFaqDocumentDisplayName(),
            ))
            ->add('isErp', CheckboxType::class, [
                'label' => 'Le site est un ERP (Établissement Recevant du Public)',
                'required' => false,
            ])
            ->add('displayOrder', HiddenType::class, [
                'attr' => ['class' => 'js-location-order'],
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('suspended', CheckboxType::class, [
                'label' => 'Suspendre ce site (plus de disponibilité)',
                'required' => false,
                'attr' => ['class' => 'js-location-suspended'],
            ])
            ->add('suspensionMessage', TextareaType::class, [
                'label' => 'Message aux candidats (obligatoire si suspendu)',
                'attr' => ['class' => 'form-control js-location-suspension-message', 'rows' => 2],
                'required' => false,
                'error_bubbling' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $location = $event->getData();
            if (!$location instanceof SpaceLocation) {
                return;
            }
            if ($location->getDisplayOrder() === 0 && $location->getId() === null) {
                $location->setDisplayOrder(0);
            }
        });
    }

    private function documentOptions(string $label, string $help, callable $displayName): array
    {
        return [
            'label' => $label,
            'required' => false,
            'allow_delete' => true,
            'download_uri' => true,
            'download_label' => static fn (SpaceLocation $location): array => [
                'download_label' => $displayName($location),
                'download_label_translation_domain' => false,
            ],
            'delete_label' => 'Supprimer ce document',
            'delete_label_translation_domain' => false,
            'help' => $help,
            'help_attr' => ['class' => 'help-block'],
            'attr' => [
                'accept' => '.pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'data-doc-label' => preg_replace('/ \(facultatif\)$/', '', $label),
            ],
            'error_bubbling' => false,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SpaceLocation::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_spacelocation';
    }
}
