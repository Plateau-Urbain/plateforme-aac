<?php
// vim:expandtab:sw=4 softtabstop=4:

namespace App\Form;

use App\Entity\Parcel;
use App\Entity\Space;
use App\Entity\SpaceAttribute;
use App\Entity\SpaceImage;
use App\Entity\SpaceDocument;
use App\Entity\SpaceVisit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpaceType extends AbstractType
{
    /**
     * SpaceType constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'Nom de l\'espace' , 'attr' => ['class' => 'form-control']])
            ->add('zipCode', null, ['label' => 'Code postal' , 'attr' => ['class' => 'form-control'], 'required' => false])
            ->add('city', null, ['label' => 'Ville' , 'attr' => ['class' => 'form-control'], 'required' => false])
            ->add('limitAvailability', DateTimeType::class, [
                    'label' => 'Date et heure limite de candidature',
                    'date_widget' => 'single_text',
                    'time_widget' => 'choice',
                    'with_seconds' => false,
                    'attr' => [
                        'class' => 'form-control'
                    ],
                    'hours' => range(0, 23),
                    'minutes' => range(0, 59),
                    'required' => false
                ]
            )
            ->add('type', null, ['label' => 'Type de locaux', 'attr' => ['class' => 'form-control'], 'required' => true])
            ->add('price', NumberType::class, [
                'label'    => 'Prix au m² mensuel',
                'attr'     => ['class' => 'form-control', 'step' => '0.01', 'placeholder' => 'Ex: 12.50'],
                'required' => false,
                'scale'    => 2,
            ])
            ->add('priceText', null, ['label' => 'Prix personnalisé (texte libre)', 'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Sur devis, Prix négociable, etc.'], 'required' => false])
            ->add('availability', null, ['label' => 'Période de disponibilité', 'attr' => ['class' => 'form-control', 'placeholder' => "1 an, 6 mois…"], 'required' => false])
            ->add('nbSpaces', IntegerType::class, ['label' => 'Nombre d\'espaces', 'attr' => ['class' => 'form-control'], 'required' => false])
            ->add('minSpace', IntegerType::class, ['label' => 'Surface minimale (m²)', 'attr' => ['class' => 'form-control'], 'required' => false])
            ->add('maxSpace', IntegerType::class, ['label' => 'Surface maximale (m²)', 'attr' => ['class' => 'form-control'], 'required' => false])
            ->add('description', null, ['label' => 'Description', 'attr' => ['class' => 'form-control'], 'required' => false])
            ->add('activityDescription', null, ['label' => 'Activités recherchées', 'attr' => ['class' => 'form-control'], 'required' => false])
            ->add('rollingApplications', CheckboxType::class, [
                'label' => 'Candidature au fil de l’eau (affiche la “date d’entrée souhaitée” côté candidat)',
                'required' => false,
            ])
            ->add('isErp', CheckboxType::class, [
                'label' => 'Le lieu est un ERP (Établissement Recevant du Public)',
                'required' => false,
            ])
            ->add('tags', CollectionType::class, [
                'entry_type' => SpaceAttributeType::class,
                'label' => false,
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
                'required' => false
            ])
            ->add('pics', SpaceImageType::class,
                [
                'label' => 'Ajouter une photo',
                'mapped' => false,
                'data' => new SpaceImage(),
                'required' => false,
                'error_bubbling' => false
            ]
            )
            ->add(
                'newDocument',
                SpaceDocumentType::class,
                [
                'label'     => 'Ajouter un document',
                'mapped' => false,
                'data' => new SpaceDocument(),
                'required' => false
            ]
            )
            ->add(
                'newVisit',
                SpaceVisitType::class,
                [
                'label'            => 'Ajouter une visite',
                'mapped'           => false,
                'data'             => new SpaceVisit(),
                'required'         => false,
                'validation_groups' => false,
            ]
            );

        if (empty($builder->getForm()->getData()->getDocs(SpaceImage::FILETYPE_DOCUMENT_AAC))) {
            $builder->add('doc_aac', SpaceDocType::class, [
                    'label' => "Document d'appel à candidature",
                    'mapped' => false,
                    'data' => new SpaceImage(),
                    'required' => false,
                    'error_bubbling' => false,
                    'file_type' => SpaceImage::FILETYPE_DOCUMENT_AAC,
                ]);
        }

        if (empty($builder->getForm()->getData()->getDocs(SpaceImage::FILETYPE_DOCUMENT_PLAN))) {
            $builder->add('doc_plan', SpaceDocType::class, [
                    'label' => "Répartition des espaces",
                    'mapped' => false,
                    'data' => new SpaceImage(),
                    'required' => false,
                    'error_bubbling' => false,
                    'file_type' => SpaceImage::FILETYPE_DOCUMENT_PLAN,
            ]);
        }

        if (empty($builder->getForm()->getData()->getDocs(SpaceImage::FILETYPE_DOCUMENT_FAQ))) {
            $builder->add('doc_faq', SpaceDocType::class, [
                    'label' => "F.A.Q",
                    'mapped' => false,
                    'data' => new SpaceImage(),
                    'required' => false,
                    'error_bubbling' => false,
                    'file_type' => SpaceImage::FILETYPE_DOCUMENT_FAQ,
            ]);
        }


        $attributes = $this->getAttributes();
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($attributes) {
            /**
             * @var Space $data
             */
            $data = $event->getData();

            $currentAttributes = $data->getTags()->map(function ($spaceAttribute) {
                return $spaceAttribute->getAttribute();
            });

            foreach ($attributes as $attribute) {
                if (!$currentAttributes->contains($attribute)) {
                    $spaceAttribute = new SpaceAttribute();
                    $spaceAttribute->setAttribute($attribute);
                    $data->addTag($spaceAttribute);
                }
            }
        });

        // SUBMIT: handle data transformations and attach new files (runs before validation)
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /**
             * @var Space $space
             */
            $space = $event->getData();

            // Handles new image (only when a file was uploaded)
            $newImage = $event->getForm()->get('pics')->getData();
            if ($newImage instanceof SpaceImage && $newImage->getFile() !== null) {
                $newImage->setPosition(count($space->getPics()));
                $space->addPic($newImage);
            }

            // Handles new document (only when a name was provided)
            $newDocument = $event->getForm()->get('newDocument')->getData();
            if ($newDocument instanceof SpaceDocument && trim((string) $newDocument->getName()) !== '') {
                $newDocument->setSpace($space);
                $space->addDocument($newDocument);
            }

            // Handles new visit
            $newVisit = $event->getForm()->get('newVisit')->getData();
            if ($newVisit instanceof SpaceVisit && $newVisit->getVisitDate() !== null) {
                $space->addVisit($newVisit);
            }

            // Handles new required doc
            $newDocAAC = null;
            if ($event->getForm()->has('doc_aac')) {
                $newDocAAC = $event->getForm()->get('doc_aac')->getData();
            }
            if ($newDocAAC instanceof SpaceImage && $newDocAAC->getFile() !== null) {
                $space->addDoc($newDocAAC, SpaceImage::FILETYPE_DOCUMENT_AAC);
            }

            $newDocPlan = null;
            if ($event->getForm()->has('doc_plan')) {
                $newDocPlan = $event->getForm()->get('doc_plan')->getData();
            }
            if ($newDocPlan instanceof SpaceImage && $newDocPlan->getFile() !== null) {
                $space->addDoc($newDocPlan, SpaceImage::FILETYPE_DOCUMENT_PLAN);
            }

            $newDocFaq = null;
            if ($event->getForm()->has('doc_faq')) {
                $newDocFaq = $event->getForm()->get('doc_faq')->getData();
            }
            if ($newDocFaq instanceof SpaceImage && $newDocFaq->getFile() !== null) {
                $space->addDoc($newDocFaq, SpaceImage::FILETYPE_DOCUMENT_FAQ);
            }
        });

        // POST_SUBMIT: cleanup or post-processing AFTER validation
        // (Previously handled attachment, but moved to SUBMIT to allow validation to see the files)
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            if (!$event->getForm()->isValid()) {
                return;
            }
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // The "cascade_validation" option is deprecated since Symfony 2.8 and
        // will be removed in 3.0. Use "constraints" with a Valid constraint
        // instead.
        $resolver->setDefaults([
            'data_class' => \App\Entity\Space::class,
            //'cascade_validation' => true,
            'constraints' => new \Symfony\Component\Validator\Constraints\Valid(),
            'validation_groups' => function (FormInterface $form) {
                $publish = $form->has('publish') ? $form->get('publish') : null;
                if ($publish instanceof ClickableInterface && $publish->isClicked()) {
                    return ['save'];
                }

                return ['draft'];
            }
        ]);
    }

    // The FormTypeInterface::getName()
    // method is deprecated since Symfony 2.8 and will be removed in 3.0.
    // Remove it from your classes. Use getBlockPrefix() if you want
    // to customize the template block prefix.
    // This method will be added to the FormTypeInterface with Symfony 3.0
    /**
     * @return string
     */
    //public function getName()
    public function getBlockPrefix()
    {
        return 'appbundle_space';
    }

    /**
     * @return \App\Entity\Attribute[]|array
     */
    protected function getAttributes()
    {
        return $this->em->getRepository(\App\Entity\Attribute::class)->findAll();
    }
}
