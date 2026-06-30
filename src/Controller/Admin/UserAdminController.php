<?php

namespace App\Controller\Admin;

use Sonata\AdminBundle\Controller\CRUDController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Sonata\Exporter\Writer\CsvWriter;
use Sonata\Exporter\Handler;
use Sonata\AdminBundle\Filter\FilterData;

/** @extends CRUDController<User> */
class UserAdminController extends CRUDController
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Action pour afficher l'aide sur les filtres et l'export
     */
    public function helpFiltersAction(): Response
    {
        return $this->renderWithExtraParams('Admin/User/help_filters.html.twig', [
            'action' => 'list',
        ]);
    }
    
    /**
     * Action pour sélectionner les champs à exporter
     */
    public function selectExportFieldsAction(Request $request): Response
    {
        $availableFields = $this->getAllAvailableFields();
        $filterParameters = $request->query->all();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('export_fields', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $selectedFields = $request->request->all('fields');
            
            if (empty($selectedFields)) {
                $this->addFlash('sonata_flash_error', 'Veuillez sélectionner au moins un champ à exporter.');
                return $this->renderWithExtraParams('Admin/User/select_export_fields.html.twig', [
                    'availableFields' => $availableFields,
                    'action' => 'list',
                    'filterParameters' => $filterParameters,
                ]);
            }
            
            $exportParams = array_merge(
                ['fields' => implode(',', array_filter($selectedFields, 'is_string'))],
                $filterParameters
            );
            
            return $this->redirect($this->admin->generateUrl('custom_export', $exportParams));
        }
        
        return $this->renderWithExtraParams('Admin/User/select_export_fields.html.twig', [
            'availableFields' => $availableFields,
            'action' => 'list',
            'filterParameters' => $filterParameters,
        ]);
    }
    
    /**
     * Action d'export personnalisée avec les champs sélectionnés
     */
    public function customExportAction(Request $request): Response
    {
        $fieldsParam = $request->query->getString('fields');
        $selectedFieldKeys = array_filter(explode(',', $fieldsParam));

        if (empty($selectedFieldKeys)) {
            $this->addFlash('sonata_flash_error', 'Aucun champ sélectionné pour l\'export.');
            return $this->redirectToList();
        }
        
        $allFields = $this->getAllAvailableFields();
        
        $exportFields = [];
        foreach ($selectedFieldKeys as $key) {
            if (isset($allFields[$key])) {
                $exportFields[$allFields[$key]['label']] = $allFields[$key]['property'];
            }
        }

        $datagrid = $this->admin->getDatagrid();
        $queryParams = $request->query->all();
        
        $convertDateArray = function($dateArray) {
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
            if (isset($dateArray['day']) && isset($dateArray['month']) && isset($dateArray['year'])) {
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
        
        foreach ($queryParams as $filterName => $filterData) {
            if (in_array($filterName, ['_page', '_sort_by', '_sort_order', '_per_page', 'fields'])) {
                continue;
            }
            
            if (is_string($filterData)) {
                $decoded = json_decode($filterData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $filterData = $decoded;
                }
            }
            
            if (is_array($filterData) && !isset($filterData['value']) && !isset($filterData['type'])) {
                if (count($filterData) == 2 && isset($filterData[0]) && isset($filterData[1])) {
                    $date1 = $convertDateArray($filterData[0]);
                    $date2 = $convertDateArray($filterData[1]);
                    
                    if ($date1 !== null || $date2 !== null) {
                        $filter = $datagrid->getFilter($filterName);
                        if ($filter) {
                            $normalizedFilterData = [
                                'type' => 'default',
                                'value' => [
                                    'start' => $date1,
                                    'end' => $date2
                                ]
                            ];
                            $filter->apply($datagrid->getQuery(), FilterData::fromArray(['value' => $normalizedFilterData['value']]));
                            continue;
                        }
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
                                'end' => $endDate
                            ];
                        }
                    } elseif (isset($value['day']) || isset($value['month']) || isset($value['year'])) {
                        $date = $convertDateArray($value);
                        if ($date !== null) {
                            $hasValidValue = true;
                            $normalizedValue = $date;
                        }
                    } else {
                        if (count($value) == 2 && isset($value[0]) && isset($value[1])) {
                            $date1 = $convertDateArray($value[0]);
                            $date2 = $convertDateArray($value[1]);
                            
                            if ($date1 !== null || $date2 !== null) {
                                $hasValidValue = true;
                                $normalizedValue = [
                                    'start' => $date1,
                                    'end' => $date2
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
        
        $datagrid->buildPager();

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
        $filename = 'export_porteurs_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function () use ($sourceIterator, $writer) {
            $handler = \Sonata\Exporter\Handler::create($sourceIterator, $writer);
            $handler->export();
        };
        
        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
    
    /**
     * Action pour afficher les statistiques globales des porteurs de projet
     */
    public function statisticsAction(Request $request): Response
    {
        $em = $this->em;

        // Récupérer les années disponibles pour le filtre d'inscription
        $yearsRaw = $em->createQuery(
            'SELECT DISTINCT SUBSTRING(u.createdAt, 1, 4) AS yr
             FROM App\\Entity\\User u
             WHERE u.typeUser = :typePorteur AND u.createdAt IS NOT NULL
             ORDER BY yr ASC'
        )->setParameter('typePorteur', User::PORTEUR)->getResult();
        $availableYears = array_map(function($r) { return (int) $r['yr']; }, $yearsRaw);

        // Année sélectionnée (toutes par défaut)
        $selectedYear = $request->query->get('year', 'all');
        if ($selectedYear !== 'all') {
            $selectedYear = (int) $selectedYear;
            if (!in_array($selectedYear, $availableYears)) {
                $selectedYear = 'all';
            }
        }

        $yearFilter = $selectedYear !== 'all' ? ' AND SUBSTRING(u.createdAt, 1, 4) = :year ' : '';
        $baseParams = [
            'typePorteur' => User::PORTEUR,
        ];
        if ($selectedYear !== 'all') {
            $baseParams['year'] = (string) $selectedYear;
        }

        // --- Données par type de projet (UseType) ---
        $useTypeRows = $em->createQuery(
            'SELECT ut.id, ut.name, ut.isActive, COUNT(u.id) AS total
             FROM App\\Entity\\User u
             JOIN u.useType ut
             WHERE u.typeUser = :typePorteur' . $yearFilter . '
             GROUP BY ut.id
             ORDER BY ut.name ASC'
        )->setParameters($baseParams)->getResult();

        // --- Données par statut juridique ---
        $companyStatusRows = $em->createQuery(
            'SELECT u.companyStatus, COUNT(u.id) AS total
             FROM App\\Entity\\User u
             WHERE u.typeUser = :typePorteur AND u.companyStatus IS NOT NULL' . $yearFilter . '
             GROUP BY u.companyStatus
             ORDER BY u.companyStatus ASC'
        )->setParameters($baseParams)->getResult();

        // --- Timeline : inscriptions par jour ---
        $timelineRows = $em->createQuery(
            'SELECT SUBSTRING(u.createdAt, 1, 10) AS day, COUNT(u.id) AS total
             FROM App\\Entity\\User u
             WHERE u.typeUser = :typePorteur AND u.createdAt IS NOT NULL' . $yearFilter . '
             GROUP BY day
             ORDER BY day ASC'
        )->setParameters($baseParams)->getResult();

        // --- Données par département ---
        $zipcodeRaw = $em->createQuery(
            'SELECT u.zipcode, COUNT(u.id) AS total
             FROM App\\Entity\\User u
             WHERE u.typeUser = :typePorteur' . $yearFilter . '
             GROUP BY u.zipcode'
        )->setParameters($baseParams)->getResult();

        $formattedDepts = [];
        foreach ($zipcodeRaw as $row) {
            $zip = trim($row['zipcode'] ?? '');
            $dept = 'Non renseigné';
            if ($zip !== '') {
                if (str_starts_with($zip, '97') && strlen($zip) >= 3) {
                    $dept = substr($zip, 0, 3);
                } elseif (strlen($zip) >= 2) {
                    $dept = substr($zip, 0, 2);
                }
            }
            if (isset($formattedDepts[$dept])) {
                $formattedDepts[$dept] += (int) $row['total'];
            } else {
                $formattedDepts[$dept] = (int) $row['total'];
            }
        }
        arsort($formattedDepts);

        $zipcodeRows = [];
        foreach ($formattedDepts as $dept => $count) {
            $zipcodeRows[] = [
                'label' => $dept,
                'total' => $count
            ];
        }

        // --- Totaux globaux ---
        $totalUsers = (int) $em->createQuery(
            'SELECT COUNT(u.id) FROM App\\Entity\\User u WHERE u.typeUser = :typePorteur' . $yearFilter
        )->setParameters($baseParams)->getSingleScalarResult();

        return $this->renderWithExtraParams('Admin/User/statistics.html.twig', [
            'action'           => 'list',
            'useTypeRows'      => $useTypeRows,
            'companyStatusRows'=> $companyStatusRows,
            'timelineRows'     => $timelineRows,
            'zipcodeRows'      => $zipcodeRows,
            'totalUsers'       => $totalUsers,
            'availableYears'   => $availableYears,
            'selectedYear'     => $selectedYear,
        ]);
    }

    private function getAllAvailableFields(): array
    {
        return [
            // Informations personnelles
            'id' => [
                'label' => 'ID de l\'utilisateur',
                'property' => 'id',
                'category' => 'Informations personnelles'
            ],
            'email' => [
                'label' => 'Email',
                'property' => 'email',
                'category' => 'Informations personnelles'
            ],
            'civility' => [
                'label' => 'Civilité',
                'property' => 'civility',
                'category' => 'Informations personnelles'
            ],
            'firstname' => [
                'label' => 'Prénom',
                'property' => 'firstname',
                'category' => 'Informations personnelles'
            ],
            'lastname' => [
                'label' => 'Nom',
                'property' => 'lastname',
                'category' => 'Informations personnelles'
            ],
            'birthday' => [
                'label' => 'Date de naissance',
                'property' => 'birthday',
                'category' => 'Informations personnelles'
            ],
            'phone' => [
                'label' => 'Téléphone',
                'property' => 'phone',
                'category' => 'Informations personnelles'
            ],
            'createdAt' => [
                'label' => 'Date d\'inscription',
                'property' => 'createdAt',
                'category' => 'Informations personnelles'
            ],

            // Structure du porteur
            'company' => [
                'label' => 'Nom de la structure',
                'property' => 'company',
                'category' => 'Structure du porteur'
            ],
            'companyStatus' => [
                'label' => 'Statut de la structure',
                'property' => 'companyStatus',
                'category' => 'Structure du porteur'
            ],
            'companyCreationDate' => [
                'label' => 'Date de création de la structure',
                'property' => 'companyCreationDate',
                'category' => 'Structure du porteur'
            ],
            'siret' => [
                'label' => 'SIRET',
                'property' => 'siret',
                'category' => 'Structure du porteur'
            ],
            'address' => [
                'label' => 'Adresse',
                'property' => 'address',
                'category' => 'Structure du porteur'
            ],
            'addressSuite' => [
                'label' => 'Adresse (suite)',
                'property' => 'addressSuite',
                'category' => 'Structure du porteur'
            ],
            'zipcode' => [
                'label' => 'Code postal',
                'property' => 'zipcode',
                'category' => 'Structure du porteur'
            ],
            'city' => [
                'label' => 'Ville',
                'property' => 'city',
                'category' => 'Structure du porteur'
            ],
            'companyPhone' => [
                'label' => 'Téléphone fixe structure',
                'property' => 'companyPhone',
                'category' => 'Structure du porteur'
            ],
            'companyMobile' => [
                'label' => 'Téléphone mobile structure',
                'property' => 'companyMobile',
                'category' => 'Structure du porteur'
            ],
            'companyDescription' => [
                'label' => 'Présentation de la structure',
                'property' => 'companyDescription',
                'category' => 'Structure du porteur'
            ],
            'companyEffective' => [
                'label' => 'Nombre de personnes',
                'property' => 'companyEffective',
                'category' => 'Structure du porteur'
            ],
            'companyStructures' => [
                'label' => 'Structure(s) d\'accompagnement',
                'property' => 'companyStructures',
                'category' => 'Structure du porteur'
            ],
            'company_site' => [
                'label' => 'Site web',
                'property' => 'company_site',
                'category' => 'Structure du porteur'
            ],
            'company_blog' => [
                'label' => 'Blog',
                'property' => 'company_blog',
                'category' => 'Structure du porteur'
            ],

            // Souhaits et projet
            'wishedSize' => [
                'label' => 'Surface souhaitée (m²)',
                'property' => 'wishedSize',
                'category' => 'Projet et souhaits'
            ],
            'preferredDepartments' => [
                'label' => 'Zone géographique (départements)',
                'property' => 'preferredDepartmentsLabelsForExport',
                'category' => 'Projet et souhaits'
            ],
            'useType' => [
                'label' => 'Type de projet',
                'property' => 'useType',
                'category' => 'Projet et souhaits'
            ],
            'usageDate' => [
                'label' => 'Date de disponibilité',
                'property' => 'usageDate',
                'category' => 'Projet et souhaits'
            ],
            'usageDuration' => [
                'label' => 'Durée d\'occupation souhaitée',
                'property' => 'usageDuration',
                'category' => 'Projet et souhaits'
            ],
            'projectDescription' => [
                'label' => 'Présentation du projet',
                'property' => 'projectDescription',
                'category' => 'Projet et souhaits'
            ],

            // Réseaux sociaux
            'facebookUrl' => [
                'label' => 'Facebook',
                'property' => 'facebookUrl',
                'category' => 'Réseaux sociaux'
            ],
            'twitterUrl' => [
                'label' => 'Twitter',
                'property' => 'twitterUrl',
                'category' => 'Réseaux sociaux'
            ],
            'instagramUrl' => [
                'label' => 'Instagram',
                'property' => 'instagramUrl',
                'category' => 'Réseaux sociaux'
            ],
            'linkedinUrl' => [
                'label' => 'LinkedIn',
                'property' => 'linkedinUrl',
                'category' => 'Réseaux sociaux'
            ],
            'youtubeUrl' => [
                'label' => 'YouTube',
                'property' => 'youtubeUrl',
                'category' => 'Réseaux sociaux'
            ],
            'tiktokUrl' => [
                'label' => 'TikTok',
                'property' => 'tiktokUrl',
                'category' => 'Réseaux sociaux'
            ],
            'otherUrl' => [
                'label' => 'Autre',
                'property' => 'otherUrl',
                'category' => 'Réseaux sociaux'
            ],
        ];
    }
}
