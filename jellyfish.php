<?php

class template
{
protected $file;
protected $values = array();
protected $macros = array();

public function __construct($file)
{
    $this->file = $file;
}

public function set($key, $value)
{
    $this->values[$key] = $value;
}

public function output()
{
if (!file_exists($this->file))
{
    return "Error loading template file ($this->file).";
}
$output = file_get_contents($this->file);

// INCLUDE OTHER TEMPLATES
$output=$this->include_template($output);
	
// ADD MACRO
$output=$this->add_macro($output);

// CALL MACRO
$output=$this->call_macro($output);

// ADD VARS
$output=$this->add_vars($output);

// ADD ARRAYS
$output=$this->add_array($output);

// LOOP IN ARRAY
$output=$this->loop($output);

// CONDITION
$output=$this->condition($output);

// FILTERS
$output=$this->filters($output);

return $output;
}

private function condition($string)
{
$string=preg_replace_callback("/\{\{ ?if (\(.+\)) ?}}(.+)\{\{ ?endif ?}}/sU", function ($found)
{
    $condstring=$this->filters($found[1]);
    $cond=eval("return $condstring;");
    if ($cond)
    {
        return $found[2];
    }
    else
    {
        return NULL;
    }
}, $string);
return $string;
}

private function loop($string)
{
$string=preg_replace_callback("/\{\{ ?for ((\w+)=>)?(\w+) in \[@(.+)] ?}}(.+)\{\{ ?endfor ?}}/sU", function($found)
{
    $row=NULL;
    if (!is_array($this->values[$found[4]]))
    {
        $myarray=str_split($this->values[$found[4]]);
    }
    else
    {
        $myarray=$this->values[$found[4]];
    }
    foreach($myarray as $key=>$element)
    {
        if (preg_match("/\[@".$found[3]."](\[.+])/", $found[5]))
        {
            $index=preg_replace_callback("/^.*\[@".$found[3]."](\[.+]).*$/sU", function($dim){return $dim[1];}, $found[5]);
            $str='return $element'.$index.';';
            $var=eval($str);
            $string=preg_replace("/\[@".$found[3]."](\[.+])/U", $var, $found[5]);
            $string=preg_replace("/\[@".$found[2]."]/U", $key, $string);
            $row.=$string;
        }
        else
        {
            $string=preg_replace("/\[@".$found[3]."]/", $element, $found[5]);
            $string=preg_replace("/\[@".$found[2]."]/", $key, $string);
            $row.=$string;
        }
    }
$row=$this->condition($row);
return $row;
}, $string);
return $string;
}

private function filters($string)
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
|count|rand|first|last|min|max|join\((.*)\)|
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
        }
    }
    else
    {
        return "Not an Array";
    }
}, $string);

// VARIABLE AND ARRAY SUBSTITUTIONS
$string=$this->main_subst($string);

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
{return eval('return $this->values[$found[1]]'."$found[2];");}, $string);

$string=preg_replace_callback("/\[@(.+)]\[(.+)]/U", function($found)
{return $this->values[$found[1]][$found[2]];}, $string);

$string=preg_replace_callback("/\[@(.+)]/U", function($found)
{return $this->values[$found[1]];}, $string);
return $string;
}

private function add_vars($string)
{
$string=preg_replace_callback("/\{\{ ?var (.+?)=(.+?) ?}}/", function($found)
{
    $names=preg_replace("/\s/", "", $found[1]);
    $names=explode(",", $names);

    $values=preg_replace("/\s/", "", $found[2]);
    $values=explode(",", $values);

    foreach($names as $key=>$value)
    {
        $this->set($value, $values[$key]);
    }

return NULL;}, $string);
return $string;
}

private function add_array($string)
{
$string=preg_replace_callback("/\{\{ ?array (\w+?)= ?\[(.+?)\] ?}}/", function($found)
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
    $this->set($found[1], $arrayname);
return NULL;}, $string);
return $string;
}

private function include_template($string)
{
$string=preg_replace_callback("/\{\{ ?include '(.+?)' ?}}/", function($found)
{
    $newfile=NULL;
    if (file_exists($found[1]))
    {
        $newfile=file_get_contents($found[1]);
    }
return $newfile;}, $string);
return $string;
}

private function add_macro($string)
{
$string=preg_replace_callback("/\{\{ ?macro (\w+?)\((.+?)\) ?}}(.+){{ ?endmacro ?}}/s", function($found)
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
    $temparray=preg_replace("/\s/", "", $found[2]);
    $temparray=explode(",", $temparray);

    $inserted=$this->macros[$found[1]]->call($found[1], $temparray);
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

$this->body=preg_replace_callback("/\[@(.+?)]/", function($found)
{
    return $this->values[$found[1]];}, $this->body);
return $this->body;
}

}

?>
