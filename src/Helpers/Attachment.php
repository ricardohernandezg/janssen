<?php 

namespace Janssen\Helpers;

use Janssen\Helpers\Image;
use Janssen\Helpers\Exception;
use Janssen\Engine\Request;

class Attachment
{

    /**
     * This mapping will depend in the server registered mimetypes
     * to check if same mimetype sent but named different is recognized
     * by the server. Precedence is the server information.
     *
     * The array is configured as 'server_mimetype' => ['accepted_reported_mimetypes',...]
     *
     * @var array
     */
    private static $mimetype_map = [
        'image/x-ms-bmp' => ['image/bmp'],
    ];

    /**
     * This function will search the configured mimetypes to find if
     * there is a map for the server mimetype and reported mimetype and
     * the file can be accepted with that mimetype.
     *
     * This function is useful when the mime types of server and browser
     * reported don't match but developer knows that the file type is the correct
     * but are named different due to OS or browser policies.
     *
     * Eg: Bitmaps are named image/bmp in browser, but image/x-ms-bmp in Debian
     *
     * @param string $server_mimetype Server indicated mimetype
     * @param string $reported_mimetype Client indicated mimetype
     * @return boolean
     */
    public static function checkMimeType($server_mimetype, $reported_mimetype)
    {
        $sm = (isset(self::$mimetype_map[$server_mimetype])?self::$mimetype_map[$server_mimetype]:false);
        if ($sm) {
            if (is_array($sm)) {
                // there are several mappings
                return in_array($reported_mimetype, $sm);
            } else {
                // there is only one mapping
                return ($reported_mimetype == $sm);
            }
        }
        return false;
    }

    /**
     * ValidateAttachments
     *
     * This function takes the $_FILES parameter and checks if intended
     * upload files meet the requirements to be hosted.
     *
     */

    public static function validateAttachments($attachment_config)
    {
        $valid = '';
        if (isset($_FILES)) {
            foreach ($attachment_config['files'] as $name => $rules) {
                $it = $_FILES[$name]['type'];
                $is = $_FILES[$name]['size'];
                // check file type

                if ($_FILES[$name]['error'] > 0) {
                    continue;
                }

                if (!in_array($it, $rules['supported_types'])) {
                    $valid .= 'File type is not supported. ';
                }
                // check file size
                $size_rule = (isset($rules['max_file_size']) && $rules['max_file_size'] > 0) ? $rules['max_file_size'] : $is;
                if ($is > $size_rule) {
                    $valid .= 'The file size is greater than allowed. ';
                }
            }
        }
        return ($valid)?$valid:true;
    }

    /**
     * ProcessAttachments
     *
     * This functions takes the $_FILES parameter and process the files inside
     * It's needed to have $attachment_config defined in module with corresponding
     * structure to this function to work.
     *
     * $attachment_config array must have this structure
     *
     * 'files' (array: set of key=>value with specific settings for each attachment)
     *
     * struncture of files array is as following:
     *
     *   '{name}' (array: name refers to the file name in $_FILES array, value is array as described)
     *   [
     *     'supported_types' (array: set of supported mime file types)
     *     'max_file_size' (number: max accepted file size in bytes, ommited if 0)
     * 
     *     'image_width' (number: size in pixels to apply to original image)
     *     'image_height' (number: size in pixels to apply to original image)
     * 
     *     'watermark_file' (string: file to apply as watermark)
     *     // 'watermark_file_types' (array: set of mime file types to apply watermark) NOT USED
     * 
     *     'thumbnail_filename' (filename to apply to thumbnail file, ommited if empty. To use the other 
     *                          thumbnail attributes this member MUST BE set)
     *     'thumbnail_width' (number: size in pixels to apply to thumbnail)
     *     'thumbnail_height' (number: size in pixels to apply to thumbnail)
     *
     *     'filename' (filename to apply to file)
     *     'folder_path' (folder to save the file)
     *     'thumbnail_path' (folder to save thumbnal. If ommited folder_path will be used) 
     * 
     *   ],
     *
     */

    public static function processAttachments(Array $attachment_config = [])
    {
        $request = new Request;

        if(empty($attachment_config))
            throw new Exception('You need to set the configuration to process attached files');

        // prevent exception for non existing array member
        $attachment_config['files'] = empty($attachment_config['files'])?[]:$attachment_config['files'];

        $ret = true;
        
        foreach ($attachment_config['files'] as $attachment_name => $config) {
            // process each file with given data
           
            if (isset($config['folder_path']) && is_dir($config['folder_path'])) 
                $folder_path = $config['folder_path'];            
            else
                throw new Exception('folder_path must be a directory and must be writable');

            if (isset($config['filename'])) 
                $dest_filename = $config['filename'];
            else
                throw new Exception('File name is mandatory in config to save attachment');

            $orig_file = $request->file($attachment_name)['tmp_name'];
            if(empty($orig_file))
                throw new Exception('original file path is mandatory in config to save attachment');

            $dest = "$folder_path/$dest_filename";
            // is it an image?
            if (Image::getImageType($orig_file) !== false) {

                // process image
                $ret = $ret && self::processImageAttachment($orig_file, $config);

            } else {
                // is not image
                // as this is admmited and isn't image we copy directly
                $ret = $ret && move_uploaded_file($orig_file, $dest);
            }
        } // foreach
        return $ret;
    } 


    /**
     * Make all the image process
     *
     * @param array $orig_file_path
     * @param array $rules
     * 
     * @return Boolean
     */
    private static function processImageAttachment($orig_file_path, Array $rules)
    {
        $image = new Image($orig_file_path);
        // get the info to make the resizing
        // if user only sent one measure we calculate the other one to keep the aspect ratio

        $r = true;

        $fp = empty($rules['folder_path'])?null:$rules['folder_path'];
        if($fp && !is_dir($fp))
            throw new Exception('Watermark file path is not valid');

        $fn = empty($rules['filename'])?null:$rules['filename'];
        if(!$fn)
            throw new Exception('File name is not valid');            

        // if thumbnail path is ommited we'll use the same folder for thumbs
        $tp = empty($rules['thumbnail_path'])?$fp:$rules['thumbnail_path'];

        // WATERMARK
        $wf = empty($rules['watermark_file'])?null:$rules['watermark_file'];
        if($wf){
            if(!is_file($wf))
                throw new Exception('Watermark file path is not valid');

            $r = $r && $image->watermark($wf);
        }

        if(!$r)
            throw new Exception('Error watermarking image');

        // THUMBNAIL
        // will we use thumbnail?
        $tfn = empty($rules['thumbnail_filename'])?null:$rules['thumbnail_filename'];
        if($tfn){
            $tw = empty($rules['thumbnail_width'])?0:$rules['thumbnail_width'];
            $th = empty($rules['thumbnail_height'])?0:$rules['thumbnail_height'];
            $thumbnail = clone $image;
            if($tw > 0 && $th > 0){
                $r = $r && $thumbnail->resize($tw, $th);
            }else{
                if($tw > 0)
                    $r = $r && $thumbnail->resizeToWidth($tw);
                elseif($th > 0)
                    $r = $r && $thumbnail->resizeToHeight($th);
            }

            $r = $r && $thumbnail->save("$tp/$tfn");
        }

        if(!$r)
            throw new Exception('Error generating thumbnail');

        //  RESIZE
        $iw = empty($rules['image_width'])?0:$rules['image_width'];
        $ih = empty($rules['image_height'])?0:$rules['image_height'];
        if($iw > 0 && $ih > 0){
            // both measures were given. This forces the resize
            $r = $r && $image->resize($iw, $ih);
        }else{
            // user sent one or none
            if($iw > 0)
                $r = $r && $image->resizeToWidth($iw);
            elseif($ih > 0)
                $r = $r && $image->resizeToHeight($ih);
                
        }

        $r = $r && $image->save("$fp/$fn");

        if(!$r)
            throw new Exception('Error saving image file');

        return $r;
    }
    
    /**
     * Gets a base64 encoded string useful for showing content in html,
     * most likely images
     *
     * @param string $path
     * @return string
     */
    public static function getBase64File($path, $with_extras = false)
    {
        $fe = is_file($path);
        if(!$fe)
            return '';
        $b64 = "";
        if($with_extras){
            $m = mime_content_type($path);
            if($m)
                $b64 = "data:$m;base64, ";                
        }
        $t = file_get_contents($path);
        $b64 .= base64_encode($t);
        return $b64;
    }

    /**
     * checks the configuration for the presence of image transformation
     * members
     *
     * @param Array $config the configuration sent by user
     * @return Boolean
     */
    private static function configHasImageTransformation(Array $config)
    {
        return !(empty($config['attachment_width']) &&
            empty($config['attachment_height']) &&
            empty($config['watermark_file']) &&            
            empty($config['watermark_file_types']) &&      
            empty($config['thumbnail_filename']));

    }
}
