<?php

/**
 * LinkCentral Updater Class
 *
 * This class handles the database update logic for the plugin.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LinkCentral_Updater {
    /**
     * Check and apply database updates.
     */
    public static function update_db_check() {
        $installed_version = get_option('linkcentral_db_version');

        if (version_compare($installed_version, '1.0.1', '<')) {
            self::update_to_1_0_1();
        }

        // Update the database version option
        update_option('linkcentral_db_version', LINKCENTRAL_DB_VERSION);
    }

    /**
     * Update to version 1.0.1.
     */
    private static function update_to_1_0_1() {
        //error_log('Updating to 1.0.1');
    }
}

/**
 * Database update check callback.
 */
function linkcentral_update_db_check() {
    LinkCentral_Updater::update_db_check();
}