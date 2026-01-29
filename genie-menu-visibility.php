<?php
/**
 * Plugin Name: Genie Menu Visibility Controller
 * Description: Controls navigation menu visibility based on user role stored in user_meta or Pure Form Builder entry meta (fallback).
 * Version: 1.6.0
 * Author: Md Shakibur Rahman
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ---------------------------------------------------------
 * MENU VISIBILITY LOGIC (USER META + FALLBACK)
 * ---------------------------------------------------------
 */
function genie_custom_menu_visibility_filter( $items, $args ) {

    // Only logged-in users
    if ( ! is_user_logged_in() ) {
        return $items;
    }

    $user_id = get_current_user_id();

    /**
     * STEP 1: Try user_meta first
     */
    $user_role = get_user_meta( $user_id, 'genie_set_role', true );

    /**
     * STEP 2: Fallback → read from pfb_entry_meta
     * (Because live site user_meta is empty)
     */
    if ( empty( $user_role ) ) {
        global $wpdb;

        $user_role = $wpdb->get_var( $wpdb->prepare(
            "SELECT em.field_value
             FROM {$wpdb->prefix}pfb_entry_meta em
             INNER JOIN {$wpdb->prefix}pfb_entries e ON e.id = em.entry_id
             WHERE e.user_id = %d
               AND em.field_name = 'genie_set_role'
             ORDER BY em.id DESC
             LIMIT 1",
            $user_id
        ) );
    }

    // Normalize role (VERY IMPORTANT)
    $user_role = strtolower( trim( $user_role ) );

    // Menu CSS classes
    $driver_class = 'genie-driver-menu';
    $owner_class  = 'genie-owner-menu';
    $both_class   = 'genie-both-menu';

    foreach ( $items as $key => $item ) {

        $classes = (array) $item->classes;

        /**
         * No role selected → hide all role-based menus
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
        if ( $user_role === 'driver' ) {
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
        elseif ( $user_role === 'owner' ) {
            if (
                in_array( $driver_class, $classes, true ) ||
                in_array( $both_class, $classes, true )
            ) {
                unset( $items[ $key ] );
            }
        }

        /**
         * Driver & Owner
         * → SHOW ONLY both menu
         */
        elseif ( in_array( $user_role, array( 'driver & owner', 'driver_owner', 'both' ), true ) ) {
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
