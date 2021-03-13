<?php 

namespace Janssen\Helpers;

// take a string and transform into regex query
class Regexer
{
    public static function fullTransform($text = '')
    {
        $a = self::transform($text);
        return "/^$a$/";
    }

    public static function transform($text = '')
    {
        if(empty($text))
            return '';
            
        $look_for = '/[\/\?\=\+\.\*]/';
        $a = preg_replace($look_for, ("\\\\$0"), $text);
        return $a;
    }

/**
     * Checks if given string is a regular expression
     * @see https://gist.github.com/smichaelsen/717fae9055ae83ed8e15
     *
     * @param string $string
     * @return boolean
     */
    public static function isRegex($text) 
    {
        set_error_handler(function(){}, E_WARNING);
        $ir = (@preg_match($text, '') !== FALSE);
        restore_error_handler();
        return $ir;
    }
}