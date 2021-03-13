<?php 

namespace Janssen\Helpers;

class File
{

    public static function extension($filename = '')
    {
        $i = strrpos($filename, '.');
        if($i !== false)
            return substr($filename, $i+1);
        else
            return '';
    }

    public static function getMimeType($file_path)
    {
        return (is_file($file_path))?mime_content_type($file_path):false;
    }

}