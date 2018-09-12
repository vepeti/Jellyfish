<?php

class template
{
protected $file;
protected $values = array();
protected $macros = array();
protected static $global_values = array();

public function __construct($file)
{
    $this->file = $file;
}

public function set($key, $value, $global=0)
{
    if ($global==0)
        $this->values[$key] = $value;
    elseif ($global==1)
        self::$global_values[$key] = $value;
    else return 0;
}

public function output()
{
if (!file_exists($this->file))
{
    return "Error loading template file ($this->file).";
}
$output = file_get_contents($this->file);

// ADD VARS
$output=$this->add_vars($output);

// ADD ARRAYS
$output=$this->add_array($output);

// INCLUDE OTHER TEMPLATES
$output=$this->include_template($output);

// ADD MACRO
$output=$this->add_macro($output);

// CALL MACRO
$output=$this->call_macro($output);

// LOOP IN ARRAY
$output=$this->loop($output);

// NESETED LOOP
$output=$this->nested_loop($output);

// CONDITION
$output=$this->condition($output);

// NESTED CONDITION
$output=$this->nested_condition($output);

// FILTERS
$output=$this->filters($output);

return $output;
}

// NESTED CONDITION
private function nested_condition($string)
{
$innerif=NULL;
$innerblock=NULL;

while (preg_match("/\{\{ if \(.+?\) ?}}/", $string))
{
$lines=explode("\n", $string);
foreach ($lines as $x)
{
    if (preg_match("/\{\{ ?if \(.+?\) ?}}/", $x))
    {
        $innerif=$x;
    }

    if (preg_match("/\{\{ ?endif ?}}/", $x))
    {
        break;
    }
}

$innerblock=preg_replace_callback("~.*(\Q".$innerif."\E.+?\{\{ ?endif ?}}).*~s", function ($found)
    {return $found[1];}, $string);

$processedblock=$this->condition($innerblock);
$innerblock=preg_quote($innerblock);
$string=preg_replace("|".$innerblock."|s", $processedblock, $string);
}

return $string;
}

// CONDITION
private function condition($string)
{
$string=preg_replace_callback("/(\{\{ ?if ?(\(.+?\)) ?}}(.+?)\{\{ ?endif ?}})/s", function ($found)
{
    if (preg_match("/\{\{ ?if \(.+?\) ?}}/s",$found[3]))
    {
        return $found[1];
    }

    $condstring=$this->filters($found[2], true);

    try
    {
        $cond=eval("return $condstring;");
    }
    catch (ParseError $e)
    {
        $cond=0;
    }

    if ($cond)
    {
        if (preg_match("/\{\{ ?else/", $found[3]))
        {
            $truestring=preg_replace_callback("/^(.*?)\{\{ ?else.*$/s", function ($innerstring)
                {return $innerstring[1];}, $found[3]);
            return $truestring;
        }
        else
        {
            return $found[3];
        }
    }
    else
    {
        if (preg_match("/\{\{ ?elseif ?\(.+?\) ?}}/s", $found[3]))
        {
            preg_match_all("/\{\{ ?elseif ?\(.+?\) ?}}/s", $found[3], $matches);
            $matches=$matches[0];
            foreach ($matches as $block)
            {
                $condstring=preg_replace_callback("/^.+(\(.+\)).+$/", function ($innerfound)
                    {return $innerfound[1];}, $block);
                $condstring=$this->filters($condstring);

                try
                {
                    $cond=eval("return $condstring;");
                }
                catch (ParseError $e)
                {
                    $cond=0;
                }

                if ($cond)
                {
                    $truestring=preg_replace_callback("/^.*\Q".$block."\E(.+?)(\{\{ ?else.*)?$/s", function ($innerstring)
                        {return $innerstring[1];}, $found[3]);
                    return $truestring;
                }
            }
        }
        if (preg_match("/\{\{ ?else ?}}/", $found[3]))
        {
            $falsestring=preg_replace_callback("/^.*\{\{ ?else ?}}(.*)$/s", function ($innerstring)
                {return $innerstring[1];}, $found[3]);
            return $falsestring;
        }
        else return NULL;
    }
}, $string);
return $string;
}

// LOOP
private function loop($string)
{
$string=preg_replace_callback("/(\{\{ ?for ((\w+)=>)?(\w+) in \[@(.+)] ?}}(.+)\{\{ ?endfor ?}})/sU", function($found)
{
    if (preg_match("/\{\{ for (\w+?=>)?\w+ in \[@\w+\] ?}}/s",$found[6]))
    {
        return $found[1];
    }

    $row=NULL;
    if (!is_array($this->values[$found[5]]))
    {
        $myarray=str_split($this->values[$found[5]]);
    }
    else
    {
        $myarray=$this->values[$found[5]];
    }

    if ((count($myarray)==1) && (isset($myarray[0])) && ($myarray[0]==""))
    {
        return NULL;
    }

    if (preg_match("/\[@".$found[4]."](\[.+])/", $found[6]))
    {
        if ((!isset($myarray[0])) || (!is_array($myarray[0])))
        {
            $newarray[0]=$myarray;
            array_push($newarray,NULL);
            $myarray=$newarray;
            array_pop($myarray);
        }
    }

    foreach($myarray as $key=>$element)
    {
        if (preg_match("/\[@".$found[4]."](\[.+])/", $found[6]))
        {
            $innerblock=$found[6];
            while (preg_match("/\[@".$found[4]."](\[.+])/", $innerblock))
            {
                $innerblock=preg_replace_callback("/\[@".$found[4]."](\[.+])/sU", function($dim) use($element)
                {
                    return eval('return $element'.$dim[1].';');
                }, $innerblock, 1);
            }
        $row.=$innerblock;
        }
        else
        {
            $string=preg_replace("/\[@".$found[4]."]/", $element, $found[6]);
            $string=preg_replace("/\[@".$found[3]."]/", $key, $string);
            $row.=$string;
        }
    }
$row=$this->condition($row);
return $row;
}, $string);
return $string;
}

// NESTED LOOP
private function nested_loop($string)
{
$innerfor=NULL;
$innerblock=NULL;
$counter=0;

while (preg_match("/\{\{ for (\w+?=>)?\w+ in \[@\w+\] ?}}/", $string))
{
$lines=explode("\n", $string);

foreach ($lines as $x)
{
    if (preg_match("/\{\{ for (\w+?=>)?\w+ in \[@\w+\] ?}}/", $x))
    {
        $innerfor=$x;
    }

    if (preg_match("/\{\{ ?endfor ?}}/", $x))
    {
        break;
    }
}

$innerblock=preg_replace_callback("~.*(\Q".$innerfor."\E.+?\{\{ ?endfor ?}}).*~s", function ($found)
    {return $found[1];}, $string);

$processedblock=$this->loop($innerblock);
$innerblock=preg_quote($innerblock);
$string=preg_replace("|".$innerblock."|s", $processedblock, $string);

$counter++;
if ($counter>100) break;
}

return $string;
}

private function filters($string,$cond=false)
{
// DEFAULT STRING SUBSTITUTION
$string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?default\((.*)\) ?}}/U", function($found)
{
    if (isset($this->values[$found[1]]))
    {
        return $this->values[$found[1]];
    }
    else
    {
        return $found[2];
    }
}, $string);

// ARRAY FUNCTIONS
$string=preg_replace_callback("/\{\{ ?\[@(.+?)] ?\| ?(
|count|rand|first|last|min|max|join\((.*)\)|contains\((.*)\)|
) ?}}/U", function($found)
{
    if (is_array($this->values[$found[1]]))
    {
        switch ($found[2])
        {
            case "count":
                return count($this->values[$found[1]]);
                break;
            case "rand":
                return array_rand($this->values[$found[1]]);
                break;
            case "first":
                return reset($this->values[$found[1]]);
                break;
            case "last":
                return end($this->values[$found[1]]);
                break;
            case "min":
                return min($this->values[$found[1]]);
                break;
            case "max":
                return end($this->values[$found[1]]);
                break;
            case "join($found[3])":
                return implode($found[3], $this->values[$found[1]]);
                break;
            case "contains($found[4])":
                return in_array($found[4], $this->values[$found[1]]);
                break;
        }
    }
    else
    {
        return "Variable $found[1] is Not an Array!";
    }
}, $string);

// VARIABLE AND ARRAY SUBSTITUTIONS
if ($cond)
{
    $string=$this->cond_subst($string);
}
else
{
    $string=$this->main_subst($string);
}

// STRING FUNCTIONS
if (preg_match("/\{\{.+?}}/", $string))
{
    $string=preg_replace_callback("/\{\{ ?(.+) ?\| ?uc ?}}/U", function($found)
    {return strtoupper($found[1]);}, $string);

    $string=preg_replace_callback("/\{\{ ?(.+) ?\| ?ucf ?}}/U", function($found)
    {return ucfirst($found[1]);}, $string);

    $string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?lcf ?}}/", function($found)
    {return lcfirst($found[1]);}, $string);

    $string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?lc ?}}/", function($found)
    {return strtolower($found[1]);}, $string);

    // MATH FUNCTIONS
    $string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?(
    |sin|cos|tan|asin|acos|atan|log|ceil|floor|round|abs|sqrt|log10|dechex|hexdec|decoct|octdec|bindec|decbin|shuffle|
    ) ?}}/", function($found)
    {return @eval("return $found[2]($found[1]);") ? @eval("return $found[2]($found[1]);") : "$found[1] is Not valid Number";}, $string);

    // HASH FUNCTIONS
    $string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?(
    |md5|sha1|sha256|sha384|sha512|crc32|
    ) ?}}/", function($found)
    {return @eval("return hash($found[2],$found[1]);") ? @eval("return hash($found[2],$found[1]);") : "$found[1] is Not valid Number";}, $string);
}
return $string;
}

private function main_subst($string)
{

$string=preg_replace_callback("/\[@(.+)]((\[.+]){2,})/", function($found)
{
    $localvar=eval('return $this->values[$found[1]]'."$found[2];");
    $globalvar=eval('return self::$global_values[$found[1]]'."$found[2];");
    if (isset($localvar))
    {
        return eval('return $this->values[$found[1]]'."$found[2];");
    }
    elseif (isset($globalvar))
    {
        return eval('return self::$global_values[$found[1]]'."$found[2];");
    }
    else return NULL;
}, $string);

$string=preg_replace_callback("/\[@(.+)]\[(.+)]/U", function($found)
{
    if (isset($this->values[$found[1]][$found[2]]))
    {
        return $this->values[$found[1]][$found[2]];
    }
    elseif (isset(self::$global_values[$found[1]][$found[2]]))
    {
        return self::$global_values[$found[1]][$found[2]];
    }
    else return NULL;
}, $string);

$string=preg_replace_callback("/\[@(.+)]/U", function($found)
{
    if (isset($this->values[$found[1]]))
    {
        return $this->values[$found[1]];
    }
    elseif (isset(self::$global_values[$found[1]]))
    {
        return self::$global_values[$found[1]];
    }
    else return NULL;
}, $string);
return $string;
}

private function cond_subst($string)
{

$string=preg_replace_callback("/\[@(.+)]((\[.+]){2,})/", function($found)
{
    $localvar=eval('return $this->values[$found[1]]'."$found[2];");
    $globalvar=eval('return self::$global_values[$found[1]]'."$found[2];");
    if (isset($localvar))
    {
        return eval('return $this->values[$found[1]]'."$found[2];");
    }
    elseif (isset($globalvar))
    {
        return eval('return self::$global_values[$found[1]]'."$found[2];");
    }
    else return NULL;
}, $string);

$string=preg_replace_callback("/\[@(.+)]\[(.+)]/U", function($found)
{
    if (isset($this->values[$found[1]][$found[2]]))
    {
        return '$this->values['.$found[1].']['.$found[2].']';
    }
    elseif (isset(self::$global_values[$found[1]][$found[2]]))
    {
        return 'self::$global_values['.$found[1].']['.$found[2].']';
    }
    else return NULL;
}, $string);

$string=preg_replace_callback("/\[@(.+)]/U", function($found)
{
    if (isset($this->values[$found[1]]))
    {
        return '$this->values["'.$found[1].'"]';
    }
    elseif (isset(self::$global_values[$found[1]]))
    {
        return 'self::$global_values["'.$found[1].'"]';
    }
    else return NULL;
}, $string);

return $string;
}

private function add_vars($string)
{
$string=preg_replace_callback("/\{\{ ?(global)? var (.+?)=(.+?) ?}}/", function($found)
{
    $names=preg_replace("/\s/", "", $found[2]);
    $names=explode(",", $names);

    $values=preg_replace("/\s/", "", $found[3]);
    $values=explode(",", $values);

    foreach($names as $key=>$value)
    {
        if (empty($found[1]))
        {
            $this->set($value, $values[$key]);
        }
        else
        {
            $this->set($value, $values[$key], 1);
        }
    }

return NULL;}, $string);

return $string;
}

private function add_array($string)
{
$string=preg_replace_callback("/\{\{ ?(global)? array (\w+?)= ?\[(.+?)\] ?}}/", function($found)
{
    $arrayname=preg_replace("/\s/", "", $found[1]);
    $arrayname=explode(",", $arrayname);
    $arrayname=array();

    $values=preg_replace("/\s/", "", $found[3]);
    $values=explode(",", $values);

    foreach($values as $value)
    {
        array_push($arrayname, $value);
    }

    if (empty($found))
    {
        $this->set($found[1], $arrayname);
    }
    else
    {
        $this->set($found[1], $arrayname);
    }

return NULL;}, $string);
return $string;
}

private function include_template($string)
{
$string=preg_replace_callback("/\{\{ ?include '(.+?)' ?}}/", function($found)
{
    // substitue variables
    $found[1]=$this->main_subst($found[1]);
    $newfile=NULL;
    if (file_exists($found[1]))
    {
        if (pathinfo($found[1], PATHINFO_EXTENSION)=="tpl")
        {
            $newfile=file_get_contents($found[1]);
        }
        else
        {
            $newfile=include $found[1];
        }
    }
return $newfile;}, $string);

if (preg_match("/\{\{ ?include '(.+?)' ?}}/", $string))
{
$string=$this->include_template($string);
}

// ADD VARS
$string=$this->add_vars($string);

// ADD ARRAYS
$string=$this->add_array($string);


return $string;
}

private function add_macro($string)
{
$string=preg_replace_callback("/\{\{ ?macro (\w+?)\((.+?)\) ?}}(.+?){{ ?endmacro ?}}/s", function($found)
{
    $temparray=preg_replace("/\s/", "", $found[2]);
    $temparray=explode(",", $temparray);

    $macro=new macro($found[1], $temparray, $found[3]);
    $this->macros[$found[1]]=$macro;
return NULL;}, $string);
return $string;
}

private function call_macro($string)
{
$string=preg_replace_callback("/\{\{ ?call (\w+?)\((.+?)\) ?}}/", function($found)
{
    $temparray=preg_replace("/,\s*?/", ",", $found[2]);
    $temparray=explode(",", $temparray);
    $inserted=isset($this->macros[$found[1]]) ? $this->macros[$found[1]]->call($found[1], $temparray) : NULL;
return $inserted;}, $string);
return $string;
}

}

class macro
{
private $name;
private $properties = array();
private $values = array();
private $body;

public function __construct($name, $properties=array(), $body)
{
$this->name=$name;

foreach($properties as $value)
{
    array_push($this->properties, $value);
}
$this->body=$body;
}

public function call($name, $property_values=array())
{

foreach ($this->properties as $key=>$propname)
{
    $this->values[$propname]=$property_values[$key];
}

$return_string=preg_replace_callback("/\[@(.+?)]/", function($found)
{
    return $this->values[$found[1]];}, $this->body);
return $return_string;
}

}

?>
