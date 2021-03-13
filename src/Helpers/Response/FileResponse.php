<?php 

namespace Janssen\Helpers\Response;

use Janssen\Engine\Response;

class FileResponse extends Response
{

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        // capture buffering
        // ask server for file mimetype
        // set mimetype in header
        // require file
        // turn off capture
        // save file contents to variable
        
        return $this;
    }

}