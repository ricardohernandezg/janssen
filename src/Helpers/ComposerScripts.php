<?php 

namespace Janssen\Helpers;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class ComposerScripts
{

    private static $pkg_name = 'ricardohernandezg/janssen';

    public static function prepareJanssen(Event $e)
    {
        $vendor_dir = $e->getComposer()->getConfig()->get('vendor-dir');
        // check if app directories exists. 
        // If not make the base scaffolding
        $base_dir = $vendor_dir . '/..';
        $sca_dir = $vendor_dir . '/' . self::$pkg_name . '/src/Resource/Scaffolding';
        
        if(!is_dir($base_dir . '/bin'))
            mkdir($base_dir . '/bin');

        copy($sca_dir . '/janssen.100', $base_dir . '/bin/janssen.php');

    }

    /**
     * Copies the entire directory in another
     *
     * @param String $src
     * @param String $dst
     * @return void
     * @see https://www.geeksforgeeks.org/copy-the-entire-contents-of-a-directory-to-another-directory-in-php/
     */
    private static function custom_copy($src, $dst)
    {
        // open the source directory
        $dir = opendir($src);

        // Make the destination directory if not exist
        @mkdir($dst);

        // Loop through the files in source directory
        foreach (scandir($src) as $file) {

            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {

                    // Recursively calling custom copy function
                    // for sub directory
                    self::custom_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    private static function custom_delete($dst)
    {
        if(!is_dir($dst))
            return false;
            
        $it = new \RecursiveDirectoryIterator($dst, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator(
                $it,
                \RecursiveIteratorIterator::CHILD_FIRST
            );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathName());
            } else {
                unlink($file->getPathName());
            }
        }
        rmdir($dst);
    }

}