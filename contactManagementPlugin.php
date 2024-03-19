<?php
/*
Plugin Name: Contact Management Plugin
Description: A plugin to manage contacts.
Version: 1.0
Author: Nazar Poritskiy
*/

function contact_management_plugin_activate() {
    global $wpdb;

    $people_table = $wpdb->prefix . 'cmp_people';
    $contacts_table = $wpdb->prefix . 'cmp_contacts';
    $people_contacts_table = $wpdb->prefix . 'cmp_people_contacts';

    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Check if the table exists before creating it
    if ($wpdb->get_var("SHOW TABLES LIKE '$people_table'") != $people_table) {
        $sql = "CREATE TABLE $people_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            UNIQUE (email),
            PRIMARY KEY  (id)
        ) $charset_collate;";        
        dbDelta($sql);
    }
    if ($wpdb->get_var("SHOW TABLES LIKE '$contacts_table'") != $contacts_table) {
        $sql = "CREATE TABLE $contacts_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            countryCode varchar(10) NOT NULL,
            number int(9) NOT NULL,
            UNIQUE ccn (countryCode, number),
            PRIMARY KEY  (id)
        ) $charset_collate;";        
        dbDelta($sql);
    }
    if ($wpdb->get_var("SHOW TABLES LIKE '$people_contacts_table'") != $people_contacts_table) {
        $sql = "CREATE TABLE $people_contacts_table (
            idPerson int(11) NOT NULL,
            idContact int(11) NOT NULL,
            active int(1) NOT NULL,
            PRIMARY KEY  (idPerson, idContact)
        ) $charset_collate;";        
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'contact_management_plugin_activate');

function contact_management_plugin_deactivate() {    
}
register_deactivation_hook(__FILE__, 'contact_management_plugin_deactivate');
