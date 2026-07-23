<?php

namespace App\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use App\Entity\Space;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Repository\UserRepository;
use App\Form\SpaceDocumentType;
use App\Form\SpaceImageType;
use App\Form\SpaceVisitType;
use App\Form\SpaceDocAdminType;
use App\Form\SpaceLocationType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/** @extends AbstractAdmin<Space> */
class SpaceAdmin extends AbstractAdmin
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
        $collection->add('select_export_fields', 'select-export-fields');
        $collection->add('custom_export', 'custom-export');
    }

    protected function configure(): void
    {
        $this->setTemplates([
            'outer_list_rows_list' => 'Admin/Space/list_outer_rows_list.html.twig',
        ]);
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        assert($query instanceof ProxyQuery);
        $alias = $query->getRootAliases()[0];
        $query->leftJoin($alias.'.owner', 'space_owner')->addSelect('space_owner');
        $query->leftJoin($alias.'.type', 'space_type')->addSelect('space_type');

        return $query;
    }

    /** @param iterable<\App\Entity\SpaceImage|\App\Entity\SpaceDocument|\App\Entity\SpaceVisit> $children */
    public function syncSpace(\App\Entity\Space $space, iterable $children): void
    {
        $pos = 0;
        foreach ($children as $child) {
            $child->setSpace($space);
            if ($child instanceof \App\Entity\SpaceImage) {
                if (!$child->getFileType()) {
                    $child->setFileType(\App\Entity\SpaceImage::FILETYPE_IMAGE);
                }
                if ($child->getPosition() === null) {
                    $child->setPosition($pos);
                }
                if ($child->getUpdatedAt() === null) {
                    $child->setUpdatedAt(new \DateTime());
                }
            }
            $pos++;
        }
    }
    public function prePersist(object $object): void
    {
        $this->syncSpace($object, $object->getPics());
        $this->syncSpace($object, $object->getDocuments());
        $this->syncSpace($object, $object->getVisits());
        $this->syncSpace($object, $object->getLocations());
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(object $object): void
    {
        $this->syncSpace($object, $object->getPics());
        $this->syncSpace($object, $object->getDocuments());
        $this->syncSpace($object, $object->getVisits());
        $this->syncSpace($object, $object->getLocations());
    }

    /**
     * {@inheritdoc}
     * Supprime les applications associées avant de supprimer l'espace
     */
    public function preRemove(object $object): void
    {
        $em = $this->em;

        $applications = $em->getRepository(\App\Entity\Application::class)->findBy(['space' => $object]);

        foreach ($applications as $application) {
            $applicationFiles = $em->getRepository(\App\Entity\ApplicationFile::class)->findBy(['application' => $application]);
            foreach ($applicationFiles as $file) {
                $em->remove($file);
            }
            $em->remove($application);
        }
        // Pas de flush() ici : Sonata commitera tout (fichiers + candidatures + espace)
        // en une seule transaction atomique via son propre flush().
    }

    protected $baseRouteName = 'property';
    protected $baseRoutePattern = 'property';

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'created';
    }

    public function getExportFormats(): array
    {
        return ['csv', 'xls'];
    }

    /** @return array<string, array{label: string, property: string, category: string}> */
    public static function getExportFieldDefinitions(): array
    {
        return [
            'id' => [
                'label' => 'Identifiant',
                'property' => 'id',
                'category' => 'Informations générales',
            ],
            'name' => [
                'label' => "Nom de l'espace",
                'property' => 'name',
                'category' => 'Informations générales',
            ],
            'managedByLabel' => [
                'label' => 'Texte du badge',
                'property' => 'managedByLabel',
                'category' => 'Informations générales',
            ],
            'workflowType' => [
                'label' => 'Type de workflow',
                'property' => 'computed.workflowTypeLabel',
                'category' => 'Informations générales',
            ],
            'created' => [
                'label' => 'Date de création',
                'property' => 'created',
                'category' => 'Informations générales',
            ],
            'updated' => [
                'label' => 'Date de mise à jour',
                'property' => 'updated',
                'category' => 'Informations générales',
            ],
            'submittedAt' => [
                'label' => 'Date de soumission',
                'property' => 'submittedAt',
                'category' => 'Informations générales',
            ],
            'owner' => [
                'label' => 'Propriétaire',
                'property' => 'computed.ownerLabel',
                'category' => 'Propriétaire',
            ],
            'owner_company' => [
                'label' => 'Structure du propriétaire',
                'property' => 'owner.company',
                'category' => 'Propriétaire',
            ],
            'owner_email' => [
                'label' => 'Email du propriétaire',
                'property' => 'owner.email',
                'category' => 'Propriétaire',
            ],
            'owner_phone' => [
                'label' => 'Téléphone du propriétaire',
                'property' => 'owner.phone',
                'category' => 'Propriétaire',
            ],
            'address' => [
                'label' => 'Adresse',
                'property' => 'address',
                'category' => 'Localisation',
            ],
            'zipCode' => [
                'label' => 'Code postal',
                'property' => 'zipCode',
                'category' => 'Localisation',
            ],
            'city' => [
                'label' => 'Ville',
                'property' => 'city',
                'category' => 'Localisation',
            ],
            'type' => [
                'label' => 'Type de locaux',
                'property' => 'type',
                'category' => 'Caractéristiques',
            ],
            'isErp' => [
                'label' => 'ERP (Établissement Recevant du Public)',
                'property' => 'computed.yesno.isErp',
                'category' => 'Caractéristiques',
            ],
            'description' => [
                'label' => 'Description',
                'property' => 'description',
                'category' => 'Description',
            ],
            'activityDescription' => [
                'label' => 'Activités recherchées',
                'property' => 'activityDescription',
                'category' => 'Description',
            ],
            'locationDescription' => [
                'label' => 'Description du lieu',
                'property' => 'locationDescription',
                'category' => 'Description',
            ],
            'usageRestriction' => [
                'label' => 'Restrictions d\'usage',
                'property' => 'usageRestriction',
                'category' => 'Description',
            ],
            'surface' => [
                'label' => 'Surface totale (m²)',
                'property' => 'surface',
                'category' => 'Caractéristiques',
            ],
            'nbSpaces' => [
                'label' => "Nombre d'espaces",
                'property' => 'nbSpaces',
                'category' => 'Caractéristiques',
            ],
            'minSpace' => [
                'label' => 'Surface minimale (m²)',
                'property' => 'minSpace',
                'category' => 'Caractéristiques',
            ],
            'maxSpace' => [
                'label' => 'Surface maximale (m²)',
                'property' => 'maxSpace',
                'category' => 'Caractéristiques',
            ],
            'availability' => [
                'label' => 'Durée du projet',
                'property' => 'availability',
                'category' => 'Caractéristiques',
            ],
            'limitAvailability' => [
                'label' => 'Date limite de candidature',
                'property' => 'limitAvailability',
                'category' => 'Caractéristiques',
            ],
            'price' => [
                'label' => 'Prix au m² mensuel',
                'property' => 'price',
                'category' => 'Caractéristiques',
            ],
            'priceText' => [
                'label' => 'Prix personnalisé (texte libre)',
                'property' => 'priceText',
                'category' => 'Caractéristiques',
            ],
            'rollingApplications' => [
                'label' => 'Candidatures au fil de l\'eau',
                'property' => 'computed.yesno.rollingApplications',
                'category' => 'Caractéristiques',
            ],
            'enabled' => [
                'label' => 'En ligne',
                'property' => 'computed.yesno.enabled',
                'category' => 'Publication',
            ],
            'submitted' => [
                'label' => 'Soumis',
                'property' => 'computed.yesno.submitted',
                'category' => 'Publication',
            ],
            'closed' => [
                'label' => 'Clôturé',
                'property' => 'computed.yesno.closed',
                'category' => 'Publication',
            ],
        ];
    }

    protected function configureExportFields(): array
    {
        $fields = [];
        foreach (self::getExportFieldDefinitions() as $definition) {
            $fields[$definition['label']] = self::mapExportPropertyForStandardExport($definition['property']);
        }

        return $fields;
    }

    private static function mapExportPropertyForStandardExport(string $property): string
    {
        return match ($property) {
            'computed.workflowTypeLabel' => 'workflowType',
            'computed.ownerLabel' => 'owner.company',
            'computed.yesno.isErp' => 'isErp',
            'computed.yesno.rollingApplications' => 'rollingApplications',
            'computed.yesno.enabled' => 'enabled',
            'computed.yesno.submitted' => 'submitted',
            'computed.yesno.closed' => 'closed',
            default => $property,
        };
    }

    protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
    {
        $buttonList = parent::configureActionButtons($buttonList, $action, $object);

        if ($action === 'list') {
            $buttonList['export_custom'] = [
                'template' => 'Admin/button_export_custom.html.twig',
            ];
        }

        return $buttonList;
    }

    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = static function (FormInterface $form): array {
            $space = $form->getData();
            $groups = ['Default', 'save'];

            if ($space instanceof Space && $space->isMultiLocation()) {
                $groups[] = 'multi_location';
            } else {
                $groups[] = 'standard';
            }

            return $groups;
        };
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->with('General')
            ->add('workflowType', ChoiceType::class, [
                'label' => 'Type d\'Appel à candidature',
                'choices' => [
                    'AAC Mono-site (Standard)' => Space::WORKFLOW_STANDARD,
                    'AAC Multi-sites' => Space::WORKFLOW_MULTI_LOCATION,
                ],
                'required' => true,
                'help' => 'AAC Mono-site (1 seul lieu) ou AAC Multi-sites (plusieurs lieux/secteurs).',
            ])
            ->add('name', null, ['label' => "Nom de l'espace"])
            ->add('managedByLabel', null, [
                'label' => "Texte du badge (ex: Géré, Commercialisé...)",
                'required' => false,
                'help' => 'Par défaut: Géré'
            ])
            ->add('owner', null, [
                'required' => true,
                'query_builder' => fn (UserRepository $repository) => $repository->createProprietairesQueryBuilder(),
            ], [
                'label' => "Propriétaire de l'espace",
                'admin_code' => 'app.admin.owner',
            ])
            ->add('zipCode', null, ['label' => 'Code postal', 'required' => false])
            ->add('city', null, ['label' => 'Ville', 'required' => false])
            ->add('limitAvailability', null, ['label' => 'Date limite de candidature', 'required' => false])
            ->add('availability', null, ['label' => 'Durée du projet', 'required' => false])
            ->add('type', null, ['label' => 'Type de locaux', 'required' => true])
            ->add('description', null, ['label' => 'Description', 'attr' => ['class' => 'trumbowyg']])
            ->add('activityDescription', null, ['label' => 'Activités recherchées', 'attr' => ['class' => 'trumbowyg']])
            ->add('price', null, ['label' => 'Prix au m² mensuel', 'required' => false])
            ->add('priceText', null, ['label' => 'Prix personnalisé (texte libre)', 'required' => false])
            ->add('nbSpaces', null, ['label' => "Nombre d'espaces", 'required' => false])
            ->add('minSpace', null, ['label' => 'Surface minimale (m²)', 'required' => false])
            ->add('maxSpace', null, ['label' => 'Surface maximale (m²)', 'required' => false])
            ->end();

        $formMapper->with('Secteurs / Lieux (AAC Multi-sites)')
                ->add('locations', CollectionType::class, [
                    'entry_type' => SpaceLocationType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'by_reference' => false,
                    'label' => 'Secteurs / Lieux rattachés à cet AAC (à renseigner pour un AAC multi-sites)',
                ], [
                    'edit' => 'inline',
                    'inline' => 'table',
                ])
            ->end()

            ->with('Photos')

            ->add('pics', CollectionType::class, [
                    'entry_type' => SpaceImageType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'by_reference' => false,
                    'label' => 'Photos',
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                ])

            ->end()
            ->with('Documents PDF (propriétaire)')

            ->add('docAac', SpaceDocAdminType::class, [
                'label'     => "Appel à candidature (PDF)",
                'required'  => false,
                'file_type' => 'document_aac',
            ])
            ->add('docPlan', SpaceDocAdminType::class, [
                'label'     => "Répartition des espaces (PDF)",
                'required'  => false,
                'file_type' => 'document_plan',
            ])
            ->add('docFaq', SpaceDocAdminType::class, [
                'label'     => "F.A.Q (PDF)",
                'required'  => false,
                'file_type' => 'document_faq',
            ])

            ->end()
            ->with('Documents requis (candidature)')

            ->add('documents', CollectionType::class, [
                    'entry_type' => SpaceDocumentType::class,
                    'by_reference' => false,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'label' => false
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                ])

            ->end()

            ->with('Visites programmées')

            ->add('visits', CollectionType::class, [
                    'entry_type' => SpaceVisitType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'by_reference' => false,
                    'label' => false,
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                ])

            ->end()

            ->with('Publication')
                ->add('enabled', ChoiceType::class, ['label' => 'En ligne', 'required' => false, 'choices' => ['Oui' => true, 'Non' => false], 'placeholder' => false])
                ->add('closed', ChoiceType::class, ['label' => 'Clôturé', 'required' => false, 'choices' => ['Oui' => true, 'Non' => false], 'placeholder' => false])
            ->end()

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name', null, ['label' => 'Nom'])
            ->add('owner', null, [
                'label' => 'Propriétaire',
                'admin_code' => 'app.admin.owner',
                'associated_property' => 'company',
            ])
            ->add('city', null, ['label' => 'Ville'])
            ->add('type', null, ['label' => 'Type de locaux'])
            ->add('enabled', null, ['label' => 'En ligne'])
            ->add('submitted', null, ['label' => 'Soumis'])
            ->add('closed', null, ['label' => 'Clôturé'])
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name', null, [
                'label' => 'Nom',
                'route' => ['name' => 'edit'],
            ])
            ->add('owner', null, [
                'label' => 'Propriétaire',
                'admin_code' => 'app.admin.owner',
                'associated_property' => 'company',
            ])
            ->add('city', null, ['label' => 'Ville'])
            ->add('limitAvailability', null, [
                'label' => 'Date limite de candidature',
                'template' => 'Admin/Space/list_limit_availability.html.twig',
                'sortable' => true,
            ])
            ->add('enabled', null, ['label' => 'En ligne'])
            ->add('submitted', null, ['label' => 'Soumis'])
            ->add('closed', null, ['label' => 'Clôturé'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => [
                    'edit'   => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->with('General')
                ->add('name', null, ['label' => "Nom de l'espace"])
                ->add('owner', null, [
                    'label' => "Propriétaire de l'espace",
                    'admin_code' => 'app.admin.owner',
                    'associated_property' => 'company',
                ])
                ->add('zipCode', null, ['label' => 'Code postal'])
                ->add('city', null, ['label' => 'Ville'])
                ->add('address', null, ['label' => 'Adresse'])
                ->add('limitAvailability', null, ['label' => 'Date limite de candidature'])
                ->add('availability', null, ['label' => 'Durée du projet'])
                ->add('type', null, ['label' => 'Type de locaux'])
                ->add('description', null, ['label' => 'Description'])
                ->add('activityDescription', null, ['label' => 'Activités recherchées'])
                ->add('price', null, ['label' => 'Prix au m² mensuel'])
                ->add('priceText', null, ['label' => 'Prix personnalisé (texte libre)'])
                ->add('nbSpaces', null, ['label' => "Nombre d'espaces"])
                ->add('minSpace', null, ['label' => 'Surface minimale (m²)'])
                ->add('maxSpace', null, ['label' => 'Surface maximale (m²)'])
            ->end()
            ->with('Prestations et services')
                ->add('tags', null, ['label' => 'Attributs', 'associated_property' => 'id'])
            ->end()
            ->with('Lots')
                ->add('parcels', null, ['label' => 'Lots', 'associated_property' => 'id'])
            ->end()
            ->with('Photos')
                ->add('pics', null, ['label' => 'Photos', 'associated_property' => 'id'])
            ->end()
            ->with('Documents')
                ->add('documents', null, ['label' => 'Documents requis (candidature)', 'associated_property' => 'id'])
            ->end()
            ->with('Publication')
                ->add('enabled', null, ['label' => 'En ligne'])
                ->add('closed', null, ['label' => 'Clôturé'])
                ->add('submitted', null, ['label' => 'Soumis'])
            ->end();
    }

    

    
}
