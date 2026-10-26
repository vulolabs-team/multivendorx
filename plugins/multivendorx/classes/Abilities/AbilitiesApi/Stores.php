<?php
/**
 * MultiVendorX Store Abilities controller.
 *
 * @package MultiVendorX
 */

namespace MultiVendorX\Abilities\AbilitiesApi;

use MultiVendorX\Abilities\StoreScopedPermission;
use MultiVendorX\Store\Store;
use MultiVendorX\Utill;

defined( 'ABSPATH' ) || exit;

/**
 * Store Abilities controller.
 *
 * Every ability here is scoped to the specific store id in its input, via
 * StoreUtil::current_user_can_manage_store() - a store owner can act on a
 * store they own/manage, an admin can act on any store.
 */
class Stores {

    use StoreScopedPermission;

    /**
     * Register MultiVendorX store abilities.
     *
     * @return void
     */
    public function register_abilities() {
        wp_register_ability(
            'multivendorx/get-store',
            array(
                'label'               => __( 'Get Store', 'multivendorx' ),
                'description'         => __( 'Retrieve a MultiVendorX store\'s details.', 'multivendorx' ),
                'category'            => 'multivendorx',
                'input_schema'        => array(
                    'type'                 => 'object',
                    'properties'           => array(
                        'store_id' => array(
                            'type'        => 'integer',
                            'description' => __( 'Store ID.', 'multivendorx' ),
                        ),
                    ),
                    'required'             => array( 'store_id' ),
                    'additionalProperties' => false,
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'          => array( 'type' => 'integer' ),
                        'name'        => array( 'type' => 'string' ),
                        'slug'        => array( 'type' => 'string' ),
                        'description' => array( 'type' => 'string' ),
                        'status'      => array( 'type' => 'string' ),
                    ),
                ),
                'execute_callback'    => array( $this, 'get_store' ),
                'permission_callback' => array( $this, 'store_permissions_check' ),
                'meta'                => array( 'public' => true ),
            )
        );

        wp_register_ability(
            'multivendorx/update-store-description',
            array(
                'label'               => __( 'Update Store Description', 'multivendorx' ),
                'description'         => __( 'Update a MultiVendorX store\'s description.', 'multivendorx' ),
                'category'            => 'multivendorx',
                'input_schema'        => array(
                    'type'                 => 'object',
                    'properties'           => array(
                        'store_id'    => array(
                            'type'        => 'integer',
                            'description' => __( 'Store ID.', 'multivendorx' ),
                        ),
                        'description' => array(
                            'type'        => 'string',
                            'description' => __( 'New store description.', 'multivendorx' ),
                        ),
                    ),
                    'required'             => array( 'store_id', 'description' ),
                    'additionalProperties' => false,
                ),
                'output_schema'       => array( 'type' => 'boolean' ),
                'execute_callback'    => array( $this, 'update_store_description' ),
                'permission_callback' => array( $this, 'store_permissions_check' ),
                'meta'                => array( 'public' => true ),
            )
        );
    }

    /**
     * Retrieve a store's details.
     *
     * @param array $input Ability input.
     * @return array|\WP_Error
     */
    public function get_store( $input ) {
        $store = new Store( (int) $input['store_id'] );

        if ( ! $store->exists() ) {
            return new \WP_Error( 'multivendorx_store_not_found', __( 'Store not found.', 'multivendorx' ) );
        }

        return array(
            'id'          => $store->get_id(),
            'name'        => $store->get( Utill::STORE_SETTINGS_KEYS['name'] ),
            'slug'        => $store->get( Utill::STORE_SETTINGS_KEYS['slug'] ),
            'description' => $store->get( Utill::STORE_SETTINGS_KEYS['description'] ),
            'status'      => $store->get( Utill::STORE_SETTINGS_KEYS['status'] ),
        );
    }

    /**
     * Update a store's description.
     *
     * @param array $input Ability input.
     * @return bool|\WP_Error
     */
    public function update_store_description( $input ) {
        $store = new Store( (int) $input['store_id'] );

        if ( ! $store->exists() ) {
            return new \WP_Error( 'multivendorx_store_not_found', __( 'Store not found.', 'multivendorx' ) );
        }

        $store->set( Utill::STORE_SETTINGS_KEYS['description'], wp_kses_post( (string) $input['description'] ) );

        return (bool) $store->save();
    }
}
