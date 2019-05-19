<?php
class App {

    private static $version  = 'v3.0.0'; // static variables can only be accessed from static methods
    private $current_version = 'v3.0.0'; 
    // This should be changed every time we push to github
    // while also creating the actual correspoding git tag
    // And don't foget to push tags to github

    private $app_dir = '';

    private $folders = array();

    private $simulate = false;
    private $simulate_ver = 'v2.0.0';
    private $simulate_dir = 'simulate/';

    private $releases = array();
    private $tags = array();
    private $commits = array();

    private $changelog = ''; // If this is not empty, it also means there is a new version

    private $repo_raw_data = array();

    private $repo; // This will hold the github instance
    
    public function __construct($github_app_repo){
        $this->app_dir = trailingslashit(dirname(__FILE__));
        
        if(is_dir('.git')){ // This means we are in a dev enviroment. We should be carefull not to overwrite our working directory!
            $this->simulate = true;

            $this->app_dir  = $this->app_dir . $this->simulate_dir;
            $this->current_version = $this->simulate_ver;
        }

        $this->folders = array(
            'updates'   => $this->app_dir . 'updates/',    
            'releases'  => $this->app_dir . 'updates/releases/',    
            'downloads' => $this->app_dir . 'updates/downloads/',    
        );

        // I should create the folders if they do not exist

        dump($this->app_dir);
        dump($this->current_version);
        dump($this->folders);

        $this->repo = $github_app_repo;
    }

    public function get_releases(){
        // Stores all the releases and populates the changelog
        
        $repo = $this->repo;
        
        $releases = json_decode($repo->get('releases'), true);

        foreach($releases as $data){
            $Parsedown = new Parsedown();
            $html = $Parsedown->text('### ' . $data['name'] . "\n" . $data['body']);

            $release = array(
                'tag' => $data['tag_name'], 
                'name' => $data['name'],
                'created' => App::convert_date($data['created_at']), 
                'published' => App::convert_date($data['published_at']),
                'zip_url' =>  $data['zipball_url'],
                'html' => $html,
            );

            if(version_compare($this->current_version, $release['tag']) < 0){
                // Add to this versions info to the changeog
                $this->changelog .= $html;
            }

            $this->releases[] = $release;        
        }

        // echo $this->releases[0]['html'];
        // echo $this->releases[1]['html'];

        // dump($this->releases);
    }

    public static function convert_date($ISO_8601){
        $date = new DateTime($ISO_8601);
        return $date->format("d/m/Y H:i");
    }

    public function print_newer(){
        if(!empty($this->changelog)){
            echo '<h3>Υπάρχουν διαθέσιμες ενημερώσεις...</h3>';
            echo '<input id="start-update" type="button" value="Αυτόματη ενημέρωση στην τελευταία έκδοση">';
            echo '<div id="update-results"></div><hr>';
            echo $this->changelog;
        }else{
            echo '<h3>Έχετε την τελευταία έκδοση της εφαρμογής.</h3>';
        }
    }

    public function download_latest(){
        // Download the target version as zip
        $latest = $this->releases[0];        
        $download = $this->repo->download_release($latest['zip_url']);
        $this->releases[0]['zip_local'] = $this->folders['downloads'] . 'misthodosia-online-' . $latest['tag'] . '.zip';
        // dump($release_filename);        
        file_put_contents($this->releases[0]['zip_local'], $download);
    }

    public function extract_latest(){
        // Unzip the file we just downloaded
        $latest = $this->releases[0];   
        $release_filename = $latest['zip_local'];

        $zip = new ZipArchive;
        $res = $zip->open($release_filename, ZipArchive::CHECKCONS);
        if ($res === TRUE) {
            $zip->extractTo($this->folders['releases'] . $latest['tag']);
            $zip->close();
            // echo 'Ολοκληρώθηκε';            
        } else {
            switch($res) {
                case ZipArchive::ER_NOZIP:
                    die('not a zip archive');
                case ZipArchive::ER_INCONS :
                    die('consistency check failed');
                case ZipArchive::ER_CRC :
                    die('checksum failed');
                default:
                    die('error ' . $res);
            }            
        }
    }

    public function copy_latest(){
        // Get the directory which contents we want to copy
        $latest = $this->releases[0];  
        $directories = glob($this->folders['releases'] . $latest['tag'] . '/*' , GLOB_ONLYDIR);
        $source_dir = $directories[0];
         
        // Aaaaaand copy the files to the app_dir folder ... and pray!         
        // dump($source_dir);
        // dump($this->app_dir);
        xcopy($source_dir, $this->app_dir);
    }

    public function update(){
        // Lots more to do. Just a test

        // Get the correct version. Maybe just get the latest is enough for our purposes
        $latest = json_decode($this->repo->get('releases/latest'), true);
        // dump($latest);
        $zipball = $latest['zipball_url'];
        dump($zipball);
        // We could just hardcode this url: https://github.com/fractalbit/misthodosia-online/archive/master.zip
        // To get the latest pushed commit.
        // But maybe it's better to download specific versions
        // So there is always a direct assosciation between the current app version and what we get from github

        // Download the target version as zip
        $download = $this->repo->download_release($zipball);
        $release_filename = $this->folders['downloads'] . 'misthodosia-online-' . $latest['tag_name'] . '.zip';
        dump($release_filename);        
        file_put_contents($release_filename, $download);

        // Unzip the file we just downloaded
        $zip = new ZipArchive;
        $res = $zip->open($release_filename, ZipArchive::CHECKCONS);
        if ($res === TRUE) {
            $zip->extractTo($this->folders['releases'] . $latest['tag_name']);
            $zip->close();
            echo 'Το αρχείο αποσυμπιέστηκε με επιτυχία';            
        } else {
            switch($res) {
                case ZipArchive::ER_NOZIP:
                    die('not a zip archive');
                case ZipArchive::ER_INCONS :
                    die('consistency check failed');
                case ZipArchive::ER_CRC :
                    die('checksum failed');
                default:
                    die('error ' . $res);
            }            
        }

        // Get the directory which contents we want to copy
        $directories = glob($this->folders['releases'] . $latest['tag_name'] . '/*' , GLOB_ONLYDIR);
        $source_dir = $directories[0];
        
        // Aaaaaand copy the files to the app_dir folder ... and pray!
        
        dump($source_dir);
        dump($this->app_dir);
        xcopy($source_dir, $this->app_dir);

        // Maybe i should add some logging here
        // So the admin knows when they updated the app (store date and from_version, to_version)

        // Maybe also change directory permissions after copying
    }

    private function cleanup(){
        // Delete contents of the updates folder
        // Maybe keep the last version though?
    }

    public static function get_version(){
        return self::$version;
    }
}

class Github {
    private $repo_url;    

    public function __construct($url = 'https://api.github.com/repos/fractalbit/misthodosia-online/'){
        $this->repo_url = $url;
    }

    public function get($query = 'commits'){
        $ch = curl_init();

        $fetch_url = $this->repo_url . $query;
        // Set curl options
        curl_setopt($ch, CURLOPT_URL, $fetch_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);               
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl/' . curl_version()['version']); // Απαραίτητο να στείλουμε έναν useragent στο github api για να δουλέψει

        $result = curl_exec($ch);
        curl_close($ch);
        
        if($result === FALSE) {
            echo "Error: " . curl_error($ch);
        } else {            
            return $result;
        }
    }

    public function download_release($fetch_url){
        // Download the specified tag version from github

        $ch = curl_init();

        // Set curl options
        curl_setopt($ch, CURLOPT_URL, $fetch_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);               
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl/' . curl_version()['version']); // Απαραίτητο να στείλουμε έναν useragent στο github api για να δουλέψει

        $result = curl_exec($ch);
        curl_close($ch);        
        
        if($result === FALSE) {
            echo "Error: " . curl_error($ch);
        } else {            
            return $result;
        }
    }
}

