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
            (SELECT * FROM $contactsTable WHERE active = 1) c ON p.id = c.personId
        WHERE p.active = 1
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
                    <th>Avatar</th>           
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contacts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arrayPeopleContacts as $personId => $person){ ?>
                    <tr>
                        <td>
                        <img width="100"
                            src="https://api.dicebear.com/8.x/adventurer/svg?seed=<?php echo explode("@",$person["email"])[0]; ?>&flip=true"
                            alt="avatar"
                            />
                        </td>
                        <td><?php echo $person["name"]; ?></td>
                        <td><?php echo $person["email"]; ?></td>
                        <td>
                            <ul>
                            <?php
                            if(isset($person["contact"])){
                                foreach ($person["contact"] as $contactId => $contact){
                                    ?>
                                    <li>
                                        <?php echo "+".$contact["countryCode"].$contact["number"]; ?>
                                        <a href="<?php echo admin_url("admin.php?page=addContact&editContactId=".$contactId); ?>">Edit</a>
                                        <a href="<?php echo admin_url("admin.php?page=addContact&deleteContactId=".$contactId); ?>">Delete</a>
                                    </li>
                                    <?php
                                }
                            }                            
                            ?>
                            <ul>
                            <a href="<?php echo admin_url("admin.php?page=addContact&personId=".$personId); ?>">Add Contact</a>
                        </td>
                        <td>
                            <div>
                            <a href="<?php echo admin_url("admin.php?page=addPerson&editPersonId=".$personId); ?>">Edit Person</a>
                            </div>
                            <div class="trash">
                                <a href="<?php echo admin_url("admin.php?page=addPerson&deletePersonId=".$personId); ?>">Delete Person</a>
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
    if (isset($_POST['updatePerson'])) {
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $personId = intval($_GET['editPersonId']);

        global $wpdb;
        $peopleTable = $wpdb->prefix . 'cmp_people';
        $result = $wpdb->update(
            $peopleTable,
            array(
                'name' => $name,
                'email' => $email
            ),
            array(
                'id' => $personId
            )
        );
         if ($result) {            
            echo '<div class="updated"><p>New person added successfully!</p></div>';
        } else {            
            echo '<div class="error"><p>Failed to add new person. Please try again later.</p></div>';
        }
    }
    if (isset($_POST['addPerson'])) {
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        global $wpdb;
        $peopleTable = $wpdb->prefix . 'cmp_people';
        $result = $wpdb->insert(
            $peopleTable,
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
    if (isset($_GET['deletePersonId'])) {
        
        $personId = intval($_GET['deletePersonId']);

        global $wpdb;
        $peopleTable = $wpdb->prefix . 'cmp_people';
        $contactTable = $wpdb->prefix . 'cmp_contacts';
        $result = $wpdb->update(
            $peopleTable,
            array(                
                'active' => 0
            ),
            array(
                'id' => $personId
            )
        );
        $result = $wpdb->update(
            $contactTable,
            array(                
                'active' => 0
            ),
            array(
                'personId' => $personId
            )
        );
         if ($result) {            
            echo '<div class="updated"><p>Person deleted successfully!</p></div>';
        } else {            
            echo '<div class="error"><p>Failed to delete a person. Please try again later.</p></div>';
        }
        exit();
    }
    if (isset($_GET['editPersonId'])) {
        
        $personId = intval($_GET['editPersonId']);

        global $wpdb;
        $peopleTable = $wpdb->prefix . 'cmp_people';
        $person = $wpdb->get_results("
            SELECT 
            * 
            FROM $peopleTable
            WHERE id = $personId
        ")[0];         
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
                        <input name="name" type="text" id="name" minlength="5" maxlength="100" value="<?php echo isset($person->name) ? $person->name : "";?>">
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="email">Email 
                            <span class="description">(required)</span>
                        </label>
                    </th>
                    <td>
                        <input name="email" type="email" id="email" maxlength="255" value="<?php echo isset($person->email) ? $person->email : "";?>">
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <?php
                if(isset($person->id)){
                    ?>                    
                    <input type="submit" name="updatePerson" class="button button-primary" value="Update Person">
                    <?php
                }else{
                    ?>
                    <input type="submit" name="addPerson" class="button button-primary" value="Add New Person">
                    <?php
                }
                ?>
            </p>
        </form>
    </div>
    <?php
}
function addContactPage() {
        
    if (!isset($_GET['personId']) && !isset($_GET['deleteContactId']) && !isset($_GET['editContactId'])) {       
        echo '<div class="error"><p>No direct access to page. Only from listing people menu.</p></div>';
        exit();
    }
    if (isset($_POST['updateContact'])) {
        
        $countryCode = sanitize_text_field($_POST['countryCode']);
        $number = sanitize_text_field($_POST['number']);
        $contactId = intval($_GET['editContactId']);

        global $wpdb;
        $contactTable = $wpdb->prefix . 'cmp_contacts';
        $result = $wpdb->update(
            $contactTable,
            array(
                'countryCode' => $countryCode,
                'number' => $number
            ),
            array(
                'id' => $contactId
            )
        );
         if ($result) {            
            echo '<div class="updated"><p>New person added successfully!</p></div>';
        } else {            
            echo '<div class="error"><p>Failed to add new person. Please try again later.</p></div>';
        }
    }
    if (isset($_POST['addContact'])) {
        
        $countryCode = sanitize_text_field($_POST['countryCode']);
        $number = sanitize_text_field($_POST['number']); 
        $personId = intval($_GET['personId']);       

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
    if (isset($_GET['deleteContactId'])) {
        
        $contactId = intval($_GET['deleteContactId']);

        global $wpdb;
        $contactsTable = $wpdb->prefix . 'cmp_contacts';
        $result = $wpdb->update(
            $contactsTable,
            array(                
                'active' => 0
            ),
            array(
                'id' => $contactId
            )
        );
         if ($result) {            
            echo '<div class="updated"><p>Contact deleted successfully!</p></div>';
        } else {            
            echo '<div class="error"><p>Failed to delete a contact. Please try again later.</p></div>';
        }
        exit();
    }
    if (isset($_GET['editContactId'])) {
        
        $contactId = intval($_GET['editContactId']);

        global $wpdb;
        $contactsTable = $wpdb->prefix . 'cmp_contacts';
        $contact = $wpdb->get_results("
            SELECT 
            * 
            FROM $contactsTable
            WHERE id = $contactId
        ")[0];         
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
                        <select name="countryCode" required>
                            <option selected disabled></option>
                            <?php

                            $url = "https://restcountries.com/v3.1/independent?status=true&fields=name,idd";

                            $curl = curl_init($url);
                            curl_setopt($curl, CURLOPT_URL, $url);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                            
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                            $resp = curl_exec($curl);
                            curl_close($curl);
                            $countries = json_decode($resp);

                            foreach($countries as $country){
                                echo "<option value='".intval($country->idd->root.$country->idd->suffixes[0])."' ";
                                if(isset($contact->countryCode) && $contact->countryCode == intval($country->idd->root.$country->idd->suffixes[0])){
                                    echo "selected";
                                }                                 
                                echo" >".$country->name->common." (".$country->idd->root.$country->idd->suffixes[0].")</option>";
                            }                            
                            ?>
                        </select>
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="number">Number 
                            <span class="description">(required)</span>
                        </label>
                    </th>
                    <td>
                        <input name="number" type="number" id="number" step="1" minlength="9" maxlength="9" value="<?php echo isset($contact->number) ? $contact->number : "";?>">
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <?php
                if(isset($contact->id)){
                    ?>                    
                    <input type="submit" name="updateContact" class="button button-primary" value="Update Contact">
                    <?php
                }else{
                    ?>
                    <input type="submit" name="addContact" class="button button-primary" value="Add New Contact">
                    <?php
                }
                ?>                
            </p>
        </form>
    </div>
    <?php
}