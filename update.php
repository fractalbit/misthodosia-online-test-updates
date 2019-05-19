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
           
    }else{
        echo '<div class="error">'.$admin->message.'</div>';
        echo $admin->show_login_form();
    }

}else{
echo $admin->message;
}

print_footer();