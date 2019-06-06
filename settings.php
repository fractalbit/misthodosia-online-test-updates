<?php
/*  
	"Μισθοδοσία online" - Εφαρμογή άντλησης και παρουσίασης οικoνομικών στοιχείων από αρχεία XML
    Copyright (C) 2011 Βελέντζας Αλέξανδρος (fractalbit@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see http://www.gnu.org/copyleft/gpl.html.
*/

/* *********** ΓΕΝΙΚΗ ΠΕΡΙΓΡΑΦΗ ΛΕΙΤΟΥΡΓΙΑΣ ΑΡΧΕΙΟΥ *********** */
// Το αρχείο αυτό επιτρέπει τη διαχείριση των αρχείων XML...
// μόνο αν κάποιος είναι συνδεδεμένος ως διαχειριστής.
/* *********** ΤΕΛΟΣ ΓΕΝΙΚΗΣ ΠΕΡΙΓΡΑΦΗΣ *********** */


include_once('./init.inc.php');


print_header();

if (admin_configured()) {

    if ($admin->check_logged_in()) {

        ?>
        <!-- Include FROALA JS files. -->
        <script src="js/ckeditor/ckeditor.js"></script>

        <script src="js/pwstabs/assets/jquery.pwstabs-1.2.1.min.js"></script>

        <script>
            jQuery(document).ready(function($) {
                $('.settings_tabs').pwstabs({
                    effect: 'scale',
                    containerWidth: '100%',
                    defaultTab: <?php
                                if (isset($_POST['google_analytics'])) {
                                    echo '3';
                                } elseif (isset($_POST['save'])) {
                                    echo '2';
                                } else {
                                    echo '1';
                                }
                                ?>,
                    theme: 'pws_theme_grey'
                });
            });
        </script>


        <div class="settings_tabs">

            <div data-pws-tab="texts" data-pws-tab-name="Κείμενα" style="min-height: 1330px;">
                <?php
                if (isset($_POST['header'])) {
                    // Αποθήκευση των κειμένων
                    $txt_new = array('header' => $_POST['header'], 'login_below' => $_POST['login_below'], 'footer' => $_POST['footer'], 'afm_label' => $_POST['afm_label'], 'amm_label' => $_POST['amm_label']);
                    file_put_contents(APP_DIR . '/texts.json', json_encode($txt_new)) or die("Αποτυχία αποθήκευσης");
                    echo '<h2 style="color: green">Τα δεδομένα αποθηκεύτηκαν με επιτυχία</h2>';

                    $txt = json_decode(file_get_contents(APP_DIR . '/texts.json'), true); // Ξανα-Φόρτωσε το αρχείο γλώσσας
                }
                ?>
                <i><strong>Σημείωση:</strong> Επεξεργαστείτε τα κείμενα και πιέστε αποθήκευση στο κάτω μέρος της σελίδας</i>

                <form id="texts" action="" method="POST">
                    <h2>Κείμενα αρχικής σελίδας</h2>
                    <textarea class="text" name="header" id="txt_header"><?php echo $txt['header'] ?></textarea><br /><br />
                    <textarea class="text" name="login_below" id="txt_login_below"><?php echo $txt['login_below'] ?></textarea><br /><br />
                    <textarea class="text" name="footer" id="txt_footer"><?php echo $txt['footer'] ?></textarea><br /><br />
                    <h2>Ετικέτες για τη φόρμα σύνδεσης</h2>
                    <input style="padding: 8px" type="text" name="afm_label" value="<?php echo $txt['afm_label'] ?>" /><br /><br />
                    <input style="padding: 8px" type="text" name="amm_label" value="<?php echo $txt['amm_label'] ?>" /><br /><br />

                    <br />

                    <input type="submit" class="button" value="Αποθήκευση" style="padding: 10px 10px; font-size: 16px; font-weight: bold;" />
                </form>

                <!-- Initialize the ckeditor -->
                <script>
                    jQuery(document).ready(function($) {
                        CKEDITOR.replace('txt_header');
                        CKEDITOR.replace('txt_login_below');
                        CKEDITOR.replace('txt_footer');
                    });
                </script>

            </div>

            <div data-pws-tab="Passwords" data-pws-tab-name="Κωδικοί">

                <?php


                // Αποθήκευσε όλους τους κωδικούς της φόρμας
                if (isset($_POST['save'])) {
                    foreach ($_POST['afm'] as $index => $value) {
                        $afm = $value;

                        $pass = $_POST['pass'][$index];

                        if (check_afm($afm)) {
                            if (!empty($pass) && strlen(utf8_decode($pass)) >= 5) {
                                $protected_save[$afm] = $pass;
                            } else {
                                echo '<h2 style="color: red">Ο κωδικός πρέπει να έχει τουλάχιστον 5 χαρακτήρες (ΑΦΜ: ' . $afm . ', κωδικός: ' . $pass . ') - ΔΕΝ ΑΠΟΘΗΚΕΥΘΗΚΕ</h2>';
                            }
                        } else {
                            if (!empty($afm)) echo '<h2 style="color: red">Ο ΑΦΜ ' . $afm . ' δεν είναι έγκυρος</h2>';
                        }
                    }

                    save_file(APP_DIR . '/passwords.php', $protected_save);
                    echo '<h2 style="color: green">Οι αλλαγές στους κωδικούς αποθηκεύτηκαν με επιτυχία</h2>';
                }


                ?>
                Παρακαλούμε δώστε προσοχή στα ακόλουθα...
                <ul>
                    <li>Συμπληρώστε το ΑΦΜ του μισθοδοτούμενου και τον κωδικό που επιθυμεί και πιέστε "Αποθήκευση κωδικών"</li>
                    <li>Μετά τον ορισμό κωδικού για τον μισθοδοτούμενο, ΔΕΝ θα μπορεί να εισέλθει με τον Αρ. Μητρώου του παρά μόνο με τον κωδικό που ορίσατε</li>
                    <li>Ο κωδικός πρέπει να έχει τουλάχιστον 5 χαρακτήρες ή/και ψηφια</li>
                    <li>Για να ακυρώσετε έναν κωδικό, απλά σβήστε τον και πιέστε "Αποθήκευση κωδικών". Θα εμφανιστεί μήνυμα ότι ο κωδικός είναι άκυρος και θα καταργηθεί από τη λίστα.</li>
                </ul>

                <h2>Διαχείριση κωδικών</h2>
                <?php

                if (file_exists(APP_DIR . '/passwords.php')) {
                    $protected = load_file(APP_DIR . '/passwords.php'); // Φόρτωσε το αρχείο με τους κωδικούς
                } else {
                    $protected = array();
                }

                //dump($protected);

                ?>
                <form action="" method="post">
                    <table id="passwords">
                        <thead>
                            <tr>
                                <th>Ονοματεπώνυμο</th>
                                <th>ΑΦΜ</th>
                                <th>Κωδικός</th>
                            </tr>
                        </thead>
                        <?php
                        if (!empty($protected)) {
                            $i = 1;
                            foreach ($protected as $afm => $pass) {
                                $file = USER_DIR . '/' . $afm . '.php';

                                if (file_exists($file)) {
                                    // Εαν υπάρχουν οικονομικά στοιχεία για αυτόν τον ΑΦΜ               
                                    $periods = load_file($file);
                                    $name = $periods['personal_info']['lastname'] . ' ' . $periods['personal_info']['firstname'];;
                                    ?>
                                    <tr>
                                        <td><?php echo  $name ?></td>
                                        <td><input type="text" name="afm[]" value="<?php echo $afm ?>" /></td>
                                        <td><input type="text" name="pass[]" value="<?php echo $pass ?>" /></td>
                                    </tr>
                                    <?php
                                    $i++;
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td><strong>Προσθήκη νέου</strong></td>
                            <td><input type="text" name="afm[]w" value="" /></td>
                            <td><input type="text" name="pass[]" value="" /></td>
                        </tr>
                        <input type="hidden" name="save" value="passwords" />
                        <tr>
                            <td colspan="3" align="center"><input type="submit" class="button" value="Αποθήκευση κωδικών" style="padding: 10px 10px; font-size: 16px; font-weight: bold;" /></td>
                            <?php
                            echo '</table></form>';
                            ?>

            </div>

            <div data-pws-tab="GA" data-pws-tab-name="Google Analytics">
                <?php
                if (isset($_POST['google_analytics'])) {
                    // Αποθήκευση του κώδικα για google analytics                        
                    if (empty($_POST['google_analytics'])) $_POST['google_analytics'] = ' ';

                    file_put_contents(APP_DIR . '/google_analytics.code', $_POST['google_analytics']) or die("Αποτυχία αποθήκευσης");

                    echo '<h2 style="color: green">H αποθήκευση του κώδικα για το google analytics έγινε με επιτυχία</h2>';
                }
                if (file_exists(APP_DIR . '/google_analytics.code')) {
                    $ga_code = trim(file_get_contents(APP_DIR . '/google_analytics.code'));
                } else {
                    $ga_code = '';
                }
                ?>
                <h2>Κώδικας google analytics (προαιρετικό)</h2>
                <form action="" method="post">
                    <textarea name="google_analytics" style="width: 900px; height: 160px"><?php echo  $ga_code ?></textarea><br><br>
                    <input type="submit" class="button" value="Αποθήκευση" style="padding: 10px 10px; font-size: 16px; font-weight: bold;" />
                </form><br><br>

            </div>


        </div>



    <?php

} else {
    echo '<div class="error box">' . $admin->message . '</div>';
    echo $admin->show_login_form();
}
} else {
    echo $admin->message;
}


print_footer();
