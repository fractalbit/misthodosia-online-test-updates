<?php

require_once './init.inc.php';

print_header();

if (admin_configured()) {

    if ($admin->check_logged_in()) {
        $app = new App();
        // $mo->update();
        $app->display_paths(); // Just to be sure we will ovrwrite files in the correct directory before testing update
        $app->check_newer(true); // Only run this if you want to overwrite the chached values from the constructor (currently 1 hour expiration time)
        $app->print_update_log();
        $app->print_newer();
    } else {
        echo '<div class="error box">' . $admin->message . '</div>';
        echo $admin->show_login_form();
    }
} else {
    echo $admin->message;
}

print_footer();
