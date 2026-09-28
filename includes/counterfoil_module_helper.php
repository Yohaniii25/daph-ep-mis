<?php
/**
 * includes/counterfoil_module_helper.php
 * Comprehensive Module Connection, Categorization, and Authorized Book Type Mapping
 * for Counterfoil & Register Management.
 */

if (!function_exists('getCounterfoilModuleInfo')) {
    /**
     * Determine module categorization and interactions for a given counterfoil book type
     *
     * @param string $type The counterfoil book type name
     * @return array Categorization metadata and cross-module link details
     */
    function getCounterfoilModuleInfo($type) {
        $clean = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$type));

        // 1. Vehicle Module: Fuel Order
        if (strpos($clean, 'fuelorder') !== false || strpos($clean, 'fuel') !== false) {
            return [
                'category' => 'Vehicle Module',
                'module_key' => 'vehicle',
                'badge_class' => 'bg-warning text-dark border border-warning',
                'icon' => 'bi-truck-front-fill',
                'target_url' => 'vehicles.php',
                'target_name' => 'Vehicle Tracking Module',
                'desc' => 'Hardwired to Fleet Fuel Orders & Vehicle Running Charts'
            ];
        }

        // 2. Human Resource Module: Holiday Warrant & Railway Warrant Goods
        if (strpos($clean, 'holidaywar') !== false || strpos($clean, 'railwaywar') !== false) {
            return [
                'category' => 'Human Resource Module',
                'module_key' => 'hr',
                'badge_class' => 'bg-info-subtle text-info border border-info-subtle',
                'icon' => 'bi-people-fill',
                'target_url' => 'employee_managment.php',
                'target_name' => 'Human Resource Module',
                'desc' => 'Hardwired to Employee Travel Warrants & Movement'
            ];
        }

        // 3. Inventory Items Module: Issue Order & Receive Order
        if (strpos($clean, 'issueorder') !== false || strpos($clean, 'receiveorder') !== false || strpos($clean, 'receiptorder') !== false) {
            return [
                'category' => 'Inventory Items Module',
                'module_key' => 'inventory',
                'badge_class' => 'bg-primary-subtle text-primary border border-primary-subtle',
                'icon' => 'bi-boxes',
                'target_url' => 'office_details.php',
                'target_name' => 'Inventory Items Tracking',
                'desc' => 'Hardwired to Furniture, Machineries, Instruments & Stores'
            ];
        }

        // 4. Office / General: Disease Outbreak Register & OPD Register
        if (strpos($clean, 'diseaseoutbreak') !== false || strpos($clean, 'diseaseout') !== false || strpos($clean, 'opdregister') !== false || strpos($clean, 'opd') !== false) {
            return [
                'category' => 'Office / General',
                'module_key' => 'office',
                'badge_class' => 'bg-secondary-subtle text-secondary border border-secondary',
                'icon' => 'bi-building',
                'target_url' => null,
                'target_name' => 'Internal Office Record',
                'desc' => 'Categorized strictly for internal office tracking'
            ];
        }

        // 5. Authorized Farmer View: 12 exact books
        if (isFarmerRelatedBook($type)) {
            return [
                'category' => 'Farmer Services',
                'module_key' => 'farmer',
                'badge_class' => 'bg-success-subtle text-success border border-success-subtle',
                'icon' => 'bi-person-check-fill',
                'target_url' => '#issueCounterfoilLeafModal',
                'target_name' => 'Individual Certificate/Leaf',
                'desc' => 'Authorized for Farmer Leaf Issuance with NIC Auto-Fetch'
            ];
        }

        // Default General Category
        return [
            'category' => 'Office / General',
            'module_key' => 'office',
            'badge_class' => 'bg-light text-dark border',
            'icon' => 'bi-journal-text',
            'target_url' => null,
            'target_name' => 'General Register',
            'desc' => 'Internal departmental registry'
        ];
    }
}

if (!function_exists('isFarmerRelatedBook')) {
    /**
     * Checks if a counterfoil book type is one of the 12 authorized farmer service books:
     * - AI Register
     * - Animal Transport
     * - AI certificate book
     * - Cash receipt book
     * - Certificate for Slaughter of Buffalo
     * - Health certificate
     * - Ownership voucher
     * - Register of cattle branded
     * - Register for Animal Identification (Schedule 08)
     * - ARV Register
     * - Animal Birth control Register
     * - Produce Register
     *
     * @param string $type
     * @return bool
     */
    function isFarmerRelatedBook($type) {
        $clean = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$type));
        $authorized_keys = [
            'airegister',
            'animaltransport',
            'aicertificatebook',
            'aicertificate',
            'cashreceiptbook',
            'cashreceipt',
            'certificateforslaughterofbuffalo',
            'healthcertificate',
            'ownershipvoucher',
            'registerofcattlebranded',
            'cattlebranded',
            'registerforanimalidentificationschedule08',
            'registerforanimalidentification',
            'pivschedule08',
            'schedule08',
            'arvregister',
            'animalbirthcontrolregister',
            'produceregister'
        ];
        foreach ($authorized_keys as $ak) {
            if ($clean === $ak || strpos($clean, $ak) !== false || strpos($ak, $clean) !== false) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('getCashBookCategories')) {
    /**
     * Standard 4 Government Veterinary Revenue Stream Categories and Canonical Items
     *
     * @return array
     */
    function getCashBookCategories() {
        return [
            'tab-consultations' => [
                'title'       => 'Consultations & Certificates',
                'short_title' => 'Consultations',
                'icon'        => 'bi-clipboard2-pulse',
                'badge_class' => 'bg-primary text-white',
                'items'       => [
                    "Consultation fee for cross breed Pet's",
                    "Consultation fee for pure breed dog",
                    "Health certificate"
                ]
            ],
            'tab-poultry' => [
                'title'       => 'Poultry Sales & Semen',
                'short_title' => 'Poultry & Semen',
                'icon'        => 'bi-egg-fried',
                'badge_class' => 'bg-warning text-dark',
                'items'       => [
                    "Day old unsexed backyard chicks",
                    "Semen straws for AI services",
                    "Day Old Cockerels"
                ]
            ],
            'tab-treatments' => [
                'title'       => 'Vaccines, Surgeries & Treatments',
                'short_title' => 'Treatments & Surgeries',
                'icon'        => 'bi-heart-pulse',
                'badge_class' => 'bg-success text-white',
                'items'       => [
                    "Wound ress (Wound dress)",
                    "Ranikhet 1st dose & 2nd Dose",
                    "OHE - Dog",
                    "OHE -Cat"
                ]
            ],
            'tab-post-mortems' => [
                'title'       => 'Post Mortems',
                'short_title' => 'Post Mortems',
                'icon'        => 'bi-clipboard-x',
                'badge_class' => 'bg-danger text-white',
                'items'       => [
                    "Poultry -Bird post mortems",
                    "Post moturm Rabbit"
                ]
            ]
        ];
    }
}

if (!function_exists('map_to_canonical_item')) {
    /**
     * Map any individual receipt purpose / activity description to the canonical Cashbook item and category
     *
     * @param string $db_item_name
     * @param array|null $tab_categories
     * @return array [0 => tab_key, 1 => canonical_item_name]
     */
    function map_to_canonical_item($db_item_name, $tab_categories = null) {
        if ($tab_categories === null) {
            $tab_categories = getCashBookCategories();
        }

        $clean = mb_strtolower(trim((string)$db_item_name));

        if (empty($clean)) {
            return ['tab-consultations', "Consultation fee for cross breed Pet's"];
        }

        // Direct exact matches first
        foreach ($tab_categories as $t_key => $t_info) {
            foreach ($t_info['items'] as $item) {
                if ($clean === mb_strtolower(trim($item))) {
                    return [$t_key, $item];
                }
            }
        }

        // Detailed alias and keyword dictionary
        $aliases = [
            // Treatments, Surgeries & Vaccines
            'wound ress'                   => ['tab-treatments', 'Wound ress (Wound dress)'],
            'wound dress'                  => ['tab-treatments', 'Wound ress (Wound dress)'],
            'wound dressing'               => ['tab-treatments', 'Wound ress (Wound dress)'],
            'wound ress (wound dress)'     => ['tab-treatments', 'Wound ress (Wound dress)'],
            'ranikhet 1st dose & 2nd dose' => ['tab-treatments', 'Ranikhet 1st dose & 2nd Dose'],
            'ranikhet 1st dose and 2nd dose'=>['tab-treatments', 'Ranikhet 1st dose & 2nd Dose'],
            'ranikhet'                     => ['tab-treatments', 'Ranikhet 1st dose & 2nd Dose'],
            'ranikhet vaccine'             => ['tab-treatments', 'Ranikhet 1st dose & 2nd Dose'],
            'ohe - dog'                    => ['tab-treatments', 'OHE - Dog'],
            'ohe -dog'                     => ['tab-treatments', 'OHE - Dog'],
            'ohe dog'                      => ['tab-treatments', 'OHE - Dog'],
            'dog ohe'                      => ['tab-treatments', 'OHE - Dog'],
            'dog surgery'                  => ['tab-treatments', 'OHE - Dog'],
            'ohe -cat'                     => ['tab-treatments', 'OHE -Cat'],
            'ohe - cat'                    => ['tab-treatments', 'OHE -Cat'],
            'ohe cat'                      => ['tab-treatments', 'OHE -Cat'],
            'cat ohe'                      => ['tab-treatments', 'OHE -Cat'],
            'cat surgery'                  => ['tab-treatments', 'OHE -Cat'],
            
            // Consultations & Certificates
            "consultation fee for cross breed pet's" => ['tab-consultations', "Consultation fee for cross breed Pet's"],
            "consultation fee for cross breed pets"  => ['tab-consultations', "Consultation fee for cross breed Pet's"],
            "cross breed consultation"               => ['tab-consultations', "Consultation fee for cross breed Pet's"],
            "pet consultation"                       => ['tab-consultations', "Consultation fee for cross breed Pet's"],
            "consultation - cross breed"             => ['tab-consultations', "Consultation fee for cross breed Pet's"],
            "consultation fee for pure breed dog"    => ['tab-consultations', "Consultation fee for pure breed dog"],
            "pure breed dog consultation"            => ['tab-consultations', "Consultation fee for pure breed dog"],
            "dog consultation"                       => ['tab-consultations', "Consultation fee for pure breed dog"],
            "consultation"                           => ['tab-consultations', "Consultation fee for pure breed dog"],
            "health certificate"                     => ['tab-consultations', "Health certificate"],
            "health cert"                            => ['tab-consultations', "Health certificate"],
            "animal health certificate"              => ['tab-consultations', "Health certificate"],

            // Poultry Sales & Semen
            "day old unsexed backyard chicks" => ['tab-poultry', "Day old unsexed backyard chicks"],
            "backyard chicks"                 => ['tab-poultry', "Day old unsexed backyard chicks"],
            "chicks"                          => ['tab-poultry', "Day old unsexed backyard chicks"],
            "chick sales"                     => ['tab-poultry', "Day old unsexed backyard chicks"],
            "semen straws for ai services"    => ['tab-poultry', "Semen straws for AI services"],
            "semen straws"                    => ['tab-poultry', "Semen straws for AI services"],
            "ai semen straws"                 => ['tab-poultry', "Semen straws for AI services"],
            "ai semen"                        => ['tab-poultry', "Semen straws for AI services"],
            "day old cockerels"               => ['tab-poultry', "Day Old Cockerels"],
            "cockerels"                       => ['tab-poultry', "Day Old Cockerels"],
            "cockerel"                        => ['tab-poultry', "Day Old Cockerels"],

            // Post Mortems
            "poultry -bird post mortems"      => ['tab-post-mortems', "Poultry -Bird post mortems"],
            "poultry - bird post mortems"     => ['tab-post-mortems', "Poultry -Bird post mortems"],
            "bird post mortem"                => ['tab-post-mortems', "Poultry -Bird post mortems"],
            "poultry post mortem"             => ['tab-post-mortems', "Poultry -Bird post mortems"],
            "post moturm rabbit"              => ['tab-post-mortems', "Post moturm Rabbit"],
            "post mortem rabbit"              => ['tab-post-mortems', "Post moturm Rabbit"],
            "rabbit post mortem"              => ['tab-post-mortems', "Post moturm Rabbit"]
        ];

        if (isset($aliases[$clean])) {
            return $aliases[$clean];
        }

        // Substring / fuzzy keyword scanning
        if (strpos($clean, 'dog consult') !== false || strpos($clean, 'pure breed') !== false) {
            return ['tab-consultations', "Consultation fee for pure breed dog"];
        }
        if (strpos($clean, 'cross breed') !== false || strpos($clean, 'pet consult') !== false) {
            return ['tab-consultations', "Consultation fee for cross breed Pet's"];
        }
        if (strpos($clean, 'consult') !== false) {
            return ['tab-consultations', "Consultation fee for pure breed dog"];
        }
        if (strpos($clean, 'certificate') !== false || strpos($clean, 'health cert') !== false) {
            return ['tab-consultations', "Health certificate"];
        }

        if (strpos($clean, 'chick') !== false) {
            return ['tab-poultry', "Day old unsexed backyard chicks"];
        }
        if (strpos($clean, 'cockerel') !== false) {
            return ['tab-poultry', "Day Old Cockerels"];
        }
        if (strpos($clean, 'semen') !== false || strpos($clean, 'ai straw') !== false) {
            return ['tab-poultry', "Semen straws for AI services"];
        }

        if (strpos($clean, 'wound') !== false || strpos($clean, 'dress') !== false) {
            return ['tab-treatments', "Wound ress (Wound dress)"];
        }
        if (strpos($clean, 'ranikhet') !== false) {
            return ['tab-treatments', "Ranikhet 1st dose & 2nd Dose"];
        }
        if (strpos($clean, 'ohe') !== false && strpos($clean, 'cat') !== false) {
            return ['tab-treatments', "OHE -Cat"];
        }
        if (strpos($clean, 'ohe') !== false || strpos($clean, 'spay') !== false || strpos($clean, 'neut') !== false) {
            return ['tab-treatments', "OHE - Dog"];
        }
        if (strpos($clean, 'vaccin') !== false || strpos($clean, 'surger') !== false || strpos($clean, 'treatment') !== false) {
            return ['tab-treatments', "Wound ress (Wound dress)"];
        }

        if (strpos($clean, 'rabbit') !== false && (strpos($clean, 'post') !== false || strpos($clean, 'mortem') !== false)) {
            return ['tab-post-mortems', "Post moturm Rabbit"];
        }
        if (strpos($clean, 'post') !== false || strpos($clean, 'mortem') !== false || strpos($clean, 'moturm') !== false || strpos($clean, 'autopsy') !== false) {
            return ['tab-post-mortems', "Poultry -Bird post mortems"];
        }

        return ['tab-other', trim($db_item_name)];
    }
}

