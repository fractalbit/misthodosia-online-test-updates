<?php

require_once './init.inc.php';

print_header();

if(admin_configured()){

    if($admin->check_logged_in()){

        $repo = new Github();
        $mo = new App($repo);
        // $mo->update();
        // $mo->get_raw_data();
        fSession::set('app', $mo); // Store the App object so that ajax requests can access it

        echo '<input id="ajax-test" type="button" value="test">';

        // Just a quick test about versioning and version comparison
        // $app_versions = array('v2.2.0', 'v2.2.1', 'v2.3', 'v2.3.1', 'v2.3.1', 'v2.3.0.9');
        // $prev = '';
        // foreach($app_versions as $key => $ver){
        //     if(check_newer($prev, $ver)){
        //         echo $prev . ' VS ' . $ver . ' : There is a new version, please upgrade<br>';
        //     }else{
        //         echo $prev . ' VS ' . $ver . ' : Your version is up to date<br>';
        //     }
        //     $prev = $ver;
        // }

        // function check_newer($current_version, $server_version){
        //     if(version_compare($current_version, $server_version) < 0){
        //         return true;
        //     }else{
        //         return false;
        //     }
        // }

    }else{
        echo '<div class="error">'.$admin->message.'</div>';
        echo $admin->show_login_form();
    }

}else{
echo $admin->message;
}

print_footer();