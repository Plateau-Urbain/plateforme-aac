<?php

namespace App\Controller\Admin;

use App\Admin\UserAdmin;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\Exporter\Writer\CsvWriter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/** @extends CRUDController<User> */
class UtilisateursAdminController extends CRUDController
{
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(private EntityManagerInterface $em)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function selectExportFieldsAction(Request $request): Response
    {
        $availableFields = $this->getAllAvailableFields();
        $filterParameters = $request->query->all();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('export_fields', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $selectedFields = array_values(array_filter(
                $request->request->all('fields'),
                static fn (mixed $field): bool => is_string($field) && $field !== ''
            ));

            if (empty($selectedFields)) {
                $this->addFlash('sonata_flash_error', 'Veuillez sélectionner au moins un champ à exporter.');
                return $this->renderWithExtraParams('Admin/Utilisateurs/select_export_fields.html.twig', [
                    'availableFields' => $availableFields,
                    'presetExportFieldKeys' => $this->getPresetExportFieldKeys(),
                    'action' => 'list',
                    'filterParameters' => $filterParameters,
                ]);
            }

            $selectedFields = $this->sortSelectedExportFieldKeys($selectedFields);

            $exportParams = array_merge(
                ['fields' => implode(',', $selectedFields)],
                $filterParameters
            );

            return $this->redirect($this->admin->generateUrl('custom_export', $exportParams));
        }

        return $this->renderWithExtraParams('Admin/Utilisateurs/select_export_fields.html.twig', [
            'availableFields' => $availableFields,
            'presetExportFieldKeys' => $this->getPresetExportFieldKeys(),
            'action' => 'list',
            'filterParameters' => $filterParameters,
        ]);
    }

    public function customExportAction(Request $request): Response
    {
        $fieldsParam = $request->query->getString('fields');
        $selectedFieldKeys = array_values(array_filter(
            explode(',', $fieldsParam),
            static fn (string $key): bool => $key !== ''
        ));

        if (empty($selectedFieldKeys)) {
            $this->addFlash('sonata_flash_error', 'Aucun champ sélectionné pour l\'export.');
            return $this->redirectToList();
        }

        $selectedFieldKeys = $this->sortSelectedExportFieldKeys($selectedFieldKeys);
        $allFields = $this->getAllAvailableFields();

        $exportFields = [];
        foreach ($selectedFieldKeys as $key) {
            if (isset($allFields[$key])) {
                $exportFields[$allFields[$key]['label']] = $allFields[$key]['property'];
            }
        }

        $hasComputedFields = false;
        foreach ($exportFields as $property) {
            if (str_starts_with($property, 'computed.')) {
                $hasComputedFields = true;
                break;
            }
        }

        $datagrid = $this->admin->getDatagrid();
        $this->applyDatagridFiltersFromRequest($datagrid, $request);

        $datagrid->getPager()->setMaxPerPage(PHP_INT_MAX);
        $datagrid->buildPager();

        if ($hasComputedFields) {
            $users = $datagrid->getResults();
            $headers = array_keys($exportFields);

            $callback = function () use ($users, $exportFields, $headers) {
                echo "\xEF\xBB\xBF";
                $rows = [];
                $rows[] = $headers;

                foreach ($users as $user) {
                    if (!$user instanceof User) {
                        continue;
                    }
                    $row = [];
                    foreach ($exportFields as $property) {
                        $row[] = (string) $this->resolveExportValue($user, $property);
                    }
                    $rows[] = $row;
                }

                foreach ($rows as $row) {
                    $escapedRow = array_map(static function ($cell) {
                        return '"' . str_replace('"', '""', (string) $cell) . '"';
                    }, $row);
                    echo implode(';', $escapedRow) . "\r\n";
                }
            };

            $filename = 'export_utilisateurs_' . date('Y-m-d_H-i-s') . '.csv';

            return new StreamedResponse($callback, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]);
        }

        $proxyQuery = $datagrid->getQuery();
        assert($proxyQuery instanceof \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery);
        $query = $proxyQuery->getQuery();
        $query->setMaxResults(null);
        $query->setFirstResult(0);
        $sourceIterator = new \Sonata\Exporter\Source\DoctrineORMQuerySourceIterator(
            $query,
            $exportFields,
            'd/m/Y H:i'
        );

        $writer = new CsvWriter('php://output');
        $filename = 'export_utilisateurs_' . date('Y-m-d_H-i-s') . '.csv';

        $callback = function () use ($sourceIterator, $writer) {
            $handler = \Sonata\Exporter\Handler::create($sourceIterator, $writer);
            $handler->export();
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /** @return array<string, array{label: string, property: string, category: string}> */
    private function getAllAvailableFields(): array
    {
        return UserAdmin::getExportFieldDefinitions();
    }

    /** @return list<string> */
    private function getPresetExportFieldKeys(): array
    {
        return [
            'id',
            'email',
            'civility',
            'firstname',
            'lastname',
            'typeUser',
            'enabled',
            'locked',
            'createdAt',
            'newsletter',
            'preferredDepartments',
            'company',
            'city',
            'zipcode',
        ];
    }

    /** @param list<string> $selectedFieldKeys @return list<string> */
    private function sortSelectedExportFieldKeys(array $selectedFieldKeys): array
    {
        $orderPreset = array_merge(
            ['id'],
            $this->getPresetExportFieldKeys(),
            array_diff(array_keys($this->getAllAvailableFields()), $this->getPresetExportFieldKeys())
        );
        $orderPreset = array_values(array_unique($orderPreset));

        usort($selectedFieldKeys, static function (string $a, string $b) use ($orderPreset): int {
            $indexA = array_search($a, $orderPreset, true);
            $indexB = array_search($b, $orderPreset, true);

            return ($indexA === false ? PHP_INT_MAX : (int) $indexA)
                <=> ($indexB === false ? PHP_INT_MAX : (int) $indexB);
        });

        return $selectedFieldKeys;
    }

    private function applyDatagridFiltersFromRequest(\Sonata\AdminBundle\Datagrid\DatagridInterface $datagrid, Request $request): void
    {
        $queryParams = $request->query->all();

        $convertDateArray = function ($dateArray) {
            if (is_string($dateArray)) {
                $decoded = json_decode($dateArray, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $dateArray = $decoded;
                } else {
                    return null;
                }
            }

            if (!is_array($dateArray)) {
                return null;
            }

            if (isset($dateArray['day'], $dateArray['month'], $dateArray['year'])) {
                $day = is_numeric($dateArray['day'] ?? 0) ? (int) ($dateArray['day'] ?? 0) : 0;
                $month = is_numeric($dateArray['month'] ?? 0) ? (int) ($dateArray['month'] ?? 0) : 0;
                $year = is_numeric($dateArray['year'] ?? 0) ? (int) ($dateArray['year'] ?? 0) : 0;

                if ($day > 0 && $month > 0 && $year > 0) {
                    try {
                        return new \DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
                    } catch (\Exception $e) {
                        return null;
                    }
                }
            }

            return null;
        };

        $filtersToApply = isset($queryParams['filter']) && is_array($queryParams['filter'])
            ? $queryParams['filter']
            : $queryParams;

        foreach ($filtersToApply as $filterName => $filterData) {
            if (in_array($filterName, ['_page', '_sort_by', '_sort_order', '_per_page', 'fields'], true)) {
                continue;
            }

            if (is_string($filterData)) {
                $decoded = json_decode($filterData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $filterData = $decoded;
                }
            }

            if (is_array($filterData) && !isset($filterData['value']) && !isset($filterData['type'])) {
                if (count($filterData) === 2 && isset($filterData[0], $filterData[1])) {
                    $date1 = $convertDateArray($filterData[0]);
                    $date2 = $convertDateArray($filterData[1]);

                    if ($date1 !== null || $date2 !== null) {
                        $filter = $datagrid->getFilter($filterName);
                        if ($filter) {
                            $filter->apply($datagrid->getQuery(), FilterData::fromArray([
                                'value' => [
                                    'start' => $date1,
                                    'end' => $date2,
                                ],
                            ]));
                        }
                        continue;
                    }
                }
            }

            if (is_array($filterData) && isset($filterData['value'])) {
                $value = $filterData['value'];

                if (is_string($value) || is_numeric($value)) {
                    if ($value !== '') {
                        $filter = $datagrid->getFilter($filterName);
                        if ($filter) {
                            $filter->apply($datagrid->getQuery(), FilterData::fromArray(['value' => $filterData['value'] ?? null]));
                        }
                    }
                } elseif (is_array($value)) {
                    $hasValidValue = false;
                    $normalizedValue = $value;

                    if (isset($value['start']) && is_array($value['start'])) {
                        $startDate = $convertDateArray($value['start']);
                        $endDate = isset($value['end']) && is_array($value['end']) ? $convertDateArray($value['end']) : null;

                        if ($startDate !== null || $endDate !== null) {
                            $hasValidValue = true;
                            $normalizedValue = [
                                'start' => $startDate,
                                'end' => $endDate,
                            ];
                        }
                    } elseif (isset($value['day'], $value['month'], $value['year'])) {
                        $date = $convertDateArray($value);
                        if ($date !== null) {
                            $hasValidValue = true;
                            $normalizedValue = $date;
                        }
                    } else {
                        if (count($value) === 2 && isset($value[0], $value[1])) {
                            $date1 = $convertDateArray($value[0]);
                            $date2 = $convertDateArray($value[1]);

                            if ($date1 !== null || $date2 !== null) {
                                $hasValidValue = true;
                                $normalizedValue = [
                                    'start' => $date1,
                                    'end' => $date2,
                                ];
                            }
                        } else {
                            foreach ($value as $v) {
                                if (!empty($v)) {
                                    $hasValidValue = true;
                                    break;
                                }
                            }
                        }
                    }

                    if ($hasValidValue) {
                        $filter = $datagrid->getFilter($filterName);
                        if ($filter) {
                            $filter->apply($datagrid->getQuery(), FilterData::fromArray(['value' => $normalizedValue]));
                        }
                    }
                }
            }
        }
    }

    private function resolveExportValue(User $user, string $property): string
    {
        if ($property === 'computed.typeUserLabel') {
            return match ($user->getTypeUser()) {
                User::PORTEUR => 'Porteur de projet',
                User::PROPRIO => 'Propriétaire',
                User::ADMIN => 'Administrateur',
                default => '',
            };
        }

        if ($property === 'computed.rolesLabel') {
            $roles = array_values(array_filter(
                $user->getRoles(),
                static fn (string $role): bool => $role !== 'ROLE_USER'
            ));

            return implode(', ', $roles);
        }

        if (str_starts_with($property, 'computed.yesno.')) {
            $field = substr($property, strlen('computed.yesno.'));
            $value = $this->readBooleanFieldValue($user, $field);

            return $value ? 'Oui' : 'Non';
        }

        try {
            $value = $this->propertyAccessor->getValue($user, $property);
        } catch (\Exception $e) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    private function readBooleanFieldValue(User $user, string $field): bool
    {
        $getterCandidates = [
            'is' . ucfirst($field),
            'get' . ucfirst($field),
        ];

        foreach ($getterCandidates as $getter) {
            if (method_exists($user, $getter)) {
                return (bool) $user->$getter();
            }
        }

        return false;
    }
}
