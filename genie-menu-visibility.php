<?php
/**
 * Plugin Name: Genie Menu Visibility Controller
 * Description: Controls navigation menu visibility based on user role stored in user_meta (genie_set_role). Cache-safe and role-update aware.
 * Version: 1.4.1
 * Author: Md Shakibur Rahman
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ---------------------------------------------------------
 * MENU VISIBILITY LOGIC (NO CACHE, USER META BASED)
 * ---------------------------------------------------------
 */
function genie_custom_menu_visibility_filter( $items, $args ) {

    // Only logged-in users
    if ( ! is_user_logged_in() ) {
        return $items;
    }

    $user_id   = get_current_user_id();
    $user_role = get_user_meta( $user_id, 'genie_set_role', true );
    $user_role = strtolower( trim( $user_role ) );

    // Menu CSS classes
    $driver_class = 'genie-driver-menu';
    $owner_class  = 'genie-owner-menu';
    $both_class   = 'genie-both-menu';

    foreach ( $items as $key => $item ) {

        $classes = (array) $item->classes;

        /**
         * No role selected → hide all profile menus
         */
        if ( empty( $user_role ) ) {
            if (
                in_array( $driver_class, $classes, true ) ||
                in_array( $owner_class, $classes, true ) ||
                in_array( $both_class, $classes, true )
            ) {
                unset( $items[ $key ] );
            }
            continue;
        }

        /**
         * Driver
         */
        if ( $user_role === 'Driver' ) {
            if (
                in_array( $owner_class, $classes, true ) ||
                in_array( $both_class, $classes, true )
            ) {
                unset( $items[ $key ] );
            }
        }

        /**
         * Owner
         */
        elseif ( $user_role === 'Owner' ) {
            if (
                in_array( $driver_class, $classes, true ) ||
                in_array( $both_class, $classes, true )
            ) {
                unset( $items[ $key ] );
            }
        }

        /**
         * Driver & Owner
         */
        elseif ( in_array( $user_role, array( 'Driver & Owner', 'driver_owner', 'both' ), true ) ) {
            if (
                in_array( $driver_class, $classes, true ) ||
                in_array( $owner_class, $classes, true )
            ) {
                unset( $items[ $key ] );
            }
        }
    }

    return $items;
}
add_filter( 'wp_nav_menu_objects', 'genie_custom_menu_visibility_filter', 99, 2 );
