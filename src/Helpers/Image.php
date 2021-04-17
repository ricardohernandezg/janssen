<?php
/**
* Image Helper
*
* This class handles image processing
* 
* @author Nidal Abdulbaky 
* @author Ricardo Hernandez
* 
*/
/*

 // Usage:
 //Load the original image
 if (isset($_GEt('image')) and !empty($_GET('image'))) {
        $picture = $_GEt('image');
        $image = new SimpleImage($picture);

 // Resize the image to 600px width and the proportional height
        $image->resizeToWidth(600);
        $image->save('pic_resized.jpg');

 // Create a squared version of the image
        $image->square(200);
        $image->save('pic_squared.jpg');

 // Scales the image to 75%
        $image->scale(75);
        $image->save('pic_scaled.jpg');

 // Resize the image to specific width and height
        $image->resize(80, 60);
        $image->save('pic_resized2.jpg');

        $image->watermark(800, 600, $watermark_url = 'tumedico_wm.png');
        $image->save('pic_watermarket.jpg');

 // Output the image to the browser:
        $image->output();
  } else {
        echo "error in getting image";
  }
*/

namespace Janssen\Helpers;

use Janssen\Helpers\Exception;

class Image
{
    protected $image;
    protected $image_type;

    public function __construct($filename = null)
    {
        if (!empty($filename)) {
            $this->load($filename);
        }
    }

    public function load($filename)
    {
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if ($this->image_type == IMAGETYPE_JPEG) {
            $this->image = imagecreatefromjpeg($filename);
        } elseif ($this->image_type == IMAGETYPE_GIF) {
            $this->image = imagecreatefromgif($filename);
        } elseif ($this->image_type == IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($filename);
        } else {
            throw new Exception("The file you're trying to open is not supported");
        }
    }

    public function __destruct()
    {
        if($this->image)
            imagedestroy($this->image);
        
        $this->image = null;
    }

    public static function getImageType($filename)
    {
        $image_info = getimagesize($filename);
        if ($image_info) {
            return $image_info[2];
        } else {
            return false;
        }
    }

    public function save($filename, $image_type = 'auto', $compression = 75, $permissions = null)
    {
        if($image_type == 'auto')
            $image_type = $this->image_type;

        if ($image_type == IMAGETYPE_JPEG || is_null($image_type)) {
            $r = imagejpeg($this->image, $filename, $compression);
        } elseif ($image_type == IMAGETYPE_GIF) {
            $w = $this->getWidth();
            $h = $this->getHeight();
            $new = imagecreatetruecolor($w, $h);
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
            imagecopyresampled($new, $this->image, 0, 0, 0, 0, $w, $h, $w, $h);
            $r = imagegif($new, $filename);
        } elseif ($image_type == IMAGETYPE_PNG) {
            $w = $this->getWidth();
            $h = $this->getHeight();
            $new = imagecreatetruecolor($w, $h);
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
            imagecopyresampled($new, $this->image, 0, 0, 0, 0, $w, $h, $w, $h);
            $r = imagepng($new, $filename);
        }else
            return false;

        if ($permissions != null) {
            chmod($filename, $permissions);
        }
        return $r;
    }

    /**
     * Outputs image to browser directly
     *
     * @param [type] $image_type
     * @param integer $quality
     * @return void
     */
    public function output($image_type = IMAGETYPE_JPEG, $quality = 80)
    {
        if ($image_type == IMAGETYPE_JPEG) {
            header("Content-type: image/jpeg");
            imagejpeg($this->image, null, $quality);
        } elseif ($image_type == IMAGETYPE_GIF) {
            header("Content-type: image/gif");
            imagegif($this->image);
        } elseif ($image_type == IMAGETYPE_PNG) {
            header("Content-type: image/png");
            imagepng($this->image);
        }
    }

    public function toBase64($image_type = IMAGETYPE_JPEG, $quality = 80, $prepend_data = false)
    {
        ob_start();
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image, null, $quality);
            $h = 'data:image/jpeg;base64, ';
        } elseif ($image_type == IMAGETYPE_GIF) {
            imagegif($this->image);
            $h = 'data:image/gif;base64, ';
        } elseif ($image_type == IMAGETYPE_PNG) {
            imagepng($this->image);
            $h = 'data:image/png;base64, ';
        }
        $contents = ob_get_contents(); 
        ob_end_clean(); 
        
        return (($prepend_data)?$h:'') . base64_encode($contents);
    }

    public function getWidth()
    {
        return imagesx($this->image);
    }

    public function getHeight()
    {
        return imagesy($this->image);
    }

    /**
     * Checks if image is square
     *
     * @param integer $tolerance indicates how much percentage
     * of difference is tolerated. Default is 0
     * @return void
     */
    public function isSquare($tolerance = 0)
    {
        $reason = $this->getReason($this->getWidth(), $this->getHeight());
        return ($reason <= $tolerance);
    }

    public function resizeToHeight($height)
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        return $this->resize($width, $height);
    }

    public function resizeToWidth($width)
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        return $this->resize($width, $height);
    }

    public function makeSquare($size)
    {
        $new_image = imagecreatetruecolor($size, $size);

        if ($this->getWidth() > $this->getHeight()) {
            $this->resizeToHeight($size);
            imagecopy($new_image, $this->image, 0, 0, ($this->getWidth() - $size) / 2, 0, $size, $size);
        } else {
            $this->resizeToWidth($size);
            imagecopy($new_image, $this->image, 0, 0, 0, ($this->getHeight() - $size) / 2, $size, $size);
        }

        $this->image = $new_image;
        return true;
    }

    public function scale($scale)
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        return $this->resize($width, $height);
    }

    public function resize($width, $height)
    {
        if ($this->image_type == IMAGETYPE_GIF || $this->image_type == IMAGETYPE_PNG) {
            $w = $this->getWidth();
            $h = $this->getHeight();
            $new_image = imagecreatetruecolor($w, $h);
            $new = null;
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
            $r = imagecopyresampled($new, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());            
        } else { //JPEG //original
            $new_image = imagecreatetruecolor($width, $height);
            $r = imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        }
        $this->image = $new_image;
        return true;
    }

    /*
    public function crop($width, $height)
    {
        $ratio_source = $this->getWidth() / $this->getHeight();
        $ratio_dest = $width / $height;

        if ($ratio_dest < $ratio_source) {
            $this->resizeToHeight($height);
        } else {
            $this->resizeToWidth($width);
        }

        $x = ($this->getWidth() / 2) - ($width / 2);
        $y = ($this->getHeight() / 2) - ($height / 2);

        return $this->cut($x, $y, $width, $height);
    }
    */

    public function watermark($watermark_file, $left = 0, $top = 0)
    {
        $watermark = imagecreatefrompng($watermark_file);

        $wm_width = imagesx($watermark);
        $wm_height = imagesy($watermark);

        // if watermark is greater than image. 
        if($wm_width > $this->getWidth() || $wm_height > $this->getHeight()){
            // create a new resource image with new dimentions
            $r = imagecopyresized($this->image, $watermark, 0,0,0,0,$this->getWidth(), $this->getHeight(), $wm_width, $wm_height);
        }else{
            //calculate the difference between the image and its watermark and put it in the middle
            $left = floor((imagesx($this->image) - $wm_width)/2);
            $top = floor((imagesy($this->image) - $wm_height)/2);
            $r = imagecopy($this->image, $watermark, $left, $top, 0, 0, imagesx($watermark), imagesy($watermark));
        }
        return $r;
    }

    public function getReason(){
        return self::calculateReason($this->getWidth(),$this->getHeight());
    }

    /**
     * Calculates the reason between width and height 
     *
     * @param Int $width
     * @param Int $height
     * @return Float
     */
    public static function calculateReason($width, $height){
        $r = ($width >= $height)?($width/$height):($height/$width);
        return ($r>=1)?($r-1):$r;
    }

    public static function mimeTypeIsImage($mime)
    {
        return (substr(strtolower($mime), 0, 5) == 'image');
    }

}
