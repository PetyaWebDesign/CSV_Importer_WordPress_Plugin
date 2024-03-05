<?php
/*
Plugin Name: CSV Importer for Custom Post Type
Description: Import data from CSV file to selected custom post type with custom field support.
Version: 1.0
Author: Petya Petkova {dev} blodnie;
*/

// Include ACF functions
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// Define function to add settings page for CSV import
function csv_import_settings_page() {
    add_menu_page(
        'CSV Import',
        'CSV Import',
        'manage_options',
        'csv-import-settings',
        'csv_import_settings_page_content'
    );
}
add_action('admin_menu', 'csv_import_settings_page');

// Define function to display settings page content
function csv_import_settings_page_content() {
    handle_csv_import();
}

// Define function to handle form submission
function handle_csv_import() {
    if (isset($_POST['submit'])) {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error">CSV file is not uploaded.</div>';
            display_import_form();
        } else {
            process_csv_file();
        }
    } else {
        display_import_form();
    }
}

// Define function to display import form
function display_import_form() {
    ?>
    <div class="wrap">
        <h2>CSV Import</h2>
        <form method="post" enctype="multipart/form-data">
            <label for="post_type">Select Post Type:</label>
            <select name="post_type" id="post_type">
                <option value="staff">Staff</option>
                <!-- Add more options for other post types if needed -->
            </select>
            <br><br>
            <label for="csv_file">Upload CSV File:</label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv">
            <br><br>
            <h3>Map CSV Columns to Post Fields:</h3>
            <table>
                <tr>
                    <th>CSV Column</th>
                    <th>Post Field</th>
                </tr>
                <tr>
                    <td>A - First Name</td>
                    <td><input type="hidden" name="mapping[first_name]" value="0">post_title</td>
                </tr>
                <tr>
                    <td>B - Last Name</td>
                    <td><input type="hidden" name="mapping[last_name]" value="1">post_title</td>
                </tr>
                <tr>
                    <td>C - Email</td>
                    <td><input type="hidden" name="mapping[email]" value="2">email</td>
                </tr>
                <tr>
                    <td>D - Office</td>
                    <td><input type="hidden" name="mapping[office]" value="3">office</td>
                </tr>
                <tr>
                    <td>E - Office Hours</td>
                    <td><input type="hidden" name="mapping[office_hours]" value="4">office_hours</td>
                </tr>
                <tr>
                    <td>F - Phone</td>
                    <td><input type="hidden" name="mapping[phone]" value="5">phone</td>
                </tr>
                <tr>
                    <td>G - Position</td>
                    <td><input type="hidden" name="mapping[position]" value="6">position</td>
                </tr>
                <tr>
                    <td>H - Specialization</td>
                    <td><input type="hidden" name="mapping[specialization]" value="7">specialization</td>
                </tr>
                <tr>
                    <td>I - Department</td>
                    <td><input type="hidden" name="mapping[department]" value="8">department</td>
                </tr>
            </table>
            <br>
            <input type="submit" name="submit" value="Import CSV">
        </form>
    </div>
    <?php
}

// Define function to process CSV file and import data
function process_csv_file() {
    $post_type = isset($_POST['post_type']) ? $_POST['post_type'] : '';
    $mapping = isset($_POST['mapping']) ? $_POST['mapping'] : array();

    if (empty($mapping)) {
        echo '<div class="error">No mapping provided.</div>';
        return;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");

    if ($handle !== false) {
        // Skip the header row
        fgetcsv($handle);

        $success_count = 0;
        $error_count = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $post_title = $data[$mapping['first_name']] . ' ' . $data[$mapping['last_name']];
            $post_content = ''; // You can set post content here if needed

            // Set post data
            $post_data = array(
                'post_title'    => $post_title,
                'post_content'  => $post_content,
                'post_status'   => 'publish',
                'post_type'     => $post_type,
            );

            // Insert the post into the database
            $post_id = wp_insert_post($post_data);

            // Check if post insertion was successful
            if (!is_wp_error($post_id)) {
                // Set post meta/custom fields based on mappings
                foreach ($mapping as $field_key => $column_index) {
                    if ($field_key !== 'first_name' && $field_key !== 'last_name') {
                        update_post_meta($post_id, $field_key, $data[$column_index]);
                    }
                }
                $success_count++;
            } else {
                // Handle error
                echo 'Error creating post: ' . $post_id->get_error_message();
                $error_count++;
            }
        }

        fclose($handle);

        // Display success and error messages
        if ($success_count > 0) {
            echo '<div class="updated">Successfully created ' . $success_count . ' posts.</div>';
        }
        if ($error_count > 0) {
            echo '<div class="error">Failed to create ' . $error_count . ' posts.</div>';
        }
    } else {
        // Handle error opening CSV file
        echo 'Error opening CSV file.';
    }
}
?>
