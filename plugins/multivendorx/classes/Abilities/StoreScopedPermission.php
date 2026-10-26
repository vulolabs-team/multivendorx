<?php
/**
 * Shared permission check for store-scoped abilities.
 *
 * @package MultiVendorX
 */

namespace MultiVendorX\Abilities;

use MultiVendorX\Store\StoreUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Used by any ability whose input directly names the store it acts on
 * (i.e. the input schema requires a 'store_id'). Not for abilities that act
 * on an existing resource (a review, a question) - those derive the owning
 * store from the resource itself instead.
 */
trait StoreScopedPermission {

    /**
     * Check whether the current user can manage the store given in the ability input.
     *
     * @param array $input Ability input.
     * @return bool
     */
    public function store_permissions_check( $input ) {
        return StoreUtil::current_user_can_manage_store( $input['store_id'] ?? 0 );
    }
}
