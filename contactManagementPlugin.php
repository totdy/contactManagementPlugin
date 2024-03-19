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

    $charsetCollate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Check if the table exists before creating it
    if ($wpdb->get_var("SHOW TABLES LIKE '$peopleTable'") != $peopleTable) {
        $sql = "CREATE TABLE $peopleTable (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            active int(1) NOT NULL,
            UNIQUE (email),
            PRIMARY KEY  (id)
        ) $charsetCollate;";        
        dbDelta($sql);
    }
    if ($wpdb->get_var("SHOW TABLES LIKE '$contactsTable'") != $contactsTable) {
        $sql = "CREATE TABLE $contactsTable (
            id int(11) NOT NULL AUTO_INCREMENT,
            personId int(11) NOT NULL,
            countryCode varchar(10) NOT NULL,
            number int(9) NOT NULL,
            active int(1) NOT NULL,
            UNIQUE ccn (countryCode, number),
            PRIMARY KEY  (id)
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
    global $wpdb;
    $peopleTable = $wpdb->prefix . 'cmp_people';    
    $contactsTable = $wpdb->prefix . 'cmp_contacts';

    $peopleContacts = $wpdb->get_results("
        SELECT 
            p.id as personId,
            p.name,
            p.email, 
            c.id as contactId,
            c.countryCode,
            c.number
        FROM 
            $peopleTable p        
        LEFT JOIN 
            $contactsTable c ON p.id = c.personId
    ");
    foreach ($peopleContacts as $person){
        $arrayPeopleContacts[$person->personId]["name"] = $person->name;
        $arrayPeopleContacts[$person->personId]["email"] = $person->email;
        if($person->contactId != NULL ){
            $arrayPeopleContacts[$person->personId]["contact"][$person->contactId]["countryCode"] = $person->countryCode;
            $arrayPeopleContacts[$person->personId]["contact"][$person->contactId]["number"] = $person->number;
        }
    }
    ?>
    <div class="wrap">        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>                    
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contacts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arrayPeopleContacts as $personId => $person){ ?>
                    <tr>                        
                        <td><?php echo $person["name"]; ?></td>
                        <td><?php echo $person["email"]; ?></td>
                        <td>
                            <ul>
                            <?php
                            foreach ($person["contact"] as $contact){
                                echo "<li>+".$contact["countryCode"].$contact["number"]."</li>";
                            }
                            ?>
                            <ul>
                            <a href="<?php echo admin_url("admin.php?page=addContact&personId=".$personId); ?>">Add Contact</a>
                        </td>
                        <td>
                            <div>
                                <a href="<?php echo $personId; ?>">Edit</a>
                            </div>
                            <div class="trash">
                                <a href="<?php echo $personId; ?>">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}
function addPersonPage() {
    if (isset($_POST['addPerson'])) {
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        global $wpdb;
        $people_table = $wpdb->prefix . 'cmp_people';
        $result = $wpdb->insert(
            $people_table,
            array(
                'name' => $name,
                'email' => $email,
                'active' => 1
            )
        );
         if ($result) {            
            echo '<div class="updated"><p>New person added successfully!</p></div>';
        } else {            
            echo '<div class="error"><p>Failed to add new person. Please try again later.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <form method="post" class="validate">
            <table class="form-table">
                <tbody>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="name">Name 
                            <span class="description">(required)</span>
                        </label>
                    </th>
                    <td>
                        <input name="name" type="text" id="name" value="" minlength="5" maxlength="100">
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="email">Email 
                            <span class="description">(required)</span>
                        </label>
                    </th>
                    <td>
                        <input name="email" type="email" id="email" value="" maxlength="255">
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="addPerson" class="button button-primary" value="Add New Person">
            </p>
        </form>
    </div>
    <?php
}
function addContactPage() {
    if (isset($_POST['addContact'])) {
        
        $countryCode = sanitize_text_field($_POST['countryCode']);
        $number = sanitize_text_field($_POST['number']);
        $personId = sanitize_text_field($_GET['personId']);

        global $wpdb;
        $contactsTable = $wpdb->prefix . 'cmp_contacts';
        $result = $wpdb->insert(
            $contactsTable,
            array(
                'personId' => $personId,
                'countryCode' => $countryCode,
                'number' => $number,
                'active' => 1
            )
        );
         if ($result) {            
            echo '<div class="updated"><p>New contact added successfully!</p></div>';
        } else {            
            echo '<div class="error"><p>Failed to add new contact. Please try again later.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <form method="post" class="validate">
            <table class="form-table">
                <tbody>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="countryCode">Country 
                            <span class="description">(required)</span>
                        </label>
                    </th>
                    <td>
                        <input name="countryCode" type="text" id="countryCode" value="" >
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="number">Number 
                            <span class="description">(required)</span>
                        </label>
                    </th>
                    <td>
                        <input name="number" type="number" id="number" value="" step="1" minlength="9" maxlength="9">
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="addContact" class="button button-primary" value="Add New Contact">
            </p>
        </form>
    </div>
    <?php
}