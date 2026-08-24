<?php

namespace App\Admin;

use App\Entity\ApplicationFile;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Fichiers joints d'une candidature — édition inline Sonata.
 *
 * @extends AbstractAdmin<ApplicationFile>
 */
class ApplicationFileAdmin extends AbstractAdmin
{
    private const SORT_CHOICES = [
        'Structure (A → Z)' => 'company_asc',
        'Structure (Z → A)' => 'company_desc',
        'Email (A → Z)' => 'email_asc',
        'Email (Z → A)' => 'email_desc',
        'Espace (A → Z)' => 'space_asc',
        'Espace (Z → A)' => 'space_desc',
    ];

    /** @var array<string, array{0: string, 1: string}> */
    private const SORT_MAP = [
        'company_asc' => ['application.projectHolder.company', 'ASC'],
        'company_desc' => ['application.projectHolder.company', 'DESC'],
        'email_asc' => ['application.projectHolder.email', 'ASC'],
        'email_desc' => ['application.projectHolder.email', 'DESC'],
        'space_asc' => ['application.space.name', 'ASC'],
        'space_desc' => ['application.space.name', 'DESC'],
    ];

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'application.projectHolder.company';
        $sortValues[DatagridInterface::SORT_ORDER] = 'ASC';
    }

    /**
     * Allows mapping a dedicated "sortBy" filter choice to Sonata's internal
     * `_sort_by` / `_sort_order` parameters.
     *
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

        // Help Doctrine build consistent joins for nested sorting / list links.
        $query->leftJoin($alias.'.application', 'a')->addSelect('a');
        $query->leftJoin('a.projectHolder', 'ph')->addSelect('ph');
        $query->leftJoin('a.space', 's')->addSelect('s');

        return $query;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('fileName', null, [
                'label' => 'Fichier',
                'template' => 'Admin/ApplicationFile/list_file_name.html.twig',
            ])
            ->add('application.projectHolder.company', null, [
                'label' => 'Structure',
                'sortable' => true,
                'sort_parent_association_mappings' => [['fieldName' => 'application'], ['fieldName' => 'projectHolder']],
                'sort_field_mapping' => ['fieldName' => 'company'],
            ])
            ->add('application.projectHolder.email', null, [
                'label' => 'Email',
                'sortable' => true,
                'sort_parent_association_mappings' => [['fieldName' => 'application'], ['fieldName' => 'projectHolder']],
                'sort_field_mapping' => ['fieldName' => 'email'],
                'template' => 'Admin/ApplicationFile/list_project_holder_email.html.twig',
            ])
            ->add('application.space.name', null, [
                'label' => 'Espace',
                'sortable' => true,
                'sort_parent_association_mappings' => [['fieldName' => 'application'], ['fieldName' => 'space']],
                'sort_field_mapping' => ['fieldName' => 'name'],
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'download' => ['template' => 'Admin/ApplicationFile/list__action_download.html.twig'],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureDatagridFilters(\Sonata\AdminBundle\Datagrid\DatagridMapper $filter): void
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
            ->add('application.projectHolder.company', null, ['label' => 'Structure'])
            ->add('application.projectHolder.email', null, ['label' => 'Email'])
            ->add('application.space.name', null, ['label' => 'Espace']);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper->add('file', VichFileType::class, [
            'label' => 'Document',
            'required' => false,
            'download_uri' => true,
            'download_label' => static function ($file): string {
                if ($file instanceof ApplicationFile && $file->getFileName()) {
                    return (string) $file->getFileName();
                }

                return 'Télécharger';
            },
            'allow_delete' => true,
            'delete_label' => 'Supprimer le fichier',
            'asset_helper' => true,
        ]);
    }
}
