<?php
/**
 * MultiVendorX Commission Abilities controller.
 *
 * @package MultiVendorX
 */

namespace MultiVendorX\Abilities\AbilitiesApi;

use MultiVendorX\Abilities\StoreScopedPermission;
use MultiVendorX\Commission\CommissionUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Commission Abilities controller.
 */
class Commissions {

    use StoreScopedPermission;

    /**
     * Register MultiVendorX commission abilities.
     *
     * @return void
     */
    public function register_abilities() {
        wp_register_ability(
            'multivendorx/get-store-commission-summary',
            array(
                'label'               => __( 'Get Store Commission Summary', 'multivendorx' ),
                'description'         => __( 'Retrieve the commission summary for a MultiVendorX store.', 'multivendorx' ),
                'category'            => 'multivendorx',
                'input_schema'        => array(
                    'type'                 => 'object',
                    'properties'           => array(
                        'store_id'   => array(
                            'type'        => 'integer',
                            'description' => __( 'Store ID.', 'multivendorx' ),
                        ),
                        'start_date' => array(
                            'type'        => 'string',
                            'description' => __( 'Start date (Y-m-d).', 'multivendorx' ),
                        ),
                        'end_date'   => array(
                            'type'        => 'string',
                            'description' => __( 'End date (Y-m-d).', 'multivendorx' ),
                        ),
                    ),
                    'required'             => array( 'store_id' ),
                    'additionalProperties' => false,
                ),
                'output_schema'       => array( 'type' => 'object' ),
                'execute_callback'    => array( $this, 'get_store_commission_summary' ),
                'permission_callback' => array( $this, 'store_permissions_check' ),
                'meta'                => array( 'public' => true ),
            )
        );
    }

    /**
     * Retrieve a store's commission summary.
     *
     * @param array $input Ability input.
     * @return array
     */
    public function get_store_commission_summary( $input ) {
        $args = array_filter(
            array(
                'start_date' => $input['start_date'] ?? null,
                'end_date'   => $input['end_date'] ?? null,
            )
        );

        return CommissionUtil::get_commission_summary_for_store( (int) $input['store_id'], false, null, null, $args );
    }
}
