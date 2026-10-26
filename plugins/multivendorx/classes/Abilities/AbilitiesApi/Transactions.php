<?php
/**
 * MultiVendorX Transaction Abilities controller.
 *
 * @package MultiVendorX
 */

namespace MultiVendorX\Abilities\AbilitiesApi;

use MultiVendorX\Abilities\StoreScopedPermission;
use MultiVendorX\Transaction\Transaction;
use MultiVendorX\Utill;

defined( 'ABSPATH' ) || exit;

/**
 * Transaction Abilities controller.
 */
class Transactions {

    use StoreScopedPermission;

    /**
     * Register MultiVendorX transaction abilities.
     *
     * @return void
     */
    public function register_abilities() {
        wp_register_ability(
            'multivendorx/get-store-transactions',
            array(
                'label'               => __( 'Get Store Transactions', 'multivendorx' ),
                'description'         => __( 'Retrieve a MultiVendorX store\'s transactions.', 'multivendorx' ),
                'category'            => 'multivendorx',
                'input_schema'        => array(
                    'type'                 => 'object',
                    'properties'           => array(
                        'store_id' => array(
                            'type'        => 'integer',
                            'description' => __( 'Store ID.', 'multivendorx' ),
                        ),
                        'limit'    => array(
                            'type'        => 'integer',
                            'description' => __( 'Number of transactions to return.', 'multivendorx' ),
                        ),
                    ),
                    'required'             => array( 'store_id' ),
                    'additionalProperties' => false,
                ),
                'output_schema'       => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'               => array( 'type' => 'integer' ),
                            'amount'           => array( 'type' => 'string' ),
                            'balance'          => array( 'type' => 'string' ),
                            'status'           => array( 'type' => 'string' ),
                            'transaction_type' => array( 'type' => 'string' ),
                            'created_at'       => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'get_store_transactions' ),
                'permission_callback' => array( $this, 'store_permissions_check' ),
                'meta'                => array( 'public' => true ),
            )
        );
    }

    /**
     * Retrieve a store's transactions.
     *
     * @param array $input Ability input.
     * @return array
     */
    public function get_store_transactions( $input ) {
        $store_id = (int) $input['store_id'];

        $transactions = Transaction::get_transaction_information(
            array(
                'store_id' => $store_id,
                'limit'    => ! empty( $input['limit'] ) ? absint( $input['limit'] ) : 10,
                'order_by' => 'created_at',
                'order'    => 'DESC',
            )
        );

        return array_map(
            function ( $row ) {
                return array(
                    'id'               => (int) $row['id'],
                    'amount'           => $row['amount'],
                    'balance'          => $row['balance'],
                    'status'           => $row['status'],
                    'transaction_type' => $row['transaction_type'],
                    'created_at'       => Utill::multivendorx_rest_prepare_date_response( $row['created_at'] ),
                );
            },
            $transactions
        );
    }
}
