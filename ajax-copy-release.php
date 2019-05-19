<?php

/* *********** ΓΕΝΙΚΗ ΠΕΡΙΓΡΑΦΗ ΛΕΙΤΟΥΡΓΙΑΣ ΑΡΧΕΙΟΥ *********** */
// Κατεβάζει την τελευταία έκδοση της εφαρμογής
/* *********** ΤΕΛΟΣ ΓΕΝΙΚΗΣ ΠΕΡΙΓΡΑΦΗΣ *********** */

include_once('./init.inc.php');

if(admin_configured()){

    if($admin->check_logged_in()){
        if(fSession::get('app')){
            $app = fSession::get('app');
            $app->copy_latest();
            echo 'Ολοκληρώθηκε';
        }
    }else{
        header('HTTP/1.1 403 Forbidden');
        exit("Η πρόσβαση σε αυτή τη λειτουργία επιτρέπεται μόνο στον διαχειριστή");
        
        echo '<div class="error">'.$admin->message.'</div>';
        echo $admin->show_login_form();
    }

}else{
    echo $admin->message;
}