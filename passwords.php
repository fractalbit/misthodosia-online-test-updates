<?php

// Στον παρακάτω πίνακα μπορούμε να συμπληρώσουμε μία λίστα με ΑΦΜ και τον κωδικό με τον οποίο θέλουμε να τα προστατεύσουμε.
// Αν για κάποιο ΑΦΜ έχει συμπληρωθεί κωδικός, τότε στο πεδίο ΑΜ θα πρέπει να μπει ο συγκεκριμένος κωδικός για να εμφανιστούν
// τα οικονομικά του στοιχεία

$protected = array( // ΑΦΜ          // Κωδικός προστασίας
                    '' => '',                    
                  );

/*                 
Για παράδειγμα...
$protected = array( // ΑΦΜ          // Κωδικός προστασίας
                    '049922804' => 'test10/10/79',                    
                    '012545865' => 'korinthos57',                    
                  );
*/

