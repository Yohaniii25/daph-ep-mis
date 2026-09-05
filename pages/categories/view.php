<?php
/**
 * pages/categories/view.php
 * Action Level Hub for Core User Categories
 * Routes users to specific actions/functions, and onward to Summary Level dedicated dashboards with charts & metrics
 */

require_once __DIR__ . '/../../includes/header.php';

$category_key = trim($_GET['cat'] ?? 'deputy_director_district');

// Master Registry of the 8 Core User Categories & their Action Levels + Summary Dashboards
$categories_config = [
    'provincial_director' => [
        'title' => 'Provincial Director',
        'subtitle' => 'Executive Provincial Oversight, Multi-District Governance & High-Level Approvals',
        'icon' => 'bi-award-fill',
        'badge' => 'Executive Directorate',
        'gradient' => 'linear-gradient(135deg, #500707 0%, #780016 100%)',
        'dashboard_url' => $rel_path . 'dashboard.php?view=provincial_director',
        'dashboard_title' => 'Executive Analytics & Provincial Performance Dashboard',
        'actions' => [
            [
                'title' => 'Pending Authorizations & Approvals',
                'desc' => 'Maker-checker approval queue for HR and inventory modifications across the province.',
                'icon' => 'bi-shield-check',
                'url' => $rel_path . 'pages/modules/pd/pending_approvals.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=provincial_director',
                'badge' => 'Maker-Checker'
            ],
            [
                'title' => 'Global HR Directory',
                'desc' => 'Unified civil service roster, personnel placement, and cross-district postings.',
                'icon' => 'bi-people-fill',
                'url' => $rel_path . 'pages/modules/pd/employee_managment.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=provincial_director',
                'badge' => 'Personnel'
            ],
            [
                'title' => 'Animal Health Log',
                'desc' => 'Aggregated clinical treatment trends, disease outbreak alerts, and veterinary stats.',
                'icon' => 'bi-heart-pulse-fill',
                'url' => $rel_path . 'pages/modules/pd/animal_health_reports.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=provincial_director',
                'badge' => 'Epidemiology'
            ],
            [
                'title' => 'Breeding & Insemination Metrics',
                'desc' => 'Artificial insemination success rates, progeny logs, and genetic progress metrics.',
                'icon' => 'bi-diagram-3-fill',
                'url' => $rel_path . 'dashboard.php?view=provincial_director',
                'dashboard_url' => $rel_path . 'dashboard.php?view=provincial_director',
                'badge' => 'Breeding'
            ],
            [
                'title' => 'Poultry & Hatchery Analytics',
                'desc' => 'State poultry farms production, incubator hatchability, and commercial distribution.',
                'icon' => 'bi-egg-fill',
                'url' => $rel_path . 'dashboard.php?view=farms',
                'dashboard_url' => $rel_path . 'dashboard.php?view=provincial_director',
                'badge' => 'Poultry'
            ],
            [
                'title' => 'Vaccine Stock & Cold Chain',
                'desc' => 'Provincial buffer balances, cold chain reliability, and immunization targets.',
                'icon' => 'bi-capsule',
                'url' => $rel_path . 'pages/modules/sms/immunization.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=sms',
                'badge' => 'Cold Chain'
            ]
        ]
    ],
    'subject_matter_specialist' => [
        'title' => 'Subject Matter Specialist',
        'subtitle' => 'Disease Control, Immunization Programmes, Mobile Clinics & Technical Advisory',
        'icon' => 'bi-journal-medical',
        'badge' => 'Specialist Operations',
        'gradient' => 'linear-gradient(135deg, #065f46 0%, #047857 100%)',
        'dashboard_url' => $rel_path . 'dashboard.php?view=sms',
        'dashboard_title' => 'SMS Technical & Disease Surveillance Dashboard',
        'actions' => [
            [
                'title' => 'Disease Surveillance & Outbreak Reports',
                'desc' => 'Emergency outbreak alert triage, epidemiological investigation, and quarantine orders.',
                'icon' => 'bi-exclamation-triangle-fill',
                'url' => $rel_path . 'pages/modules/sms/outbreak_report.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=sms',
                'badge' => 'Surveillance'
            ],
            [
                'title' => 'Immunization & Vaccination Campaigns',
                'desc' => 'Mass vaccination programmes, FMD, Rabies, Black Quarter, and Haemorrhagic Septicemia.',
                'icon' => 'bi-shield-plus',
                'url' => $rel_path . 'pages/modules/sms/immunization.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=sms',
                'badge' => 'Immunization'
            ],
            [
                'title' => 'Mobile Veterinary Clinics',
                'desc' => 'Remote field deployment routes, village animal health camps, and clinical delivery.',
                'icon' => 'bi-truck',
                'url' => $rel_path . 'pages/modules/sms/mobile_clinics.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=sms',
                'badge' => 'Field Clinics'
            ],
            [
                'title' => 'Veterinary Drug Maintenance & Stocks',
                'desc' => 'Therapeutic pharmaceutical allocation, antibiotic stewardship, and expiration registers.',
                'icon' => 'bi-prescription2',
                'url' => $rel_path . 'pages/modules/sms/drug_maintenance.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=sms',
                'badge' => 'Pharmacy'
            ],
            [
                'title' => 'Advanced Programme & Specialist Diary',
                'desc' => 'Field consultation schedule, technical audits, and monthly itineraries.',
                'icon' => 'bi-journal-bookmark-fill',
                'url' => $rel_path . 'pages/modules/sms/my_diary.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=sms',
                'badge' => 'Planning'
            ],
            [
                'title' => 'Specialist Office Details',
                'desc' => 'Institutional inventory, equipment, vehicle fleet, and personnel.',
                'icon' => 'bi-building',
                'url' => $rel_path . 'pages/modules/sms/office_details.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=sms',
                'badge' => 'Registry'
            ]
        ]
    ],
    'deputy_director_hq_1' => [
        'title' => 'Deputy Director - H/Q-1 (Planning)',
        'subtitle' => 'Provincial Planning, Range Performance, Annual Targets & Development Projects',
        'icon' => 'bi-kanban-fill',
        'badge' => 'Headquarters Directorate',
        'gradient' => 'linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%)',
        'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
        'dashboard_title' => 'Provincial Planning, Targets & Project Metrics Dashboard',
        'actions' => [
            [
                'title' => 'Range Statistical Details',
                'desc' => 'Provincial database of all 26 Veterinary Ranges, production volumes, and indicators.',
                'icon' => 'bi-geo-alt-fill',
                'url' => $rel_path . 'pages/planning_dd/range_details.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'Ranges'
            ],
            [
                'title' => 'Annual Targets & Quotas',
                'desc' => 'Strategic KPI allocation for AI, calves born, vaccinations, and farmer trainings.',
                'icon' => 'bi-bullseye',
                'url' => $rel_path . 'pages/modules/veterinary/annual_targets.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'KPIs'
            ],
            [
                'title' => 'Monthly & Annual Reports Repository',
                'desc' => 'Consolidated statutory performance reports, departmental returns, and summaries.',
                'icon' => 'bi-file-earmark-bar-graph-fill',
                'url' => $rel_path . 'pages/modules/veterinary/monthly-annual-reports.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'Reporting'
            ],
            [
                'title' => 'PSDG / CBG Development Projects',
                'desc' => 'Public sector development grants, physical milestone tracking, and financial absorption.',
                'icon' => 'bi-graph-up-arrow',
                'url' => $rel_path . 'pages/modules/project/psdg_projects.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'Projects'
            ],
            [
                'title' => 'Headquarters Office Details',
                'desc' => 'Central department assets, furniture, machinery, and transfer administration.',
                'icon' => 'bi-building-fill-gear',
                'url' => $rel_path . 'pages/planning_dd/office_details.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'HQ Assets'
            ]
        ]
    ],
    'deputy_director_hq_2' => [
        'title' => 'Deputy Director - H/Q-2 (Regulatory & Admin)',
        'subtitle' => 'Regulatory Enforcement, Animal Quarantine, Breeding Policy & Administrative Supervision',
        'icon' => 'bi-shield-shaded',
        'badge' => 'Regulatory Operations',
        'gradient' => 'linear-gradient(135deg, #312e81 0%, #4338ca 100%)',
        'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
        'dashboard_title' => 'Regulatory Compliance & Livestock Standards Dashboard',
        'actions' => [
            [
                'title' => 'Regulatory Functions & Animal Acts',
                'desc' => 'Enforcement of Animals Act, meat inspection rules, and transport permits.',
                'icon' => 'bi-patch-check-fill',
                'url' => $rel_path . 'pages/modules/veterinary/regulatory_functions.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'Compliance'
            ],
            [
                'title' => 'Livestock Breeding Monitoring',
                'desc' => 'Oversight of semen quality, liquid nitrogen logistics, and breeding farms.',
                'icon' => 'bi-diagram-3',
                'url' => $rel_path . 'pages/modules/veterinary/animal_breeding.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'Genetics'
            ],
            [
                'title' => 'Clean Sri Lanka Programmes',
                'desc' => 'Environmental bio-security, farm waste hygiene, and public health campaigns.',
                'icon' => 'bi-recycle',
                'url' => $rel_path . 'pages/modules/veterinary/clean_sri_lanka.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'Hygiene'
            ],
            [
                'title' => 'Provincial Asset Monitoring',
                'desc' => 'Capital machinery audits, departmental vehicles, and inter-unit transfers.',
                'icon' => 'bi-box-seam-fill',
                'url' => $rel_path . 'pages/planning_dd/office_details.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=planning_dd',
                'badge' => 'Assets'
            ]
        ]
    ],
    'deputy_director_district' => [
        'title' => 'Deputy Director - District',
        'subtitle' => 'District-Wide Administration, Task Delegation, Range Supervision & Revenue Management',
        'icon' => 'bi-geo-alt-fill',
        'badge' => 'District Secretariat',
        'gradient' => 'linear-gradient(135deg, #701a75 0%, #86198f 100%)',
        'dashboard_url' => $rel_path . 'dashboard.php?view=district',
        'dashboard_title' => 'District Aggregated Statistical & Financial Dashboard',
        'actions' => [
            [
                'title' => 'Office Details (District Scope)',
                'desc' => 'Manage Lands, Buildings, Vehicles, Furniture, Machinery, Counter Foils, and HR.',
                'icon' => 'bi-building',
                'url' => $rel_path . 'pages/modules/district/office_details.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=district',
                'badge' => 'Office Details'
            ],
            [
                'title' => 'Task Delegation & Quick Actions',
                'desc' => 'District-wide assignment of Clinical Services, Animal Health, and Reports to Surgeons.',
                'icon' => 'bi-person-check-fill',
                'url' => $rel_path . 'pages/modules/district/task_assignments.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=district',
                'badge' => 'Delegation'
            ],
            [
                'title' => 'Range Veterinary Officers Roster',
                'desc' => 'Supervise all Government Veterinary Surgeons and range officers across the district.',
                'icon' => 'bi-person-badge',
                'url' => $rel_path . 'pages/modules/district/range_veterinary_officers.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=district',
                'badge' => 'Roster'
            ],
            [
                'title' => 'Regional Farms in District',
                'desc' => 'Direct oversight of state regional livestock and poultry breeding stations.',
                'icon' => 'bi-flower1',
                'url' => $rel_path . 'pages/modules/district/regional_farms.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=district',
                'badge' => 'Farms'
            ],
            [
                'title' => 'District Training Centers',
                'desc' => 'Farmer training institutions, capacity building sessions, and produce management.',
                'icon' => 'bi-mortarboard-fill',
                'url' => $rel_path . 'pages/modules/district/training_centers.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=district',
                'badge' => 'Training'
            ],
            [
                'title' => 'Revenue & Financial Aggregates',
                'desc' => 'Collection registers, service fees, counter foil accountability, and audit totals.',
                'icon' => 'bi-currency-exchange',
                'url' => $rel_path . 'pages/modules/district/district_revenue_summary.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=district',
                'badge' => 'Revenue'
            ]
        ]
    ],
    'range_veterinary_officer' => [
        'title' => 'Range Veterinary Officer',
        'subtitle' => 'Frontline Clinical Healthcare, Artificial Insemination, Vaccinations & Farmer Services',
        'icon' => 'bi-hospital-fill',
        'badge' => 'Frontline Clinical',
        'gradient' => 'linear-gradient(135deg, #0e7490 0%, #0891b2 100%)',
        'dashboard_url' => $rel_path . 'dashboard.php?view=veterinary_office',
        'dashboard_title' => 'Veterinary Range Operations & Health Statistics Dashboard',
        'actions' => [
            [
                'title' => 'Clinical Services & Outpatient Log',
                'desc' => 'Frontline treatment of farm animals, surgical procedures, and prescriptions.',
                'icon' => 'bi-heart-pulse',
                'url' => $rel_path . 'pages/modules/veterinary/range_details.php?section=clinical_services',
                'dashboard_url' => $rel_path . 'dashboard.php?view=veterinary_office',
                'badge' => 'Clinical'
            ],
            [
                'title' => 'Animal Health & Disease Control',
                'desc' => 'Preventative treatments, outbreak reporting, and mandatory vaccinations.',
                'icon' => 'bi-shield-plus',
                'url' => $rel_path . 'pages/modules/veterinary/range_details.php?section=animal_health',
                'dashboard_url' => $rel_path . 'dashboard.php?view=veterinary_office',
                'badge' => 'Health'
            ],
            [
                'title' => 'Artificial Insemination (AI) & Breeding',
                'desc' => 'Bovine and caprine insemination logs, pregnancy diagnostics, and calf registrations.',
                'icon' => 'bi-diagram-3-fill',
                'url' => $rel_path . 'pages/modules/veterinary/range_details.php?section=animal_breeding',
                'dashboard_url' => $rel_path . 'dashboard.php?view=veterinary_office',
                'badge' => 'Breeding'
            ],
            [
                'title' => 'Dairy Hubs & Milk Yield Monitoring',
                'desc' => 'Farmer cooperative milk collection centers, chilling units, and production metrics.',
                'icon' => 'bi-cup-hot-fill',
                'url' => $rel_path . 'pages/modules/veterinary/range_details.php?section=dairy_hub',
                'dashboard_url' => $rel_path . 'dashboard.php?view=veterinary_office',
                'badge' => 'Dairy'
            ],
            [
                'title' => 'Range Office Asset & Staff Details',
                'desc' => 'Field dispensary inventory, surgical kits, vehicle logs, and office staff.',
                'icon' => 'bi-building-check',
                'url' => $rel_path . 'pages/modules/veterinary/office_details.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=veterinary_office',
                'badge' => 'Range Office'
            ],
            [
                'title' => 'Farmer Trainings & Advisory',
                'desc' => 'Grassroots livestock education workshops, fodder development, and clean milk practices.',
                'icon' => 'bi-mortarboard',
                'url' => $rel_path . 'pages/modules/veterinary/range_details.php?section=trainings',
                'dashboard_url' => $rel_path . 'dashboard.php?view=veterinary_office',
                'badge' => 'Advisory'
            ]
        ]
    ],
    'training_centers' => [
        'title' => 'Training Centers',
        'subtitle' => 'Farmer Education, Livestock Capacity Building, Perishables & Institutional Revenue',
        'icon' => 'bi-mortarboard-fill',
        'badge' => 'Vocational Education',
        'gradient' => 'linear-gradient(135deg, #c2410c 0%, #ea580c 100%)',
        'dashboard_url' => $rel_path . 'dashboard.php?view=training',
        'dashboard_title' => 'Training Center Operations & Trainee Analytics Dashboard',
        'actions' => [
            [
                'title' => 'Advance Programme & Training Calendar',
                'desc' => 'Scheduled residential farmer courses, youth livestock workshops, and agendas.',
                'icon' => 'bi-calendar2-week-fill',
                'url' => $rel_path . 'pages/modules/training/advanced_programme.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=training',
                'badge' => 'Calendar'
            ],
            [
                'title' => 'Monthly Income & Fee Collections',
                'desc' => 'Hostel accommodation receipts, course enrollment dues, and audit remittances.',
                'icon' => 'bi-cash-stack',
                'url' => $rel_path . 'pages/modules/training/monthly_income_summary.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=training',
                'badge' => 'Financial'
            ],
            [
                'title' => 'Produce Register (Perishables)',
                'desc' => 'Farm crop yields, dairy harvests, perishable inventory, and institutional kitchen use.',
                'icon' => 'bi-journal-text',
                'url' => $rel_path . 'pages/modules/training/produce_register.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=training',
                'badge' => 'Perishables'
            ],
            [
                'title' => 'Training Center Office Details',
                'desc' => 'Lecture hall facilities, AV equipment, student accommodation, and campus assets.',
                'icon' => 'bi-building-fill',
                'url' => $rel_path . 'pages/modules/training/office_details.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=training',
                'badge' => 'Campus Assets'
            ]
        ]
    ],
    'regional_farms' => [
        'title' => 'Regional Farms',
        'subtitle' => 'Livestock Breeding Stock, Hatchery Operations, Animal Production & State Farm Registers',
        'icon' => 'bi-tree-fill',
        'badge' => 'State Livestock Stations',
        'gradient' => 'linear-gradient(135deg, #15803d 0%, #16a34a 100%)',
        'dashboard_url' => $rel_path . 'dashboard.php?view=farms',
        'dashboard_title' => 'Regional Farms Production & Herd Performance Dashboard',
        'actions' => [
            [
                'title' => 'Parent Stock & Poultry Flock',
                'desc' => 'Breeder flock management, egg production curves, mortality logs, and biosecurity.',
                'icon' => 'bi-collection-fill',
                'url' => $rel_path . 'pages/modules/farm/parent_stock_operations.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=farms',
                'badge' => 'Parent Stock'
            ],
            [
                'title' => 'Commercial Hatchery Register',
                'desc' => 'Egg setting rosters, candling audits, hatchability percentages, and chick sexing.',
                'icon' => 'bi-egg-fried',
                'url' => $rel_path . 'pages/modules/farm/hatchery_register.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=farms',
                'badge' => 'Hatchery'
            ],
            [
                'title' => 'Cattle & Dairy Herd Register',
                'desc' => 'Purebred dairy cattle, milking records, pasture utilization, and lactation yields.',
                'icon' => 'bi-record-circle-fill',
                'url' => $rel_path . 'pages/modules/farm/cattle_register.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=farms',
                'badge' => 'Dairy Herd'
            ],
            [
                'title' => 'Goat & Buffalo Breeding Stock',
                'desc' => 'Jamnapari/Saanen goat herds and Murrah buffalo stock registries and breeding logs.',
                'icon' => 'bi-diamond-fill',
                'url' => $rel_path . 'pages/modules/farm/buffalo_register.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=farms',
                'badge' => 'Livestock'
            ],
            [
                'title' => 'Feed & Concentrates Management',
                'desc' => 'Ration formulation, silages, commercial feed stocks, and feed conversion ratios.',
                'icon' => 'bi-basket-fill',
                'url' => $rel_path . 'pages/modules/farm/feed_management.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=farms',
                'badge' => 'Feed'
            ],
            [
                'title' => 'Farm Office Details & Machinery',
                'desc' => 'Tractors, milking machines, pasture lands, infrastructure, and farm laborers.',
                'icon' => 'bi-building-gear',
                'url' => $rel_path . 'pages/modules/farm/office_details.php',
                'dashboard_url' => $rel_path . 'dashboard.php?view=farms',
                'badge' => 'Machinery'
            ]
        ]
    ]
];

// Fallback to district if invalid key passed
if (!isset($categories_config[$category_key])) {
    $category_key = 'deputy_director_district';
}

$cat = $categories_config[$category_key];
?>

<div class="container-fluid px-0 py-2">
    <!-- Category Navigation Pill Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-2 bg-light border-bottom">
            <div class="d-flex align-items-center gap-2 overflow-auto py-1 px-2 text-nowrap" id="categoryScrollTabs">
                <span class="small fw-bold text-muted text-uppercase me-2" style="font-size: 11px;">Core Categories:</span>
                <?php foreach ($categories_config as $k => $c): ?>
                    <a href="<?= $rel_path ?>pages/categories/view.php?cat=<?= $k ?>" 
                       class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold <?= $k === $category_key ? 'btn-danger text-white shadow-sm' : 'btn-outline-secondary bg-white' ?>"
                       style="font-size: 12px; transition: all 0.2s;">
                        <i class="bi <?= $c['icon'] ?> me-1"></i> <?= htmlspecialchars($c['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Category Header Hero Banner -->
    <div class="card border-0 shadow-sm rounded-4 text-white p-4 p-md-5 mb-4 position-relative overflow-hidden" 
         style="background: <?= $cat['gradient'] ?>;">
        <div class="position-absolute" style="right: -20px; bottom: -30px; opacity: 0.12; pointer-events: none;">
            <i class="bi <?= $cat['icon'] ?>" style="font-size: 16rem;"></i>
        </div>
        <div class="position-relative" style="z-index: 2; max-width: 820px;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white text-dark small fw-bold mb-3 shadow-sm">
                <i class="bi <?= $cat['icon'] ?> text-danger"></i> <?= htmlspecialchars($cat['badge']) ?>
            </div>
            <h2 class="fw-bold display-6 mb-2"><?= htmlspecialchars($cat['title']) ?></h2>
            <p class="fs-6 opacity-90 mb-4"><?= htmlspecialchars($cat['subtitle']) ?></p>
            
            <!-- Direct Summary Level Route Button -->
            <div class="d-flex flex-wrap gap-3">
                <a href="<?= $cat['dashboard_url'] ?>" class="btn btn-light btn-lg px-4 py-2.5 rounded-3 fw-bold text-dark d-inline-flex align-items-center gap-2 shadow">
                    <i class="bi bi-graph-up-arrow text-danger fs-5"></i>
                    <span>Open Aggregated Statistical Dashboard</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Level Highlight Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-danger bg-white p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                    <i class="bi bi-pie-chart-fill fs-3"></i>
                </div>
                <div>
                    <div class="badge bg-danger rounded-pill px-2.5 py-1 text-uppercase fw-bold mb-1" style="font-size: 10px;">Summary Level</div>
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($cat['dashboard_title']) ?></h5>
                    <p class="text-muted small mb-0">Direct access to consolidated charts, real-time KPI metrics, performance gauges, and historical summaries.</p>
                </div>
            </div>
            <a href="<?= $cat['dashboard_url'] ?>" class="btn btn-outline-danger px-4 py-2 rounded-3 fw-semibold flex-shrink-0 d-inline-flex align-items-center gap-2">
                <span>View Charts & Metrics</span> <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Section Title -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Action Level Functions</h4>
            <p class="text-muted small mb-0">Select an operational function to launch workflows or inspect function-level summaries</p>
        </div>
        <span class="badge bg-secondary rounded-pill px-3 py-1.5"><?= count($cat['actions']) ?> Functions Available</span>
    </div>

    <!-- Action Level Function Cards Grid -->
    <div class="row g-4 mb-5">
        <?php foreach ($cat['actions'] as $action): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 action-function-card bg-white p-4 d-flex flex-column justify-content-between" 
                     style="transition: transform 0.25s ease, box-shadow 0.25s ease;">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="rounded-3 bg-light text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border: 1px solid rgba(80,7,7,0.1);">
                                <i class="bi <?= $action['icon'] ?> fs-4"></i>
                            </div>
                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                <?= htmlspecialchars($action['badge']) ?>
                            </span>
                        </div>
                        <h5 class="fw-bold mb-2 text-dark"><?= htmlspecialchars($action['title']) ?></h5>
                        <p class="text-secondary small mb-4 lh-base"><?= htmlspecialchars($action['desc']) ?></p>
                    </div>

                    <div class="d-flex flex-column gap-2 pt-3 border-top">
                        <a href="<?= $action['url'] ?>" class="btn btn-danger btn-sm rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Open Function</span>
                        </a>
                        <a href="<?= $action['dashboard_url'] ?>" class="btn btn-outline-secondary btn-sm rounded-3 py-1.5 text-muted d-flex align-items-center justify-content-center gap-1" style="font-size: 12px;">
                            <i class="bi bi-graph-up"></i>
                            <span>View Statistical Summary & Charts</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.action-function-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.12) !important;
}
#categoryScrollTabs::-webkit-scrollbar {
    height: 4px;
}
#categoryScrollTabs::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
