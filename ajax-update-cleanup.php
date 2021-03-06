<?php

/* *********** ΓΕΝΙΚΗ ΠΕΡΙΓΡΑΦΗ ΛΕΙΤΟΥΡΓΙΑΣ ΑΡΧΕΙΟΥ *********** */
// Αντιγράφει την τελευταία έκδοση της εφαρμογής κάνοντας overwrite. Εδώ πρακτικά γίνεται η ενημέρωση της εφαρμογής.
/* *********** ΤΕΛΟΣ ΓΕΝΙΚΗΣ ΠΕΡΙΓΡΑΦΗΣ *********** */

include_once('./init.inc.php');

if (admin_configured()) {
    if ($admin->check_logged_in()) {
        $app = new App();
        $app->cleanup_and_log();
        echo 'Ολοκληρώθηκε';
    } else {
        header('HTTP/1.1 403 Forbidden');
        exit("Η πρόσβαση σε αυτή τη λειτουργία επιτρέπεται μόνο στον διαχειριστή");
    }
} else {
    echo $admin->message;
}
