<?php
/**
 * MultiVendorX Customer Queries Abilities.
 *
 * @package MultiVendorX
 */

namespace MultiVendorX\CustomerQueries;

use MultiVendorX\Abilities\StoreScopedPermission;
use MultiVendorX\Store\StoreUtil;
use MultiVendorX\Utill;

defined( 'ABSPATH' ) || exit;

/**
 * Customer Queries Abilities.
 *
 * Registers abilities on the shared 'multivendorx' category (already
 * registered centrally by \MultiVendorX\Abilities\Abilities), only while
 * this module is active.
 */
class Abilities {

    use StoreScopedPermission;

    /**
     * Abilities constructor.
     */
    public function __construct() {
        add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
    }

    /**
     * Register customer-question abilities.
     *
     * @return void
     */
    public function register_abilities() {
        wp_register_ability(
            'multivendorx/get-store-customer-questions',
            array(
                'label'               => __( 'Get Store Customer Questions', 'multivendorx' ),
                'description'         => __( 'Retrieve customer product questions for a store.', 'multivendorx' ),
                'category'            => 'multivendorx',
                'input_schema'        => array(
                    'type'                 => 'object',
                    'properties'           => array(
                        'store_id' => array(
                            'type'        => 'integer',
                            'description' => __( 'Store ID.', 'multivendorx' ),
                        ),
                        'status'   => array(
                            'type'        => 'string',
                            'description' => __( 'Filter by answer status.', 'multivendorx' ),
                            'enum'        => array( 'has_answer', 'no_answer' ),
                        ),
                    ),
                    'required'             => array( 'store_id' ),
                    'additionalProperties' => false,
                ),
                'output_schema'       => array( 'type' => 'array' ),
                'execute_callback'    => array( $this, 'get_store_customer_questions' ),
                'permission_callback' => array( $this, 'store_permissions_check' ),
                'meta'                => array( 'public' => true ),
            )
        );

        wp_register_ability(
            'multivendorx/answer-customer-question',
            array(
                'label'               => __( 'Answer Customer Question', 'multivendorx' ),
                'description'         => __( 'Answer a customer\'s product question.', 'multivendorx' ),
                'category'            => 'multivendorx',
                'input_schema'        => array(
                    'type'                 => 'object',
                    'properties'           => array(
                        'id'          => array(
                            'type'        => 'integer',
                            'description' => __( 'Question ID.', 'multivendorx' ),
                        ),
                        'answer_text' => array(
                            'type'        => 'string',
                            'description' => __( 'Answer text.', 'multivendorx' ),
                        ),
                    ),
                    'required'             => array( 'id', 'answer_text' ),
                    'additionalProperties' => false,
                ),
                'output_schema'       => array( 'type' => 'boolean' ),
                'execute_callback'    => array( $this, 'answer_customer_question' ),
                'permission_callback' => array( $this, 'question_permissions_check' ),
                'meta'                => array( 'public' => true ),
            )
        );
    }

    /**
     * Check whether the current user can manage the question's store.
     *
     * @param array $input Ability input.
     * @return bool
     */
    public function question_permissions_check( $input ) {
        $question = reset( Util::query_questions( array( 'id' => (int) ( $input['id'] ?? 0 ) ) ) );

        return $question && StoreUtil::current_user_can_manage_store( $question['store_id'] ?? 0 );
    }

    /**
     * Retrieve a store's customer questions.
     *
     * @param array $input Ability input.
     * @return array
     */
    public function get_store_customer_questions( $input ) {
        $args = array( 'store_id' => (int) $input['store_id'] );

        if ( ! empty( $input['status'] ) ) {
            $args[ $input['status'] ] = true;
        }

        return array_map( array( $this, 'prepare_question' ), Util::query_questions( $args ) );
    }

    /**
     * Answer a customer question.
     *
     * @param array $input Ability input.
     * @return bool|\WP_Error
     */
    public function answer_customer_question( $input ) {
        $id = (int) $input['id'];

        if ( ! reset( Util::query_questions( array( 'id' => $id ) ) ) ) {
            return new \WP_Error( 'multivendorx_question_not_found', __( 'Question not found.', 'multivendorx' ) );
        }

        return (bool) Util::update_question(
            $id,
            array(
                'answer_text' => sanitize_textarea_field( (string) $input['answer_text'] ),
                'answer_by'   => MultiVendorX()->current_user_id,
            )
        );
    }

    /**
     * Format a question row for ability output.
     *
     * @param array $question Question row.
     * @return array
     */
    private function prepare_question( $question ) {
        return array(
            'id'            => (int) $question['id'],
            'product_id'    => (int) $question['product_id'],
            'question_text' => $question['question_text'],
            'answer_text'   => $question['answer_text'],
            'question_date' => Utill::multivendorx_rest_prepare_date_response( $question['question_date'] ),
        );
    }
}
