<?php
/**
 * MultiVendorX Abilities API
 *
 * @package MultiVendorX
 */

namespace MultiVendorX\Abilities;

use MultiVendorX\Abilities\AbilitiesApi\Stores;
use MultiVendorX\Abilities\AbilitiesApi\Commissions;
use MultiVendorX\Abilities\AbilitiesApi\Transactions;

defined( 'ABSPATH' ) || exit;

/**
 * MultiVendorX Abilities main class.
 *
 * @class       Abilities class
 * @version     1.0.0
 * @author      MultiVendorX
 */
class Abilities {

    /**
     * Container for all abilities controller classes.
     *
     * @var array
     */
    private $container = array();

    /**
     * Abilities constructor.
     */
    public function __construct() {
        $this->init_classes();

        add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
        add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
    }

    /**
     * Initialize all abilities controller classes.
     *
     * @return void
     */
    public function init_classes() {
        $this->container = array(
            'store'       => new Stores(),
            'commission'  => new Commissions(),
            'transaction' => new Transactions(),
        );
    }

    /**
     * Register the MultiVendorX ability category.
     *
     * @return void
     */
    public function register_categories() {
        wp_register_ability_category(
            'multivendorx',
            array(
                'label'       => __( 'MultiVendorX', 'multivendorx' ),
                'description' => __( 'MultiVendorX marketplace abilities.', 'multivendorx' ),
            )
        );
    }

    /**
     * Register abilities for each controller in the container.
     *
     * @return void
     */
    public function register_abilities() {
        foreach ( $this->container as $controller ) {
            if ( method_exists( $controller, 'register_abilities' ) ) {
                $controller->register_abilities();
            }
        }
    }
}
