<?php
// vim:expandtab:sw=4 softtabstop=4:

namespace App\Form;

use App\Entity\Application;
use App\Entity\ApplicationFile;
use App\Entity\Category;
use App\Entity\Space;
use App\Entity\User;
use App\Form\ProjectOwnerType;
use App\Form\ApplicationFileType;
use App\Form\ApplicationLocationPreferenceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * Class ApplicationType
 *
 * @package AppBundle\Form
 */
class ApplicationType extends AbstractType
{
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $application = $builder->getData();
        $user = $options['user'];

        if ($application instanceof Application && $user instanceof User && !$application->getProjectHolder()) {
            $application->setProjectHolder($user);
        }

        $builder
            ->add('intent', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            // Embed project owner form
            // disable_use_type=true : le champ "Type de projet" (User.useType) est affiché en récap
            // sur la page apply. On le désactive pour que sa valeur ne soit pas écrasée à la soumission.
            ->add('projectHolder', ProjectOwnerType::class, [
                'disable_use_type' => true,
            ])

            // Candidature
            ->add('name', null, ['label'=>"Nom de mon projet", 'attr' => ['class'=>'form-control input-box']])
            ->add('companyStatus', ChoiceType::class, [
                'label' => "Statut juridique",
                'choices' => Application::getApplicationCompanyStatuses(),
                'placeholder' => 'Sélectionnez un statut',
                'required' => true,
                'attr' => ['class' => 'form-control input-box'],
            ])
            ->add('wishedSize', null, [
                'label' => "Surface souhaitée en m2",
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'step' => 1
                ]
            ])
            ->add('description', TextareaType::class, [
                'label'=>"Présentation du projet",
                'attr' => [
                    'class'=>'form-control textarea-box',
                    'rows'=> 6
                ]
            ])
            ->add('localUsageDescription', TextareaType::class, [
                'label' => "Quel sera l'usage du local ?",
                'required' => false,
                'attr' => [
                    'class' => 'form-control textarea-box',
                    'rows' => 6
                ]
            ])
            ->add('contribution', null, [
                'required' => false,
                'label'=>"Quelles idées avez-vous pour participer au projet collectif ?",
                'attr' => ['class' => 'form-control textarea-box', 'rows'=> 6],
            ])
            ->add(
                'startOccupation',
                \Symfony\Component\Form\Extension\Core\Type\DateType::class,
                [
                    'label'=>"Date d'entrée souhaitée",
                    'input'  => 'datetime', // 'datetime' is the default !
                    'widget'=>'single_text',
                    'attr' => ['class' => 'form-control']
                ]
            )
            ->add(
                'lengthOccupation',
                null,
                [
                    'label'=>"Durée d'occupation",
                    'attr' => ['class' => 'form-control']
                ]
            )
            ->add('openToGlobalProject', null, ['label' => "Je suis ouvert(e) à faire parti(e) d'un projet collectif "])
            ->add(
                'lengthTypeOccupation',
                ChoiceType::class,
                [
                    'choices' => Application::getAllLengthType(),
                    'label' => "Durée d'occupation"
                ]
            )
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => "Type d'usage",
                'required' => true,
                'placeholder' => 'Sélectionnez un type d\'usage',
                'attr' => ['class' => 'form-control input-box'],
                'query_builder' => function (EntityRepository $repo) use ($application) {
                    $space = ($application instanceof Application) ? $application->getSpace() : null;

                    // AAC multi-lieux : tous les types d'usage actifs (y compris ERP).
                    $showAllUsageTypes = $space instanceof Space && $space->isMultiLocation();

                    $spaceIsErp = false;
                    if (!$showAllUsageTypes && $space instanceof Space && method_exists($space, 'getIsErp')) {
                        $spaceIsErp = (bool) $space->getIsErp();
                    }

                    $qb = $repo->createQueryBuilder('c')
                        ->where('c.isActive = :active')
                        ->setParameter('active', true);

                    if (!$showAllUsageTypes && !$spaceIsErp) {
                        $qb->andWhere('c.requiresErp = :notErp')
                           ->setParameter('notErp', false);
                    }

                    // On conserve la valeur actuellement sélectionnée même si elle est
                    // désormais archivée ou réservée ERP, pour ne pas perdre la donnée.
                    if ($application instanceof Application && $application->getCategory() instanceof Category) {
                        $qb->orWhere('c.id = :current')
                           ->setParameter('current', $application->getCategory()->getId());
                    }

                    return $qb->orderBy('c.name', 'ASC');
                },
            ])
            ->add('newDocument', ApplicationFileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false
            ])
        ;

        $projectHolderForm = $builder->get('projectHolder');

        // Ces champs du profil ne sont pas exposés dans le formulaire de candidature.
        // IMPORTANT : on utilise setDisabled(true) plutôt que remove() pour les champs
        // mappés sur l'entité User. remove() + clearMissing=true (défaut Symfony) viderait
        // la propriété User à la soumission ; setDisabled() la préserve.
        foreach (['usageDate', 'usageDuration', 'preferredDepartments',
                  'wishedSize', 'lengthTypeOccupation', 'projectDescription',
                  'monthlyBudgetMax', 'newsletter'] as $profileOnlyField) {
            if ($projectHolderForm->has($profileOnlyField)) {
                $projectHolderForm->get($profileOnlyField)->setDisabled(true);
            }
        }

        // useType : désactivé en amont via l'option `disable_use_type` passée à ProjectOwnerType
        // (le champ est ajouté dynamiquement dans PRE_SET_DATA, donc il n'est pas accessible ici).

        if ($user->getId()) {
            $projectHolderForm->get('userInfo')->remove('plainPassword');
        }

        $projectHolderForm->get('userInfo')->remove('oldPassword');

        // IMPORTANT (UX + sécurité des données):
        // Quand certaines sections issues du profil sont affichées en "récap" (non éditables) sur la page apply,
        // les champs ne sont plus postés. Symfony "clear" alors les champs manquants et peut vider le profil.
        // On désactive explicitement ces sous-champs pour qu'ils ne soient ni soumis ni modifiés.
        if (!empty($options['freeze_profile_sections'])) {
            if ($projectHolderForm->has('userInfo')) {
                $projectHolderForm->get('userInfo')->setDisabled(true);
            }
            if ($projectHolderForm->has('companyInfo')) {
                $projectHolderForm->get('companyInfo')->setDisabled(true);
            }

            foreach (['facebookUrl', 'twitterUrl', 'instagramUrl', 'googleUrl', 'linkedinUrl', 'youtubeUrl', 'tiktokUrl', 'otherUrl'] as $socialField) {
                if ($projectHolderForm->has($socialField)) {
                    $projectHolderForm->get($socialField)->setDisabled(true);
                }
            }

            // Documents de profil éventuellement présents dans le form (cas profil incomplet)
            foreach (['idcard', 'kbis'] as $docField) {
                if ($projectHolderForm->has($docField)) {
                    $projectHolderForm->get($docField)->setDisabled(true);
                }
            }
        }

        foreach ($application->getSpace()->getDocuments() as $field) {
            if ($application->hasFileType($field->getId())) {
                continue;
            }

            $builder->add(
              'document_' . $field->getId(),
              ApplicationFileType::class,
              [
              'label' => false,
              'mapped' => false,
              'required' => ($application->hasFileType($field->getId()) ? false : true)
            ]
          );
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $application = $event->getData();

            if (!$application instanceof Application) {
                return;
            }

            $space = $application->getSpace();
            $activeLocations = ($space instanceof Space) ? $space->getActiveLocations() : [];
            if (!$space instanceof Space || !$space->isMultiLocation() || count($activeLocations) < 2) {
                if ($form->has('locationPreferences')) {
                    $form->remove('locationPreferences');
                }

                return;
            }

            $application->syncLocationPreferencesFromSpace();
            $application->sortLocationPreferencesByRank();

            if (!$form->has('locationPreferences')) {
                $form->add('locationPreferences', CollectionType::class, [
                    'entry_type' => ApplicationLocationPreferenceType::class,
                    'entry_options' => [
                        'locations' => $activeLocations,
                    ],
                    'label' => false,
                    'by_reference' => false,
                    'allow_add' => false,
                    'allow_delete' => false,
                ]);
            }
        });

        $builder->add('save', SubmitType::class, [
            'label' => 'Enregistrer en brouillon',
            'attr' => [
                'class' => 'btn btn-default-color submit_form',
                'value' => 'Enregistrer en brouillon'
            ]
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Soumettre ma candidature',
            'attr' => [
                'class' => 'btn btn-fullcolor submit_form',
                'value' => 'Soumettre ma candidature'
            ]
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($user): void {
            $application = $event->getData();
            if (!$application instanceof Application || !$user instanceof User) {
                return;
            }

            $holder = $application->getProjectHolder();
            if (!$holder instanceof User) {
                $application->setProjectHolder($user);

                return;
            }

            if ($holder->getId() === null && $user->getId() !== null) {
                $application->setProjectHolder($user);
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $application = $event->getData();

            if ($application instanceof Application && $form->has('locationPreferences')) {
                foreach ($form->get('locationPreferences') as $childForm) {
                    $preference = $childForm->getData();
                    if ($preference instanceof \App\Entity\ApplicationLocationPreference) {
                        $preference->setApplication($application);
                    }
                }
            }

            // Bloquer les emojis côté serveur (la DB n'accepte pas utf8mb4 sur certains champs).
            // Sinon on obtient une exception SQL "Incorrect string value" à la soumission.
            $emojiRegex = '/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u';
            $textFields = [
                'description' => "Le texte ne doit pas contenir d'emojis",
                'localUsageDescription' => "Le texte ne doit pas contenir d'emojis",
                'contribution' => "Le texte ne doit pas contenir d'emojis",
            ];
            foreach ($textFields as $fieldName => $message) {
                if (!$form->has($fieldName)) {
                    continue;
                }
                $value = $form->get($fieldName)->getData();
                if (is_string($value) && $value !== '' && preg_match($emojiRegex, $value)) {
                    $form->get($fieldName)->addError(new FormError($message));
                }
            }

            $document = $event->getForm()->get('newDocument')->getData();
            if ($document instanceof ApplicationFile) {
                $document->setApplication($application);
                $application->addFile($document);
            }


            foreach ($application->getSpace()->getDocuments() as $field) {
                if ($application->hasFileType($field->getId())) {
                    continue;
                }

                $document = $event->getForm()->get('document_' . $field->getId())->getData();

                // Vérifier si le document est obligatoire et manquant lors de la soumission
                $isSubmitClicked = $event->getForm()->get('submit')->isClicked();
                $hasExistingFile = $application->hasFileType($field->getId());
                $documentProvided = ($document instanceof ApplicationFile) && ($document->getFile() !== null);
                
                if ($isSubmitClicked && !$hasExistingFile && !$documentProvided) {
                    $event->getForm()->get('document_' . $field->getId())->addError(new FormError('Le document ' . $field->getName() . ' est obligatoire'));
                } else {
                    if ($document instanceof ApplicationFile) {
                        $document->setApplication($application);
                        $document->setSpaceDocument($field);

                        if ($application->hasFileType($field->getId())) {
                            $currentDocument = $application->getFilesType($field->getId())[0];
                            $currentDocument->setFile($document->getFile());
                            $currentDocument->setFileName($document->getFileName());
                        } else {
                            if ($document->getFile()) {
                                $application->addFile($document);
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Application::class,
            'validation_groups' => function (FormInterface $form) {
                $intent = '';
                if ($form->has('intent')) {
                    $intent = (string) $form->get('intent')->getData();
                }

                if ($form->get('save')->isClicked() || $intent === 'save') {
                    // Pour l'enregistrement en brouillon, on désactive la validation
                    return [];
                }

                return ['submit', 'projectHolder', 'Default'];
            },
            'user' => \App\Entity\User::class,
            // True = sections "profil" en lecture seule dans apply (évite d'écraser le profil).
            'freeze_profile_sections' => false,
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
        return 'appbundle_application';
    }
}
