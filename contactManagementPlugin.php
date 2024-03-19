<?php
/*
Plugin Name: Contact Management Plugin
Description: A plugin to manage contacts.
Version: 1.0
Author: Nazar Poritskiy
*/

function contactManagementPluginActivate() {
    global $wpdb;

    $peopleTable = $wpdb->prefix . 'cmp_people';
    $contactsTable = $wpdb->prefix . 'cmp_contacts';
    $peopleContactsTable = $wpdb->prefix . 'cmp_people_contacts';

    $charsetCollate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Check if the table exists before creating it
    if ($wpdb->get_var("SHOW TABLES LIKE '$peopleTable'") != $peopleTable) {
        $sql = "CREATE TABLE $peopleTable (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            UNIQUE (email),
            PRIMARY KEY  (id)
        ) $charsetCollate;";        
        dbDelta($sql);
    }
    if ($wpdb->get_var("SHOW TABLES LIKE '$contactsTable'") != $contactsTable) {
        $sql = "CREATE TABLE $contactsTable (
            id int(11) NOT NULL AUTO_INCREMENT,
            countryCode varchar(10) NOT NULL,
            number int(9) NOT NULL,
            UNIQUE ccn (countryCode, number),
            PRIMARY KEY  (id)
        ) $charsetCollate;";        
        dbDelta($sql);
    }
    if ($wpdb->get_var("SHOW TABLES LIKE '$peopleContactsTable'") != $peopleContactsTable) {
        $sql = "CREATE TABLE $peopleContactsTable (
            idPerson int(11) NOT NULL,
            idContact int(11) NOT NULL,
            active int(1) NOT NULL,
            PRIMARY KEY  (idPerson, idContact)
        ) $charsetCollate;";        
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'contactManagementPluginActivate');

function contactManagementPluginDeactivate() {    
}
register_deactivation_hook(__FILE__, 'contactManagementPluginDeactivate');

function contactManagementMenu() {
    //$page_title:string
    //$menu_title:string 
    //$capability:string 
    //$menu_slug:string 
    //$callback:callable
    add_menu_page(
        'Listing people',
        'Listing people',
        'manage_options',
        'listingPeople',
        'listingPeoplePage'
    );
    add_submenu_page(
        'listingPeople',
        'Add a new person',
        'Add person',
        'manage_options',
        'addPerson',
        'addPersonPage'
    );
    add_submenu_page(
        'listingPeople',
        'Add a new contact',
        'Add contact',
        'manage_options',
        'addContact',
        'addContactPage'
    );
}
add_action('admin_menu', 'contactManagementMenu');

function listingPeoplePage() {

}
function addPersonPage() {

}
function addContactPage() {

}