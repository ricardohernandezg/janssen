<?php 

namespace Janssen\Helpers\Response;

/**
 * The sole intention of having a View class is to force Janssen to 
 * use the Plates templates. Janssen View will prepare the data to make
 * Plates process and return the result to engine.
 * 
 * This class support Events, so you can interfere with the class in determinate
 * times
 */

use Janssen\Helpers\Exception;
use Janssen\Engine\Event;
use Janssen\Engine\Response;
use \League\Plates\Engine;
use App\Auth\AdminGuard;
use App\Auth\UserGuard;


class ViewResponse extends Response
{

    protected $template;
    protected $data = [];

    private $template_path = '';

    public function __construct($template_name = '', $data = [])
    {
        $this->setTemplatePath(App::appPath() . '../templates');

        if($template_name !== '')
            $this->setTemplate($template_name, $data);
        
    }

    public function render($data = [])
    {
    
        // data=>filename overwrites setted template
        if(!empty($data['filename']))
            $this->template = $this->filenameToTemplate($data['filename']); 

        if(empty($this->template))
            throw new Exception('No template defined for this response!');

        $r = Event::invoke('ViewResponse.BeforeRender', $this);
        $template = new Engine($this->template_path);

        // inject all the assets variables to each template
        $ec_assets = App::getConfig('assets');
        if(!empty($ec_assets) && is_array($ec_assets)){
            foreach($ec_assets as $k=>$v){
                $assets[$k] = App::assets($k);
            }
        }else
            $assets = [];

        $data = array_merge($assets, $this->data, $data);

        $r = $template->render($this->template, $data);
        if(empty($r))
            throw new Exception("The template {$this->template} is empty!", 500);
        $this->content = $r;
        return $r;
    }

    public function setTemplate($template, $data = [])
    {
        if(strtolower(substr(trim($template), -4)) == '.php')
            $template = strtolower(substr($template, 0, -4));
        $this->template = $template;
        if(!empty($data))
            $this->setTemplateData($data); // this is absolutely redundant but kept for compat reason
        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setTemplatePath($path)
    {
        $this->template_path = $path;
        return $this;
    }

    public function getTemplatePath()
    {
        return $this->template_path;
    }

    public function setTemplateData(Array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function getTemplateData()
    {
        return $this->data;
    }

    private function filenameToTemplate($filename)
    {
        if(strtolower(substr(trim($filename), -4)) == '.php')
            return strtolower(substr($filename, 0, -4));
        else
            return $filename;
    }
}
