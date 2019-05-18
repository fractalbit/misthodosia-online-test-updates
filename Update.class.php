<?php
require_once 'init.inc.php';
require_once 'Parsedown.php';

class App {

    private $current_version = '3.0.0'; 
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
        $download = $this->repo->get_release($zipball);
        $release_filename = $this->folders['downloads'] . 'misthodosia-online-' . $latest['tag_name'] . '.zip';
        dump($release_filename);        
        file_put_contents($release_filename, $download);

        // Unzip the file we just downloaded
        $zip = new ZipArchive;
        $res = $zip->open($release_filename);
        if ($res === TRUE) {
            $zip->extractTo($this->folders['releases'] . $latest['tag_name']);
            $zip->close();
            echo 'Το αρχείο αποσυμπιέστηκε με επιτυχία';            
        } else {
            echo 'Συνέβη ένα σφάλμα κατά την αποσυμπίεση του αρχείου';   
        }

        // Get the directory which contents we want to copy
        $directories = glob($this->folders['releases'] . $latest['tag_name'] . '/*' , GLOB_ONLYDIR);
        $source_dir = $directories[0];
        
        // Aaaaaand copy the files to the app_dir folder ... and pray!
        
        dump($source_dir);
        dump($this->app_dir);
        xcopy($source_dir, $this->app_dir);

        // Delete the .gitignore files from the simulate directory
        // if($this->simulate){
        //     $for_del = glob($this->simulate_dir . '**/*/.gitignore');
        //     dump($for_del);
        //     // foreach($for_del as $file)
        // }
               
        // Maybe also change directory permissions after copying
    }

    private function cleanup(){
        // Delete contents of the updates folder
        // Maybe keep the last version though?
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

    public function unzip(){
        // Just try the unzip for now with hardcoded file and path
        $zip = new ZipArchive;
        $res = $zip->open('./temp-experiments/misthodosia-online-master.zip');
        if ($res === TRUE) {
            $zip->extractTo('./temp-experiments/new_repo/');
            $zip->close();
            echo 'woot!';

            // Now copy the contents of misthodosia-online-master
            $orig = './temp-experiments/new_repo/misthodosia-online-master/';
            $target = './temp-experiments/new_repo/';

           xcopy($orig, $target);
        } else {
            echo 'doh!';   
        }
    }

    public function get_release($fetch_url){
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

/**
 * Copy a file, or recursively copy a folder and its contents
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.1
 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @param       int      $permissions New folder creation permissions
 * @return      bool     Returns true on success, false on failure
 */
function xcopy($source, $dest, $permissions = 0755)
{
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        xcopy("$source/$entry", "$dest/$entry", $permissions);
    }

    // Clean up
    $dir->close();
    return true;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

<?php

$repo = new Github();
$mo = new App($repo);
$mo->update();
// $mo->get_raw_data();

// $repo->get_release('https://api.github.com/repos/fractalbit/misthodosia-online/zipball/v2.1.1');
// $repo->unzip();



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

?>

    
</body>
</html>