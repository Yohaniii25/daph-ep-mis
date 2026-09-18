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
