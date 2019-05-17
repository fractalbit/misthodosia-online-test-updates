<?php
// This may get more complicated and time consuming than i anticipated
// Maybe a better solution would be to create a zip file for the user to download
// Or even just prompt the user to download a copy of the folder before proceeding

ini_set('max_execution_time', 180); // = 3 minutes

require_once 'init.inc.php';

class Backup {
    private $orig_folder = '';
    private $target_folder = '';

    public function __construct(){
        // Set some default values
        $this->orig_folder = trailingslashit(dirname(__FILE__));
        $this->target_folder = trailingslashit(dirname($this->orig_folder)) . Backup::gen_backup_name() ;

        dump($this->orig_folder);
        dump($this->target_folder);
    }

    private static function gen_backup_name(){
        return 'misthodosia-online.bakcup.' . time() . '/';
    }

    public function create($type = 'zip'){
        if($type === 'zip'){
            
        }else{
            $orig = $this->orig_folder;
            $target = $this->target_folder;

            exec('cp -r '.$orig.' '.$target);
        }

        // Now i should save the backup_folder name so i should delete failed backups
    }
}

$backup = new Backup();
$backup->create('zip');


$dir1 = 'temp-experiments/dir_compare_1/';
$dir2 = 'temp-experiments/dir_compare2/';

if(verify_backup($dir1, $dir2)){
    echo '<div>Success! The backup folder is an exact copy of the original</div>';
}else{
    echo '<div class="error">FAIL! the backup folder is NOT an exact copy</div>';
}

// Does not support flag GLOB_BRACE        
function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
    {
    $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}


function verify_backup($dir1, $dir2){
    $structure_verification = false;
    $contents_verification = true;

    $dir1_list = glob_recursive($dir1.'**');
    $dir2_list = glob_recursive($dir2.'**');

    // dump($dir1_list);
    // dump($dir2_list);

    $dir1_str = str_replace($dir1, '', implode('', $dir1_list));
    $dir2_str = str_replace($dir2, '', implode('', $dir2_list));

    // dump($dir1_str);
    // dump($dir2_str);

    if($dir1_str === $dir2_str){
        echo $dir1 . ' and ' . $dir2 . ' have the same file and folder structure';   
        $structure_verification = true; 
    }else {
        echo $dir1 . ' and ' . $dir2 . ' are not the same';        
        $structure_verification = false; 
    }
    echo '<hr />';
    
    foreach($dir1_list as $file){
        if(is_file($file)){
            $sha1 = '';
            $sha2 = '';

            $file2 = $dir2 . str_replace($dir1, '', $file);
            // echo $file2 . ' <br />';
            $sha1 = sha1_file($file);
            $sha2 = sha1_file($file2);
            // echo $sha1 . '<br>';
            // echo $sha2 . '<br>';
            // echo '<hr>';
            if($sha1 !== $sha2){
                $contents_verification = false;
                break;
            }
        }
    }

    if($structure_verification && $contents_verification){
        return true;
    }else{
        return false;
    }
}

