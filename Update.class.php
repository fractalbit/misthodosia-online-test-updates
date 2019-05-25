<?php
class App
{
    private $current_version;
    // This should be changed every time we push to github
    // while also creating the actual correspoding git tag
    // And don't foget to push tags to github

    private $app_dir = '';

    private $folders = array();

    private $simulate = false;
    private $simulate_ver = 'v2.0.0';
    private $simulate_dir = 'simulate/';

    private $releases = array();
    private $changelog = ''; // If this is not empty, it also means there is a new version
    private $last_checked;

    private $repo; // This will hold the github instance

    public function __construct()
    {

        $this->app_dir = trailingslashit(dirname(__FILE__));
        $this->current_version = self::get_version();

        if (is_dir('.git')) { // This means we are in a dev enviroment. We should be carefull not to overwrite our working directory!
            $this->simulate = true;
            $this->app_dir  = $this->app_dir . $this->simulate_dir;
            $this->current_version = $this->simulate_ver;
            // I should really save the version in a separate file
            // Should think this through on how to manage the simulate part
            // Also change the get_version method
        }

        $this->folders = array(
            'updates'   => $this->app_dir . 'updates/',
            'releases'  => $this->app_dir . 'updates/releases/',
            'downloads' => $this->app_dir . 'updates/downloads/',
        );

        // I should create the folders if they do not exist (for some reason)
        $this->create_folders();

        $this->repo = new Github();

        $this->check_newer();
    }

    private function create_folders()
    {
        foreach ($this->folders as $folder) {
            if (!is_dir($folder)) mkdir($folder, 0755);
        }
    }

    public function display_paths()
    {
        if (is_dir('.git')) {
            dump($this->app_dir);
            dump($this->current_version);
            dump($this->folders);
        }
    }

    public function check_newer($force_check = false)
    {
        // Only checks if the session expired or the force_check flag is set

        if ($force_check) {
            $this->get_releases();
        } else {
            $cached_releases = fSession::get('releases');
            is_array($cached_releases) ? $this->releases = $cached_releases : $this->get_releases();

            $cached_changelog = fSession::get('changelog');
            !empty($cached_changelog) ? $this->changelog = $cached_changelog : $this->get_releases();

            $last_checked = fSession::get('last_checked');
            !empty($last_checked) ? $this->last_checked = $last_checked : $this->get_releases();
        }
    }

    public function get_releases()
    {
        // Stores all the releases 
        // It also checks if there are new releases and populates the changelog

        // First clear possible cached values. This is needed if the get_releases is called outside
        //  of the constructor so it force updates the values stored when creating the object        
        $this->releases = array();
        $this->changelog = '';

        $repo = $this->repo;

        $releases = json_decode($repo->get('releases'), true);
        if (version_compare($this->current_version, $releases[0]['tag_name']) < 0)
            $this->changelog = '<h3 style="font-weight: normal">Αλλαγές από την έκδοση <strong>' . $this->current_version .
                '</strong> (τρέχουσα έκδοση) έως την έκδοση <strong>' . $releases[0]['tag_name'] . '</strong> (πιο πρόσφατη)</h3><hr>';

        foreach ($releases as $data) {
            $Parsedown = new Parsedown();
            $created_at = self::convert_date($data['created_at']);
            $published_at = self::convert_date($data['published_at']);
            $html = $Parsedown->text('## ' . $data['name'] . "\n _Δημοσιεύτηκε στις " . $published_at . "_\n" . $data['body']);

            $release = array(
                'tag' => $data['tag_name'],
                'name' => $data['name'],
                'created' => $created_at,
                'published' => $published_at,
                'zip_url' =>  $data['zipball_url'],
                'html' => $html,
            );

            if (version_compare($this->current_version, $release['tag']) < 0) {
                // Add to this versions info to the changeog
                $this->changelog .= $html;
            }

            $this->releases[] = $release;
        }

        $this->last_checked = date("d/m/Y H:i:s", time());

        fsession::set('releases', $this->releases);
        fsession::set('changelog', $this->changelog);
        fsession::set('last_checked', $this->last_checked);

        // echo $this->releases[0]['html'];
        // echo $this->releases[1]['html'];

        // dump($this->releases);
    }

    public static function convert_date($ISO_8601)
    {
        $date = new DateTime($ISO_8601);
        return $date->format("d/m/Y H:i");
    }

    /**
     *  Τυπώνει το changelog
     */
    public function print_newer()
    {
        echo '<div style="font-style: italic; margin-top: 20px">Έγινε έλεγχος για ενημερώσεις στις: ' . $this->last_checked . '</div>';
        if (!empty($this->changelog)) {
            echo '<h3>Υπάρχουν διαθέσιμες ενημερώσεις...</h3>';
            echo 'Η αυτόματη ενημέρωση είναι σε πειραματικό στάδιο και δεν έχει δοκιμαστεί επαρκώς. <strong>Παρακαλούμε προχωρήστε με δική σας ευθύνη.</strong>
            Σε περίπτωση που αποτύχει θα πρέπει να αναβαθμίσετε την εφαρμογή με βάση τις οδηγίες της <a href="https://github.com/fractalbit/misthodosia-online/blob/master/readme.md">τεκμηρίωσης</a>.';
            echo '<br><input type="checkbox" name="accept-danger" id="accept-danger"> <label for="accept-danger" style="min-width: 140px">Κατανοώ τους κινδύνους</label>';
            echo '<br><input id="start-update" type="button" value="Αυτόματη ενημέρωση στην τελευταία έκδοση" disabled>';
            echo '<div id="update-results"></div><hr>';
            echo $this->changelog;
        } else {
            echo '<h3>Έχετε την τελευταία έκδοση της εφαρμογής.</h3>';
        }
    }

    /**
     *  Τυπώνει το changelog
     */
    public function print_notification()
    {
        if (!empty($this->changelog)) {
            echo '<div style="margin: 10px;">Υπάρχει διαθέσιμη μία νέα έκδοση - <a href="update.php">Ενημέρωση της εφαρμογής</a></div>';
        }
    }

    public function download_latest()
    {
        // Download the target version as zip
        $latest = $this->releases[0];
        $download = $this->repo->download_release($latest['zip_url']);
        $this->releases[0]['zip_local'] = $this->folders['downloads'] . 'misthodosia-online-' . $latest['tag'] . '.zip';

        file_put_contents($this->releases[0]['zip_local'], $download);
        fsession::set('releases', $this->releases); // So the location of the zip is cached for the extract method to use
    }

    public function extract_latest()
    {
        // Unzip the file we just downloaded
        $latest = $this->releases[0];
        if (array_key_exists('zip_local', $latest)) {

            $release_filename = $latest['zip_local'];

            if (file_exists($release_filename)) {
                $zip = new ZipArchive;
                $res = $zip->open($release_filename, ZipArchive::CHECKCONS);
                if ($res === TRUE) {
                    $zip->extractTo($this->folders['releases'] . $latest['tag']);
                    $zip->close();
                    // echo 'Ολοκληρώθηκε';            
                } else {
                    switch ($res) {
                        case ZipArchive::ER_NOZIP:
                            die('not a zip archive');
                        case ZipArchive::ER_INCONS:
                            die('consistency check failed');
                        case ZipArchive::ER_CRC:
                            die('checksum failed');
                        default:
                            die('error ' . $res);
                    }
                }
            } else {
                header("HTTP/1.1 404 Not Found");
                exit("Το αρχείο δεν βρέθηκε");
            }
        } else {
            header("HTTP/1.1 404 Not Found");
            exit("Αποτυχία εύρεσης του αρχείου");
        }
    }

    public function copy_latest()
    {
        // Get the directory which contents we want to copy
        $latest = $this->releases[0];
        if (is_dir($this->folders['releases'] . $latest['tag'])) {
            $directories = glob($this->folders['releases'] . $latest['tag'] . '/*', GLOB_ONLYDIR);
            $source_dir = $directories[0];
            // Aaaaaand copy the files to the app_dir folder ... and pray!                 
            xcopy($source_dir, $this->app_dir);
        } else {
            echo 'folder not found';
        }
    }

    public function cleanup_and_log()
    {
        // Delete contents of the updates folder
        // Maybe keep the last version though? NO! delete all
        empty_folder($this->folders['releases']);
        empty_folder($this->folders['downloads']);

        // Now let's log the update process
        $latest = $this->releases[0];
        if ($latest['tag'] === self::get_version()) {
            $message = date('d/m/Y H:i:s', time()) . ' - Η εφαρμογή αναβαθμίστηκε στην έκδοση ' . $latest['tag'] . ' (από την ' . $this->current_version . ')';
            savelog($message);
            // Along with the admin log also log to different file the update actions
            savelog($message, 'update_log.txt');
        } else {
            $message = date('d/m/Y H:i:s', time()) . ' - Έγινε προσπάθεια αναβάθμισης αλλά οι εκδόσεις δεν ταιριάζουν (target: ' . $latest['tag'] . ', current: ' . self::get_version() . ')';
            savelog($message);
            // Along with the admin log also log to different file the update actions
            savelog($message, 'update_log.txt');
        }
    }

    public function print_update_log()
    {
        echo '<div class="box">
                <h3>Ιστορικό ενημερώσεων</h3>';

        $logfile = trailingslashit(APP_DIR) . 'update_log.txt';

        if (file_exists($logfile)) {
            $log = array_reverse(file($logfile));

            foreach ($log as $line) {
                echo $line . '<br />';
            }
        } else {
            echo 'Δεν έχουν πραγματοποιηθεί ακόμα ενημερώσεις';
        }
        echo '</div>';
    }

    public function update()
    {
        // Do it all in one go here. Not used, just to have the overall picture
    }

    public static function get_version()
    {
        is_dir('.git') ? $version_file = 'simulate/version.txt' : $version_file = 'version.txt';
        if (file_exists($version_file)) {
            $version = trim(file_get_contents($version_file));
        } else {
            $version = 'v2.1.2';
        }
        return $version;
    }
}

class Github
{
    private $repo_url;

    public function __construct($url = 'https://api.github.com/repos/fractalbit/misthodosia-online/')
    {
        $this->repo_url = $url;
    }

    public function get($query = 'commits')
    {
        $ch = curl_init();

        $fetch_url = $this->repo_url . $query;
        // Set curl options
        curl_setopt($ch, CURLOPT_URL, $fetch_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl/' . curl_version()['version']); // Απαραίτητο να στείλουμε έναν useragent στο github api για να δουλέψει

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === FALSE) {
            echo "Error: " . curl_error($ch);
        } else {
            return $result;
        }
    }

    public function download_release($fetch_url)
    {
        // Download the specified tag version from github

        $ch = curl_init();

        // Set curl options
        curl_setopt($ch, CURLOPT_URL, $fetch_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl/' . curl_version()['version']); // Απαραίτητο να στείλουμε έναν useragent στο github api για να δουλέψει

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === FALSE) {
            echo "Error: " . curl_error($ch);
        } else {
            return $result;
        }
    }
}
