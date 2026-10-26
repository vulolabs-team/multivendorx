<?php
/**
 * MultiVendorX Store Review Abilities.
 *
 * @package MultiVendorX
 */

namespace MultiVendorX\StoreReview;

use MultiVendorX\Abilities\StoreScopedPermission;
use MultiVendorX\Store\StoreUtil;
use MultiVendorX\Utill;

defined( 'ABSPATH' ) || exit;

/**
 * Store Review Abilities.
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
     * Register store-review abilities.
     *
     * @return void
     */
    public function register_abilities() {
        wp_register_ability(
            'multivendorx/get-store-reviews',
            array(
                'label'               => __( 'Get Store Reviews', 'multivendorx' ),
                'description'         => __( 'Retrieve a store\'s approved reviews.', 'multivendorx' ),
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
                            'description' => __( 'Number of reviews to return.', 'multivendorx' ),
                        ),
                    ),
                    'required'             => array( 'store_id' ),
                    'additionalProperties' => false,
                ),
                'output_schema'       => array( 'type' => 'array' ),
                'execute_callback'    => array( $this, 'get_store_reviews' ),
                'permission_callback' => array( $this, 'store_permissions_check' ),
                'meta'                => array( 'public' => true ),
            )
        );

        wp_register_ability(
            'multivendorx/reply-to-store-review',
            array(
                'label'               => __( 'Reply To Store Review', 'multivendorx' ),
                'description'         => __( 'Reply to a customer review on a store.', 'multivendorx' ),
                'category'            => 'multivendorx',
                'input_schema'        => array(
                    'type'                 => 'object',
                    'properties'           => array(
                        'id'    => array(
                            'type'        => 'integer',
                            'description' => __( 'Review ID.', 'multivendorx' ),
                        ),
                        'reply' => array(
                            'type'        => 'string',
                            'description' => __( 'Reply text.', 'multivendorx' ),
                        ),
                    ),
                    'required'             => array( 'id', 'reply' ),
                    'additionalProperties' => false,
                ),
                'output_schema'       => array( 'type' => 'boolean' ),
                'execute_callback'    => array( $this, 'reply_to_store_review' ),
                'permission_callback' => array( $this, 'review_permissions_check' ),
                'meta'                => array( 'public' => true ),
            )
        );
    }

    /**
     * Check whether the current user can manage the review's store.
     *
     * @param array $input Ability input.
     * @return bool
     */
    public function review_permissions_check( $input ) {
        $review = reset( Util::query_reviews( array( 'review_id' => (int) ( $input['id'] ?? 0 ) ) ) );

        return $review && StoreUtil::current_user_can_manage_store( $review['store_id'] ?? 0 );
    }

    /**
     * Retrieve a store's reviews.
     *
     * @param array $input Ability input.
     * @return array
     */
    public function get_store_reviews( $input ) {
        $reviews = Util::get_reviews_by_store(
            (int) $input['store_id'],
            ! empty( $input['limit'] ) ? absint( $input['limit'] ) : 20
        );

        return array_map( array( $this, 'prepare_review' ), $reviews );
    }

    /**
     * Reply to a store review.
     *
     * @param array $input Ability input.
     * @return bool|\WP_Error
     */
    public function reply_to_store_review( $input ) {
        $id = (int) $input['id'];

        if ( ! reset( Util::query_reviews( array( 'review_id' => $id ) ) ) ) {
            return new \WP_Error( 'multivendorx_review_not_found', __( 'Review not found.', 'multivendorx' ) );
        }

        return (bool) Util::update_review(
            $id,
            array(
                'reply'      => sanitize_textarea_field( (string) $input['reply'] ),
                'reply_date' => current_time( 'mysql' ),
            )
        );
    }

    /**
     * Format a review row for ability output.
     *
     * @param object $review Review row.
     * @return array
     */
    private function prepare_review( $review ) {
        return array(
            'id'      => (int) $review->review_id,
            'title'   => $review->review_title,
            'content' => $review->review_content,
            'reply'   => $review->reply ?? '',
            'created' => Utill::multivendorx_rest_prepare_date_response( $review->date_created ),
        );
    }
}
