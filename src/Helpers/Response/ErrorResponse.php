<?php

namespace Janssen\Helpers\Response;

use Janssen\Engine\Request;
use Janssen\Engine\Response;
use Janssen\Resource\EmbedFonts;
use Janssen\Helpers\Exception;
use Janssen\Engine\Config;
use Throwable;

class ErrorResponse extends Response
{
	protected $message;
	protected $code;
	protected $advise;

	private $prepend_stack_items;
	private $force_stack;

	protected $is_json;
	protected $exception;
	private $line_qty = 5;

	public function __construct($message = '', $code = 0, $advise = '')
	{
		parent::__construct();
		$this->message = $message;
		$this->code = $code;
		$this->advise = $advise;
		// this makes default error handling an ajax response.
		$this->setContent(['error' => true, 'message' => $message, 'code' => $code, 'advise' => $advise]);

		$this->isJson(Request::expectsJSON());

	}

	public function setException(Throwable $e)
	{
		$this->exception = $e;
		$this->setMessage($e->getMessage());
		$this->setCode($e->getCode());
		
		if($e instanceof Exception)
			$this->setAdvise($e->getAdvise());
		
		return $this;
	}

	public function isJson($is_json = true)
	{
		$this->is_json = $is_json;
		if($is_json)
			$this->setContentType('application/json');
		else
			$this->setContentType();
		
		return $this;
	}

	public function setCode($code = 500)
	{
		$this->code = $code;
		return $this;
	}

	public function setMessage($message = 'Internal server error')
	{
		$this->message = $message;
		return $this;
	}

	public function setAdvise($advise = '')
	{
		$this->advise = $advise;
		return $this;
	}

	public function makeFriendlyJson()
	{
		return json_encode(
			[
				'error' => true,
				'message' => $this->message,
				'code' => $this->code,
				'advise' => $this->advise
			]);
	}

	/**
	 * Makes a debug trace in JSON format
	 * 
	 * @return Array
	 */
	public function makeDebugJson()
	{
		if($this->exception instanceof Exception){
			if($this->exception->getRewriteStack())
				$stack = $this->exception->getStackItems();
			else
				$stack = $this->exception->getTrace();
		}elseif ($this->exception instanceof Throwable){
			$stack = $this->exception->getTrace();
		}else
			$stack = [];

		$a = [
			'error' => true,
			'message' => $this->message,
			'code' => $this->code,
			'advise' => $this->advise,	
		];
		if($stack)
			$a['stack'] = $this->prepareJSONizableStack($stack);

		return json_encode($a);
	}

	public function makeFriendlyHtml()
	{
		/**
		 * @todo check if the user made a template to substitute exceptions
		 * and use it
		 */
		$titillium = EmbedFonts::$titillium;
		$montserrat = EmbedFonts::$montserrat;

		$html = "<!DOCTYPE html>
        <html>
        <head>
		<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        
        <title>{$this->code} - We got an error processing your request..</title>
        
        <style type='text/css'>

        @font-face {
            font-family: 'Titillium Web';
            src: url(data:font/truetype;charset=utf-8;base64,$titillium) format('truetype');
            font-weight: normal;
            font-style: normal;
           }

        @font-face {
            font-family: 'Montserrat';
            src: url(data:font/truetype;charset=utf-8;base64,$montserrat) format('truetype');
            font-weight: normal;
            font-style: normal;
           }           

        * {
            -webkit-box-sizing: border-box;
                    box-sizing: border-box;
          }
          
          body {
            padding: 0;
            margin: 0;
          }
          
          #notfound {
            position: relative;
            height: 100vh;
          }
          
          #notfound .notfound {
            position: absolute;
            left: 50%;
            top: 50%;
            -webkit-transform: translate(-50%, -50%);
                -ms-transform: translate(-50%, -50%);
                    transform: translate(-50%, -50%);
          }
          
          .notfound {
            max-width: 767px;
            width: 100%;
            line-height: 1.4;
            padding: 0px 15px;
          }
          
          .notfound .notfound-404 {
            position: relative;
            height: 150px;
            line-height: 150px;
            margin-bottom: 25px;
          }
          
          .notfound .notfound-404 h1 {
            font-family: 'Titillium Web', sans-serif;
            font-size: 186px;
            font-weight: 900;
            margin: 0px;
            text-transform: uppercase;
            color: #E9632A;
          }
          
          .notfound h2 {
            font-family: 'Titillium Web', sans-serif;
            font-size: 26px;
            font-weight: 700;
            margin: 0;
          }
          
          .notfound p {
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 0px;
            text-transform: uppercase;
          }
          
          .notfound a {
            font-family: 'Titillium Web', sans-serif;
            display: inline-block;
            text-transform: uppercase;
            color: #fff;
            text-decoration: none;
            border: none;
            background: #5c91fe;
            padding: 10px 40px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 1px;
            margin-top: 15px;
            -webkit-transition: 0.2s all;
            transition: 0.2s all;
          }
          
          .notfound a:hover {
            opacity: 0.8;
          }
          
          @media only screen and (max-width: 767px) {
            .notfound .notfound-404 {
              height: 110px;
              line-height: 110px;
            }
            .notfound .notfound-404 h1 {
              font-size: 120px;
            }
          }
        </style>

        </head>
        <body>
        <div id='notfound'>
        	<div class='notfound'>
        		<div class='notfound-404'>
        			<h1>{$this->code}</h1>
        		</div>
        		<h2>{{GENERAL_ERROR_TITLE}}</h2>
				<p>{$this->message}</p>
				<p>{$this->advise}</p>
				<p><a>{{WHERE_TO_GO}}</a></p>
        	</div>
        </div>
        </body>
        </html>";


		return $html;
	}

	public function makeDebugHtml()
	{ 

		$html_stack = $error_title = '';
		if($this->exception instanceof \Throwable){
			$error_title = $this->exception->getMessage();		
			if($this->exception instanceof Exception && $this->exception->getRewriteStack())
				$stack = $this->exception->getStackItems();
			else
				$stack = $this->exception->getTrace();
			/**
			 * @todo remove the globalFunctions.php line from stack
			 * if it's GlobalFunctions.php at line 129
			 */
			// get the first code that throw exception
			$f = $this->exception->getFile();
			$l = $this->exception->getLine(); 
			if($this->lineIsPublishable($f, $l)){
				$ctx = $this->extractContextCode($f, $l, $this->line_qty);
				$decorated_ctx = $this->decorateContext($ctx, $l, "<span class='line'>{{__LINE__}}</span><span class='code'>{{__CODE__}}</span><br/>", "<span class='selected-line'>{{__LINE__}}</span><span class='selected-code'>{{__CODE__}}</span><br/>");
				$html_stack = "<div class='stack-details'>
					<span><b>{$f} at line {$l}</b><br/>
					<pre>{$decorated_ctx}</pre></span></div>";
			}
			
			foreach($stack as $k=>$v){ 
				//$ctx = highlight_string(implode('\n', $v['context']), true); 
				//str_replace('&lt;?php&nbsp;', '', $ctx);
				$file = empty($v['file'])?'':$v['file'];
				$line = empty($v['line'])?0:$v['line'];

				if(!$this->lineIsPublishable($file, $line) || ($file == '' && $line == 0))
					continue;

				$ctx = $this->extractContextCode($file, $line, $this->line_qty);
				$decorated_ctx = $this->decorateContext($ctx, $line, "<span class='line'>{{__LINE__}}</span><span class='code'>{{__CODE__}}</span><br/>", "<span class='selected-line'>{{__LINE__}}</span><span class='selected-code'>{{__CODE__}}</span><br/>");
				$html_stack .= "<div class='stack-details'>
					<span><b>$file at line $line</b><br/>
					<pre>$decorated_ctx</pre></span></div>";
			}	
			
		}elseif($this->exception instanceof Response){
			$error_title = 'Error!';		
			$html_stack = "({$this->code}) {$this->message}";
		}elseif($this instanceof Response){
			$error_title = 'Error!';		
			$html_stack = "({$this->code}) {$this->message}";
		}
		
		$lobster = EmbedFonts::$lobster;
	
		$html = "<HTML>
		<HEAD>
			<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
			<meta http-equiv='X-UA-Compatible' content='IE=edge'>
			<meta name='viewport' content='width=device-width, initial-scale=1'>
			
			<title>Error! - Something happened processing your request..</title>
			<style>
				@font-face {
					font-family: 'Lobster';
					src: url(data:font/truetype;charset=utf-8;base64,$lobster) format('truetype');
					font-weight: normal;
					font-style: normal;
				}
		
				:root {
					--main-color: #3C3C3C;
					--light-orange: #ffbf00;
				}
		
				body {
					background-color: var(--main-color);
					padding: 0px;
					margin: 0px;
				}
				.title-container {
					background-color: var(--light-orange);
					height: auto;
					padding: 10px;
					padding-left: 25px;
					margin-left: auto;
					margin-right: auto;
				}
				.title {
					font-family: Verdana, Geneva, Tahoma, sans-serif;
					font-size: 20px;
					font-weight:500;
					color: slategray;
					line-height: 40px;
				}        
				.error-title {
					font-family: 'Lobster';
					font-size: 24px;
					font-weight:500;
					color: #ba0505;
					line-height: 40px;
				}
				.stack {
					border: 1px solid  var(--light-orange);
					margin-left: 25px;
					margin-right: 25px;
					margin-top: 10px;
					padding: 5px;
					font-family: monospace;
					color: whitesmoke;
				}
				.stack-details pre {
					margin-left: 25px;
					line-height: 15px; 
					background-color: gray;
					padding: 5px;
				}
		
				.stack-details pre .line {
					display: inline-block;
					margin-right: 0px;
					width: 40px;
					background-color: blue;
				}

				.stack-details pre .code {
					display: inline-block;
					background-color: darkblue;
				}

				.stack-details pre .selected-code {
					display: inline-block;
					background-color: yellow;
					color: darkblue;
				}

				.stack-details pre .selected-line {
					display: inline-block;
					margin-right: 1px;
					width: 40px;
					background-color: darkblue;
					font-weight: bold;
					color: yellow;
				}

				.stack-details span {
					line-height: 20px;
				}
		
				.stack-detail {
					
				}
			</style>
		</HEAD>
		
		<BODY>
			<div class='title-container'>
				<span class='error-title'>Error!</span><span class='title'>&nbsp;$error_title</span>
			</div>" .
			((trim($html_stack) !== '')?"<div class='stack'>
				$html_stack
			</div>":"") . 
		"</BODY>
		</HTML>";
		return $html;
	}

	/**
	 * Prepend items to the Exception stack to debug
	 *
	 * @param Array $stack_items
	 * @return void
	 */
	public function setPrependStackItems(Array $stack_items = [])
	{
		$this->prepend_stack_items = $stack_items;
		$this->force_stack = false;
		return $this;
	}

	/**
	 * Force the use of stack to the debug
	 *
	 * @param Array $stack_items
	 * @return void
	 */
	public function forceStackItems(Array $stack_items = [])
	{
		$this->prepend_stack_items = $stack_items;
		$this->force_stack = true;
		return $this;
	}

	public function render()
	{
        if( Config::get('debug', false) == 'true')
            $this->setContent(($this->is_json)?$this->makeDebugJson():$this->makeDebugHtml());
        else
            $this->setContent(($this->is_json)?$this->makeFriendlyJson():$this->makeFriendlyHtml());

        return $this->getContent();
	}

	private function extractContextCode($file, $line, $lines = null)
	{
		// extract from source file 
		$r = [];
		if(empty($lines) || !is_numeric($lines))
			$lines = $this->line_qty;

		$from = $line - floor($lines/2);
		if($from < 0)
			$from = 0;
		$until = $line + floor($lines/2);

		if(file_exists($file)){
			$h = fopen($file, 'r');
			$i = 1;
			while (($buffer = fgets($h, 4096)) !== false) {
				if($i >= $from && $i <= $until)
					//$r[$i] = $buffer;
					$r[$i] = htmlspecialchars((string)$buffer);
				$i++;
				if($i > $until)
					break;
			}
		}
		
		return $r;
	}

	private function decorateContext(Array $lines, $exception_line, $decoration, $exception_line_decoration = '')
	{
		$r = '';
		if(empty($exception_line_decoration))
			$exception_line_decoration = $decoration;

		foreach ($lines as $line=>$code)
		{			
			$t = str_replace('{{__LINE__}}', $line, ($line == $exception_line)?$exception_line_decoration:$decoration);
			$t = str_replace('{{__CODE__}}', $code, $t);
			$r .= $t;
		}
		return $r;
	}

	private function prepareJSONizableStack(Array $stack)
	{
		$ret = [];
		foreach($stack as $v)
		{
			if(empty($v['file']) && empty($v['class'])){
				// sometimes stack only comes with function call
				$ret[] = [
					'function' => $v['function'],
				];
			}elseif(empty($v['file'])){
				// sometimes stacks don't have file name, but class 
				$ret[] = [
					'class' => $v['class'],
					'method' => $v['function']
				];
			}else{
				if(!$this->lineIsPublishable($v['file'], $v['line']))
				continue;

				$ret[] = [
					'file' => $v['file'],
					'line' => $v['line']
				];
			}
		}
		return $ret;
	}

	private function lineIsPublishable($file, $line)
	{
		$unpublishable = [
			['\src\Helpers\GlobalFunctions.php', 124],
			['\src\Helpers\GlobalFunctions.php', 170],
			['\src\App.php', 191],
			['\src\Engine\Response.php', 125]
		];
		foreach($unpublishable as $unp)
		{
			$fc = substr($file, (strlen($unp[0]) * -1));
			$fc = str_replace('/', "\\", $fc);
			if(($fc == $unp[0]) && $line == $unp[1])
				return false;
		}
		return true;
	}

	/* S T A T I C   U T I L S  */ 
	public static function makePrependableStackItem($file, $line)
    {
        return ['file' => $file, 'line' => $line];
    }
}
