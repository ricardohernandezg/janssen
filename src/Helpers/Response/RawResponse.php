<?php 

namespace Janssen\Helpers\Response;

use Janssen\Engine\Response;
use Janssen\Helpers\Exception;


class RawResponse extends Response
{

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        return $this;
    }

    /**
     * Load the php file that will be returned.
     * We'll assume the templates folder is where it should be
     *
     * @param String $page
     * @return void
     */
    public function loadPage($page)
    {
        $a = \Janssen\App::appPath();
        // we expect the templates are located in the same folder as public
        $template_path = $a . "../templates";
        $page_candidate = $template_path . '/' . $page;
        if(file_exists($page_candidate)){
        // turn off echo and capture 
        ob_start();
        require($page_candidate);
        // save capture
        $this->content = ob_get_contents();
        // turn on echo
        ob_end_clean(); 
        }else
            throw new Exception("File $page not found", 404);

        return $this;
    }

}