<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Filter\Model\FilterData;
use App\Entity\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Sonata\Exporter\Writer\CsvWriter;

/** @extends CRUDController<Application> */
class ApplicationAdminController extends CRUDController
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Action pour afficher l'aide sur les filtres et l'export
     */
    public function helpFiltersAction(): Response
    {
        return $this->renderWithExtraParams('Admin/Application/help_filters.html.twig', [
            'action' => 'list',
        ]);
    }
    
    /**
     * Action pour sélectionner les champs à exporter
     */
    public function selectExportFieldsAction(Request $request): Response
    {
        // Récupérer tous les champs disponibles
        $availableFields = $this->getAllAvailableFields();
        
        // IMPORTANT: Récupérer TOUS les paramètres de l'URL (filtres + pagination + tri)
        // car getFilterParameters() de Sonata ne retourne que _sort_by, _sort_order, etc.
        $filterParameters = $request->query->all();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('export_fields', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $selectedFields = $request->request->all('fields');
            
            if (empty($selectedFields)) {
                $this->addFlash('sonata_flash_error', 'Veuillez sélectionner au moins un champ à exporter.');
                return $this->renderWithExtraParams('Admin/Application/select_export_fields.html.twig', [
                    'availableFields' => $availableFields,
                    'action' => 'list',
                    'filterParameters' => $filterParameters,
                ]);
            }
            
            // Préparer les paramètres pour l'export en conservant les filtres
            $exportParams = array_merge(
                ['fields' => implode(',', array_filter($selectedFields, 'is_string'))],
                $filterParameters
            );
            
            // Rediriger vers l'export avec les champs sélectionnés ET les filtres
            return $this->redirect($this->admin->generateUrl('custom_export', $exportParams));
        }
        
        return $this->renderWithExtraParams('Admin/Application/select_export_fields.html.twig', [
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
        
        // Récupérer tous les champs disponibles
        $allFields = $this->getAllAvailableFields();
        
        // Construire le tableau des champs à exporter
        $exportFields = [];
        foreach ($selectedFieldKeys as $key) {
            if (isset($allFields[$key])) {
                $exportFields[$allFields[$key]['label']] = $allFields[$key]['property'];
            }
        }
        
        // Déterminer si des champs calculés sont demandés.
        // Les champs computed.* ne sont pas résolus par le PropertyAccessor Sonata Exporter.
        $hasComputedFields = false;
        foreach ($exportFields as $property) {
            if (strpos($property, 'computed.') === 0) {
                $hasComputedFields = true;
                break;
            }
        }

        // Récupérer le datagrid
        $datagrid = $this->admin->getDatagrid();

        // Appliquer les filtres manuellement depuis les paramètres de l'URL
        $queryParams = $request->query->all();
        
        // Fonction helper pour convertir un tableau de date (day, month, year) en DateTime
        $convertDateArray = function($dateArray) {
            // Si c'est une chaîne JSON, la décoder
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
            

            // Si c'est un tableau avec day, month, year
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
        
        // Parcourir les filtres et les appliquer au datagrid
        foreach ($queryParams as $filterName => $filterData) {
            // Ignorer les paramètres système et fields
            if (in_array($filterName, ['_page', '_sort_by', '_sort_order', '_per_page', 'fields'])) {
                continue;
            }
            
            // Normaliser les paramètres qui peuvent être des chaînes JSON
            if (is_string($filterData)) {
                $decoded = json_decode($filterData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $filterData = $decoded;
                }
            }
            
            // Vérifier si c'est un filtre de date range passé directement comme tableau [date1, date2]
            // Les dates peuvent être passées comme [{"day":"11","month":"1","year":"2026"}, {"day":"19","month":"1","year":"2026"}]
            if (is_array($filterData) && !isset($filterData['value']) && !isset($filterData['type'])) {
                // Vérifier si c'est un tableau de deux éléments qui ressemblent à des dates
                if (count($filterData) == 2 && isset($filterData[0]) && isset($filterData[1])) {
                    $date1 = $convertDateArray($filterData[0]);
                    $date2 = $convertDateArray($filterData[1]);
                    
                    // Si au moins une des deux dates est valide, c'est probablement un filtre de date range
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
            
            // Si c'est un filtre avec une valeur
            if (is_array($filterData) && isset($filterData['value'])) {
                $value = $filterData['value'];
                
                // Vérifier si la valeur est réellement non vide
                // Pour les valeurs simples (string, number)
                if (is_string($value) || is_numeric($value)) {
                    if ($value !== '') {
                        $filter = $datagrid->getFilter($filterName);
                        if ($filter) {
                            $filter->apply($datagrid->getQuery(), FilterData::fromArray(['value' => $filterData['value'] ?? null]));
                        }
                    }
                }
                // Pour les tableaux (comme les dates), vérifier qu'il y a des valeurs non vides
                elseif (is_array($value)) {
                    // Pour les dates de type range (start/end)
                    $hasValidValue = false;
                    $normalizedValue = $value;
                    
                    if (isset($value['start']) && is_array($value['start'])) {
                        // Date range avec start/end
                        $startDate = $convertDateArray($value['start']);
                        $endDate = isset($value['end']) && is_array($value['end']) ? $convertDateArray($value['end']) : null;
                        
                        if ($startDate !== null || $endDate !== null) {
                            $hasValidValue = true;
                            // Normaliser la valeur pour le filtre
                            $normalizedValue = [
                                'start' => $startDate,
                                'end' => $endDate
                            ];
                        }
                    } elseif (isset($value['day']) || isset($value['month']) || isset($value['year'])) {
                        // Date simple avec day, month, year
                        $date = $convertDateArray($value);
                        if ($date !== null) {
                            $hasValidValue = true;
                            $normalizedValue = $date;
                        }
                    } else {
                        // Vérifier si c'est un tableau de dates (pour les filtres range qui peuvent être passés différemment)
                        // Parfois les dates range sont passées comme [date1, date2] au lieu de {start: date1, end: date2}
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
                            // Pour les autres tableaux, vérifier s'il y a des valeurs non vides
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
                            // Reconstruire filterData avec la valeur normalisée
                            $filter->apply($datagrid->getQuery(), FilterData::fromArray(['value' => $normalizedValue]));
                        }
                    }
                }
            }
        }
        
        $datagrid->getPager()->setMaxPerPage(PHP_INT_MAX);
        $datagrid->buildPager();

        // Export CSV manuel si des champs calculés sont sélectionnés
        if ($hasComputedFields) {
            $applications = $datagrid->getResults();
            $headers = array_keys($exportFields);

            $callback = function () use ($applications, $exportFields, $headers) {
                echo "\xEF\xBB\xBF";
                $rows = [];
                $rows[] = $headers;

                foreach ($applications as $application) {
                    $row = [];
                    foreach ($exportFields as $property) {
                        $row[] = (string) $this->resolveExportValue($application, $property);
                    }
                    $rows[] = $row;
                }

                foreach ($rows as $row) {
                    $escapedRow = array_map(function ($cell) {
                        return '"' . str_replace('"', '""', (string) $cell) . '"';
                    }, $row);
                    echo implode(';', $escapedRow) . "\r\n";
                }
            };

            $filename = 'export_candidatures_' . date('Y-m-d_H-i-s') . '.csv';

            return new StreamedResponse($callback, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]);
        }

        // Export direct via query source iterator
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
        
        // Préparer le CSV
        $writer = new CsvWriter('php://output');
        $filename = 'export_candidatures_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function () use ($sourceIterator, $writer) {
            // Pour Sonata Exporter moderne
            $handler = \Sonata\Exporter\Handler::create($sourceIterator, $writer);
            $handler->export();
        };
        
        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
    
    /**
     * Retourne tous les champs disponibles pour l'export
     */
    /** @return array<string, array{label: string, property: string, category: string}> */
    private function getAllAvailableFields(): array
    {
        // Aligné sur `SpaceManagementController::getAllAvailableFieldsForExport()`
        return [
            // Informations générales
            'space' => [
                'label' => '[Candidature] Espace',
                'property' => 'space',
                'category' => 'Informations générales'
            ],
            'id' => [
                'label' => '[Candidature] ID de la candidature',
                'property' => 'id',
                'category' => 'Informations générales'
            ],
            'name' => [
                'label' => '[Candidature] Nom du projet',
                'property' => 'name',
                'category' => 'Informations générales'
            ],
            'status' => [
                'label' => '[Candidature] Statut',
                'property' => 'statusLabel',
                'category' => 'Informations générales'
            ],
            'selected' => [
                'label' => '[Candidature] Sélectionné',
                'property' => 'selected',
                'category' => 'Informations générales'
            ],
            'created' => [
                'label' => '[Candidature] Date de création',
                'property' => 'created',
                'category' => 'Informations générales'
            ],
            'updated' => [
                'label' => '[Candidature] Date de mise à jour',
                'property' => 'updated',
                'category' => 'Informations générales'
            ],

            // Profil - Porteur de projet
            'projectHolder_fullName' => [
                'label' => '[Profil] Nom complet du porteur',
                'property' => 'projectHolder.fullName',
                'category' => 'Profil - Porteur de projet'
            ],
            'projectHolder_email' => [
                'label' => '[Profil] Email du porteur',
                'property' => 'projectHolder.email',
                'category' => 'Profil - Porteur de projet'
            ],
            'projectHolder_civility' => [
                'label' => '[Profil] Civilité',
                'property' => 'projectHolder.civility',
                'category' => 'Profil - Porteur de projet'
            ],
            'projectHolder_birthday' => [
                'label' => '[Profil] Date de naissance',
                'property' => 'projectHolder.birthday',
                'category' => 'Profil - Porteur de projet'
            ],
            'projectHolder_newsletter' => [
                'label' => '[Profil] Newsletter',
                'property' => 'projectHolder.newsletter',
                'category' => 'Profil - Porteur de projet'
            ],
            'projectHolder_requiredIdDoc' => [
                'label' => '[Profil] Doc obligatoire - Pièce d\'identité (chemin local)',
                'property' => 'computed.profileRequiredIdDocPath',
                'category' => 'Profil - Documents obligatoires'
            ],
            'projectHolder_requiredKbisDoc' => [
                'label' => '[Profil] Doc obligatoire - Justificatif d\'activité (chemin local)',
                'property' => 'computed.profileRequiredKbisDocPath',
                'category' => 'Profil - Documents obligatoires'
            ],

            // Profil - Structure du porteur de projet
            
            'projectHolder_firstname' => [
                'label' => '[Profil] Prénom',
                'property' => 'projectHolder.firstname',
                'category' => 'Profil - Mes informations'
            ],
            'projectHolder_lastname' => [
                'label' => '[Profil] Nom',
                'property' => 'projectHolder.lastname',
                'category' => 'Profil - Mes informations'
            ],
            
            
            'projectHolder_phone' => [
                'label' => '[Profil] Téléphone personnel',
                'property' => 'projectHolder.phone',
                'category' => 'Profil - Mes informations'
            ],

            // Profil - Structure du porteur de projet
            'projectHolder_company' => [
                'label' => '[Profil] Nom de la structure',
                'property' => 'projectHolder.company',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_siret' => [
                'label' => '[Profil] SIRET',
                'property' => 'projectHolder.siret',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_companyStatus' => [
                'label' => '[Profil] Statut de la structure',
                'property' => 'projectHolder.companyStatus',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_companyCreationDate' => [
                'label' => '[Profil] Date de création de la structure',
                'property' => 'projectHolder.companyCreationDate',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_address' => [
                'label' => '[Profil] Adresse',
                'property' => 'projectHolder.address',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_zipcode' => [
                'label' => '[Profil] Code postal',
                'property' => 'projectHolder.zipcode',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_companyPhone' => [
                'label' => '[Profil] Téléphone structure',
                'property' => 'projectHolder.companyPhone',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_companyMobile' => [
                'label' => '[Profil] Mobile',
                'property' => 'projectHolder.companyMobile',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_companyEffective' => [
                'label' => '[Profil] Nombre de personnes',
                'property' => 'projectHolder.companyEffective',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_companyStructures' => [
                'label' => '[Profil] Structure d\'accompagnement',
                'property' => 'projectHolder.companyStructures',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_companySite' => [
                'label' => '[Profil] Site web',
                'property' => 'projectHolder.companySite',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_companyDescription' => [
                'label' => '[Profil] Présentation de la structure',
                'property' => 'projectHolder.companyDescription',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_isPuShareholder' => [
                'label' => '[Profil] Déjà sociétaire Plateau urbain',
                'property' => 'projectHolder.isPuShareholder',
                'category' => 'Profil - Structure du porteur de projet'
            ],
            'projectHolder_isSubjectToVat' => [
                'label' => '[Profil] Assujetti à la TVA',
                'property' => 'projectHolder.isSubjectToVat',
                'category' => 'Profil - Structure du porteur de projet'
            ],

            // Profil - Réseaux sociaux
            'projectHolder_facebookUrl' => [
                'label' => '[Profil] Facebook',
                'property' => 'projectHolder.facebookUrl',
                'category' => 'Profil - Réseaux sociaux'
            ],
            'projectHolder_twitterUrl' => [
                'label' => '[Profil] Twitter',
                'property' => 'projectHolder.twitterUrl',
                'category' => 'Profil - Réseaux sociaux'
            ],
            'projectHolder_instagramUrl' => [
                'label' => '[Profil] Instagram',
                'property' => 'projectHolder.instagramUrl',
                'category' => 'Profil - Réseaux sociaux'
            ],
            'projectHolder_googleUrl' => [
                'label' => '[Profil] Bluesky',
                'property' => 'projectHolder.googleUrl',
                'category' => 'Profil - Réseaux sociaux'
            ],
            'projectHolder_linkedinUrl' => [
                'label' => '[Profil] LinkedIn',
                'property' => 'projectHolder.linkedinUrl',
                'category' => 'Profil - Réseaux sociaux'
            ],
            'projectHolder_youtubeUrl' => [
                'label' => '[Profil] YouTube',
                'property' => 'projectHolder.youtubeUrl',
                'category' => 'Profil - Réseaux sociaux'
            ],
            'projectHolder_tiktokUrl' => [
                'label' => '[Profil] TikTok',
                'property' => 'projectHolder.tiktokUrl',
                'category' => 'Profil - Réseaux sociaux'
            ],
            'projectHolder_otherUrl' => [
                'label' => '[Profil] Autre URL',
                'property' => 'projectHolder.otherUrl',
                'category' => 'Profil - Réseaux sociaux'
            ],

            // Profil - Mon projet
            'projectHolder_wishedSize' => [
                'label' => '[Profil] Surface souhaitée (m²)',
                'property' => 'projectHolder.wishedSize',
                'category' => 'Profil - Mon projet'
            ],
            'projectHolder_useType' => [
                'label' => '[Profil] Type de projet',
                'property' => 'projectHolder.useType',
                'category' => 'Profil - Mon projet'
            ],
            'projectHolder_preferredDepartments' => [
                'label' => '[Profil] Zone géographique',
                'property' => 'projectHolder.preferredDepartmentsLabelsForExport',
                'category' => 'Profil - Mon projet'
            ],
            'projectHolder_usageDate' => [
                'label' => '[Profil] Date de disponibilité',
                'property' => 'projectHolder.usageDate',
                'category' => 'Profil - Mon projet'
            ],
            'projectHolder_usageDuration' => [
                'label' => '[Profil] Durée d\'occupation',
                'property' => 'projectHolder.usageDuration',
                'category' => 'Profil - Mon projet'
            ],
            'projectHolder_monthlyBudgetMax' => [
                'label' => '[Profil] Budget mensuel total maximum (€)',
                'property' => 'projectHolder.monthlyBudgetMax',
                'category' => 'Profil - Mon projet'
            ],
            'projectHolder_projectDescription' => [
                'label' => '[Profil] Présentation du projet',
                'property' => 'projectHolder.projectDescription',
                'category' => 'Profil - Mon projet'
            ],

            // Candidature - Informations sur le projet
            'description' => [
                'label' => '[Candidature] Présentation du projet',
                'property' => 'description',
                'category' => 'Candidature - Informations sur le projet'
            ],
            'localUsageDescription' => [
                'label' => '[Candidature] Quel sera l\'usage du local ?',
                'property' => 'localUsageDescription',
                'category' => 'Candidature - Informations sur le projet'
            ],
            'category' => [
                'label' => '[Candidature] Type d\'usage',
                'property' => 'category',
                'category' => 'Candidature - Informations sur le projet'
            ],
            'companyStatus' => [
                'label' => '[Candidature] Statut juridique',
                'property' => 'companyStatus',
                'category' => 'Candidature - Informations sur le projet'
            ],
            'contribution' => [
                'label' => '[Candidature] Quelles idées avez-vous pour participer au projet collectif ?',
                'property' => 'contribution',
                'category' => 'Candidature - Informations sur le projet'
            ],
            'openToGlobalProject' => [
                'label' => '[Candidature] Ouvert au projet collectif',
                'property' => 'openToGlobalProject',
                'category' => 'Candidature - Informations sur le projet'
            ],

            // Candidature - Occupation
            'wishedSize' => [
                'label' => '[Candidature] Surface souhaitée (m²)',
                'property' => 'wishedSize',
                'category' => 'Candidature - Occupation'
            ],
            'startOccupation' => [
                'label' => '[Candidature] Date d\'entrée souhaitée',
                'property' => 'startOccupation',
                'category' => 'Candidature - Occupation'
            ],
            'application_documents_paths' => [
                'label' => '[Candidature] Documents déposés (chemins locaux)',
                'property' => 'computed.applicationDocumentsPaths',
                'category' => 'Candidature - Documents déposés'
            ],
        ];
    }

    /**
     * Résout la valeur d'un champ export, y compris les champs calculés.
     *
     * @param object $application
     * @param string $property
     *
     * @return string
     */
    private function resolveExportValue($application, $property)
    {
        if (strpos($property, 'computed.') === 0) {
            return $this->resolveComputedExportValue($application, $property);
        }

        $parts = explode('.', $property);
        $value = $application;
        foreach ($parts as $part) {
            $getter = 'get' . ucfirst($part);
            if (is_object($value)) {
                if (method_exists($value, $getter)) {
                    $value = $value->$getter();
                } elseif (method_exists($value, $part)) {
                    $value = $value->$part();
                } else {
                    $value = '';
                    break;
                }
            } else {
                $value = '';
                break;
            }
        }

        if ($value instanceof \DateTime) {
            $value = $value->format('d/m/Y H:i');
        }
        if (is_bool($value)) {
            $value = $value ? 'Oui' : 'Non';
        }
        if (is_array($value)) {
            $value = implode('; ', array_map(fn(mixed $v): string => is_scalar($v) ? (string) $v : '', $value));
        }

        if (!is_scalar($value)) { return ''; }
        return (string) $value;
    }

    /**
     * Résout les champs export calculés (computed.*).
     *
     * @param object $application
     * @param string $property
     *
     * @return string
     */
    private function resolveComputedExportValue($application, $property)
    {
        if ($property === 'computed.applicationDocumentsPaths') {
            $paths = $this->getApplicationDocumentDisplayPaths($application);
            return $paths ? implode('; ', $paths) : '';
        }

        if (!is_object($application) || !method_exists($application, 'getProjectHolder')) {
            return '';
        }

        $projectHolder = $application->getProjectHolder();
        if (!$projectHolder instanceof \App\Entity\User) {
            return '';
        }

        if ($property === 'computed.profileRequiredIdDocPath') {
            $docs = $projectHolder->getDocumentsType('id');
            if (!empty($docs) && isset($docs[0]) && method_exists($docs[0], 'getFileName') && $docs[0]->getFileName()) {
                return 'Piece_identite_' . $docs[0]->getFileName();
            }
            return '';
        }

        if ($property === 'computed.profileRequiredKbisDocPath') {
            $docs = $projectHolder->getDocumentsType('kbis');
            if (!empty($docs) && isset($docs[0]) && method_exists($docs[0], 'getFileName') && $docs[0]->getFileName()) {
                return 'KBIS_' . $docs[0]->getFileName();
            }
            return '';
        }

        return '';
    }

    /**
     * Renvoie une liste de "chemins" lisibles pour les documents déposés.
     * En Sonata (export CSV), on ne crée pas de ZIP, donc on renvoie des noms de fichiers.
     *
     * @param object $application
     *
     * @return string[]
     */
    private function getApplicationDocumentDisplayPaths($application)
    {
        if (!is_object($application) || !method_exists($application, 'getFiles')) {
            return [];
        }

        $paths = [];
        foreach ($application->getFiles() as $file) {
            if (!$file instanceof \App\Entity\ApplicationFile || !$file->getFileName()) {
                continue;
            }
            $displayName = $file->getFileName();
            $spaceDoc = $file->getSpaceDocument();
            if ($spaceDoc instanceof \App\Entity\SpaceDocument) {
                $displayName = $spaceDoc->getName() . '_' . $file->getFileName();
            }
            $paths[] = $displayName;
        }

        return $paths;
    }

    /**
     * Page de statistiques globales sur toutes les candidatures (hors brouillons).
     */
    public function statisticsAction(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $em = $this->em;

        // Récupérer les années disponibles pour le filtre
        $yearsRaw = $em->createQuery(
            'SELECT DISTINCT SUBSTRING(a.created, 1, 4) AS yr
             FROM App\\Entity\\Application a
             WHERE a.status != :draft
             ORDER BY yr ASC'
        )->setParameter('draft', \App\Entity\Application::DRAFT_STATUS)->getResult();
        $availableYears = array_map(function($r) { return (int) $r['yr']; }, $yearsRaw);

        // Année sélectionnée (toutes par défaut)
        $selectedYear = $request->query->get('year', 'all');
        if ($selectedYear !== 'all') {
            $selectedYear = (int) $selectedYear;
            if (!in_array($selectedYear, $availableYears)) {
                $selectedYear = 'all';
            }
        }

        // Clause de filtre DQL commune
        $yearFilter    = $selectedYear !== 'all' ? ' AND SUBSTRING(a.created, 1, 4) = :year ' : '';
        $baseParams    = [
            'wait'     => \App\Entity\Application::WAIT_STATUS,
            'accepted' => \App\Entity\Application::ACCEPT_STATUS,
            'rejected' => \App\Entity\Application::REJECT_STATUS,
            'draft'    => \App\Entity\Application::DRAFT_STATUS,
        ];
        if ($selectedYear !== 'all') {
            $baseParams['year'] = (string) $selectedYear;
        }

        // --- Données par type d'usage (Category) ---
        $categoryRows = $em->createQuery(
            'SELECT c.id, c.name, c.isActive,
                    SUM(CASE WHEN a.status = :wait     THEN 1 ELSE 0 END) AS awaiting,
                    SUM(CASE WHEN a.status = :accepted THEN 1 ELSE 0 END) AS accepted,
                    SUM(CASE WHEN a.status = :rejected THEN 1 ELSE 0 END) AS rejected,
                    COUNT(a.id) AS total,
                    SUM(CASE WHEN a.wishedSize > 0 THEN a.wishedSize ELSE 0 END) AS totalSurface,
                    SUM(CASE WHEN a.wishedSize > 0 THEN 1 ELSE 0 END) AS countWithSurface
             FROM App\\Entity\\Application a
             JOIN a.category c
             WHERE a.status != :draft' . $yearFilter . '
             GROUP BY c.id
             ORDER BY c.name ASC'
        )->setParameters($baseParams)->getResult();

        // Candidatures sans catégorie
        $noCategoryCount = (int) $em->createQuery(
            'SELECT COUNT(a.id) FROM App\\Entity\\Application a
             WHERE a.category IS NULL AND a.status != :draft' . $yearFilter
        )->setParameters(array_intersect_key($baseParams, ['draft' => true, 'year' => true]))->getSingleScalarResult();

        // --- Données par type de projet (UseType) ---
        $useTypeRows = $em->createQuery(
            'SELECT u.id, u.name, u.isActive, COUNT(a.id) AS total
             FROM App\\Entity\\Application a
             JOIN a.projectHolder ph
             JOIN ph.useType u
             WHERE a.status != :draft' . $yearFilter . '
             GROUP BY u.id
             ORDER BY u.name ASC'
        )->setParameters(array_intersect_key($baseParams, ['draft' => true, 'year' => true]))->getResult();

        // --- Données par statut juridique (candidature) ---
        $companyStatusRows = $em->createQuery(
            'SELECT a.companyStatus, COUNT(a.id) AS total
             FROM App\\Entity\\Application a
             WHERE a.status != :draft AND a.companyStatus IS NOT NULL' . $yearFilter . '
             GROUP BY a.companyStatus
             ORDER BY a.companyStatus ASC'
        )->setParameters(array_intersect_key($baseParams, ['draft' => true, 'year' => true]))->getResult();

        // --- Timeline : candidatures par jour ---
        $timelineRows = $em->createQuery(
            'SELECT SUBSTRING(a.created, 1, 10) AS day, COUNT(a.id) AS total
             FROM App\\Entity\\Application a
             WHERE a.status != :draft' . $yearFilter . '
             GROUP BY day
             ORDER BY day ASC'
        )->setParameters(array_intersect_key($baseParams, ['draft' => true, 'year' => true]))->getResult();

        // --- Données par département (Zipcode) ---
        $zipcodeRaw = $em->createQuery(
            'SELECT ph.zipcode, COUNT(a.id) AS total
             FROM App\\Entity\\Application a
             JOIN a.projectHolder ph
             WHERE a.status != :draft' . $yearFilter . '
             GROUP BY ph.zipcode'
        )->setParameters(array_intersect_key($baseParams, ['draft' => true, 'year' => true]))->getResult();

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
        $totals = $em->createQuery(
            'SELECT COUNT(a.id) AS total,
                    SUM(CASE WHEN a.status = :wait     THEN 1 ELSE 0 END) AS awaiting,
                    SUM(CASE WHEN a.status = :accepted THEN 1 ELSE 0 END) AS accepted,
                    SUM(CASE WHEN a.status = :rejected THEN 1 ELSE 0 END) AS rejected
             FROM App\\Entity\\Application a WHERE a.status != :draft' . $yearFilter
        )->setParameters($baseParams)->getSingleResult();

        return $this->renderWithExtraParams('Admin/Application/statistics.html.twig', [
            'action'           => 'list',
            'categoryRows'     => $categoryRows,
            'noCategoryCount'  => $noCategoryCount,
            'useTypeRows'      => $useTypeRows,
            'companyStatusRows'=> $companyStatusRows,
            'timelineRows'     => $timelineRows,
            'zipcodeRows'      => $zipcodeRows,
            'totals'           => $totals,
            'availableYears'   => $availableYears,
            'selectedYear'     => $selectedYear,
        ]);
    }
}
