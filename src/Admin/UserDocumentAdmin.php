<?php

namespace App\Admin;

use App\Entity\Application;
use App\Entity\UserDocument;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * Documents utilisateur (KBIS, pièce d'identité) — édition inline Sonata.
 *
 * @extends AbstractAdmin<UserDocument>
 */
class UserDocumentAdmin extends AbstractAdmin
{
    private const SORT_CHOICES = [
        'Structure (A → Z)' => 'company_asc',
        'Structure (Z → A)' => 'company_desc',
        'Email (A → Z)' => 'email_asc',
        'Email (Z → A)' => 'email_desc',
    ];

    /** @var array<string, array{0: string, 1: string}> */
    private const SORT_MAP = [
        'company_asc' => ['projectHolder.company', 'ASC'],
        'company_desc' => ['projectHolder.company', 'DESC'],
        'email_asc' => ['projectHolder.email', 'ASC'],
        'email_desc' => ['projectHolder.email', 'DESC'],
    ];

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'projectHolder.company';
        $sortValues[DatagridInterface::SORT_ORDER] = 'ASC';
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    protected function configureFilterParameters(array $parameters): array
    {
        $sortValue = $parameters['sortBy']['value'] ?? null;
        if (\is_string($sortValue) && isset(self::SORT_MAP[$sortValue])) {
            [$sortBy, $sortOrder] = self::SORT_MAP[$sortValue];
            $parameters[DatagridInterface::SORT_BY] = $sortBy;
            $parameters[DatagridInterface::SORT_ORDER] = $sortOrder;
        }

        return $parameters;
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        assert($query instanceof ProxyQuery);
        $alias = $query->getRootAliases()[0];
        $query->leftJoin($alias.'.projectHolder', 'ph')->addSelect('ph');

        return $query;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('fileName', null, [
                'label' => 'Fichier',
                'template' => 'Admin/UserDocument/list_file_name.html.twig',
            ])
            ->add('type', FieldDescriptionInterface::TYPE_CHOICE, [
                'label' => 'Type',
                'choices' => [
                    UserDocument::KBIS_TYPE => 'Extrait KBIS',
                    UserDocument::ID_TYPE => 'Pièce d\'identité',
                    UserDocument::NO_TYPE => 'Non renseigné',
                ],
            ])
            ->add('projectHolder.company', null, [
                'label' => 'Structure',
                'sortable' => true,
                'sort_parent_association_mappings' => [['fieldName' => 'projectHolder']],
                'sort_field_mapping' => ['fieldName' => 'company'],
            ])
            ->add('projectHolder.email', null, [
                'label' => 'Email',
                'sortable' => true,
                'sort_parent_association_mappings' => [['fieldName' => 'projectHolder']],
                'sort_field_mapping' => ['fieldName' => 'email'],
                'template' => 'Admin/list_project_holder_link.html.twig',
            ])
            ->add('spaceNames', null, [
                'label' => 'Espace',
                'sortable' => false,
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'download' => ['template' => 'Admin/UserDocument/list__action_download.html.twig'],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('sortBy', CallbackFilter::class, [
                'label' => 'Trier par',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    return $data->hasValue() && $data->getValue() !== null && $data->getValue() !== '';
                },
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => self::SORT_CHOICES,
                    'placeholder' => 'Choisir',
                ],
            ])
            ->add('projectHolder.company', null, ['label' => 'Structure'])
            ->add('projectHolder.email', null, ['label' => 'Email'])
            ->add('spaceName', CallbackFilter::class, [
                'label' => 'Espace',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue() || $data->getValue() === null || $data->getValue() === '') {
                        return false;
                    }

                    assert($query instanceof ProxyQuery);
                    $query->andWhere($query->expr()->exists(
                        'SELECT 1 FROM '.Application::class.' a_space'
                        .' JOIN a_space.space s_space'
                        .' WHERE a_space.projectHolder = '.$alias.'.projectHolder'
                        .' AND s_space.name LIKE :spaceName'
                    ));
                    $query->setParameter('spaceName', '%'.$data->getValue().'%');

                    return true;
                },
            ])
            ->add('type', ChoiceFilter::class, [
                'label' => 'Type',
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        'Extrait KBIS' => UserDocument::KBIS_TYPE,
                        'Pièce d\'identité' => UserDocument::ID_TYPE,
                    ],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        if (!$this->hasParentFieldDescription()) {
            $formMapper->add('projectHolder', ModelListType::class, [
                'label' => 'Utilisateur',
                'required' => false,
                'btn_add' => false,
            ], [
                'admin_code' => 'app.admin.user',
            ]);
        }

        $formMapper
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'kbis' => UserDocument::KBIS_TYPE,
                    'id' => UserDocument::ID_TYPE,
                ],
                'required' => true,
            ])
            ->add('file', VichFileType::class, [
                'label' => 'Document',
                'required' => false,
                'download_uri' => true,
                'download_label' => static function ($document): string {
                    if ($document instanceof UserDocument && $document->getFileName()) {
                        return (string) $document->getFileName();
                    }

                    return 'Télécharger';
                },
                'allow_delete' => true,
                'delete_label' => 'Supprimer le fichier',
                'asset_helper' => true,
            ]);
    }
}
