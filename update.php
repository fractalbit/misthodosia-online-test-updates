<?php

require_once './init.inc.php';

print_header();

if(admin_configured()){

    if($admin->check_logged_in()){

        $repo = new Github();
        $mo = new App($repo);
        // $mo->update();
        $mo->get_releases();
        $mo->print_newer();
        fSession::set('app', $mo); // Store the App object so that ajax requests can access it
        // This does not work well when sqitching branches
        // I should see how i can delete session data from xampp
        // Or consider other ways to save data to the server
        // Maybe store in session only the data i want and not the full object
        // Or pass the required data from one ajax request to another   
    }else{
        echo '<div class="error">'.$admin->message.'</div>';
        echo $admin->show_login_form();
    }

}else{
echo $admin->message;
}

print_footer();