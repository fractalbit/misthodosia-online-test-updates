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


    public function get_raw_data(){       
        $repo = $this->repo;
        // I don't think we have to download the commits
        // We will work with tags for version checking and releases if there is one for that tag (for better changelong)
        // $this->repo_raw_data['commits'] = json_decode($repo->get('commits'), true); // Set second parameter to true to return an assosiative array instead of an object
        $this->repo_raw_data['tags'] = json_decode($repo->get('tags'), true); 
        $this->repo_raw_data['releases'] = json_decode($repo->get('releases'), true);
        
        $Parsedown = new Parsedown();        
        $md = '## ' . $this->repo_raw_data['releases'][0]['tag_name'] . "\n" . $this->repo_raw_data['releases'][0]['body'];
        echo $Parsedown->text($md);
        
        dump($this->repo_raw_data['releases']);
        dump($this->repo_raw_data['tags']);
        // dump($this->repo_raw_data['commits']);

        $this->save_data_by_sha();
        dump($this->commits);
        // $this->get_release_by_sha('a4f9f749f92b99a8212d38670110a303f3701281');
    }

    private function get_release_by_sha($commit_hash){
        if(!empty($commit_hash)){ // Should also check for validity
            $tag = array_search($commit_hash, $this->repo_raw_data['tags']);
            if($tag) {
                echo $tag;
            }
        }else{
            echo 'Error: Hash not provided';
        }
    }

    private function save_data_by_sha(){
        // Store just the data we want indexed by sha hash
        $releases = $this->repo_raw_data['releases'];
        $tags = $this->repo_raw_data['tags'];
        $commits = $this->repo_raw_data['commits'];

        foreach($commits as $cdata){
            $sha = $cdata['sha'];
            $this->commits[$sha] = array('message' => $cdata['commit']['message'], 'date' => $cdata['commit']['committer']['date']);
            echo App::convert_date($cdata['commit']['committer']['date']) . '<br />';
        }
    }

    public static function convert_date($ISO_8601){
        $date = new DateTime($ISO_8601);
        return $date->format("d/m/Y H:i");
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

