<?php
/**
 * Plugin Name: Espacio Sutil PMPro Cleanup
 * Description: Corrige incompatibilidades de limpieza de usuarios en PMPro.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cancel all active PMPro levels for a user before deletion when requested.
 *
 * PMPro 3.x deprecated using pmpro_changeMembershipLevel(0, $user_id) for
 * cancellations, but its delete-user cleanup still calls that path.
 *
 * @param int $user_id
 * @return bool
 */
function espaciosutil_pmpro_delete_user(int $user_id): bool
{
    if ($user_id < 1) {
        return false;
    }

    $cancel_active_subscriptions = isset($_REQUEST['pmpro_delete_active_subscriptions'])
        && $_REQUEST['pmpro_delete_active_subscriptions'] === '1';

    if (apply_filters('pmpro_user_deletion_cancel_active_subscriptions', $cancel_active_subscriptions, $user_id)) {
        $membership_levels = pmpro_getMembershipLevelsForUser($user_id);
        foreach ($membership_levels as $membership_level) {
            pmpro_cancelMembershipLevel((int) $membership_level->id, $user_id);
        }
    }

    if (isset($_REQUEST['pmpro_delete_member_history']) && $_REQUEST['pmpro_delete_member_history'] === '1') {
        pmpro_delete_membership_history($user_id);
    }

    return true;
}

/**
 * Replace PMPro's built-in delete hooks with the compatible implementation.
 */
function espaciosutil_pmpro_override_delete_user_cleanup(): void
{
    remove_action('delete_user', 'pmpro_delete_user');
    remove_action('wpmu_delete_user', 'pmpro_delete_user');

    add_action('delete_user', 'espaciosutil_pmpro_delete_user');
    add_action('wpmu_delete_user', 'espaciosutil_pmpro_delete_user');
}
add_action('plugins_loaded', 'espaciosutil_pmpro_override_delete_user_cleanup', 20);
