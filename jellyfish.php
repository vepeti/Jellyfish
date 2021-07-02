<?php

class template
{
    protected $file;
    protected $looparray = array();
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
        {
            $this->values[$key] = $value;
        }
        elseif ($global==1)
        {
            self::$global_values[$key] = $value;
        }
        else
        {
            return 0;
        }
    }

    public function output()
    {
        if (!file_exists($this->file))
        {
            return "Error loading template file ($this->file).";
        }

        $output = file_get_contents($this->file);

        // COMMENT
        $output=$this->comment($output);

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

        while (preg_match("/\{% if \(.+?\) ?%}/", $string))
        {
            $lines=explode("\n", $string);

            foreach ($lines as $x)
            {
                if (preg_match("/\{% ?if \(.+?\) ?%}/", $x))
                {
                    $innerif=$x;
                }

                if (preg_match("/\{% ?endif ?%}/", $x))
                {
                    break;
                }
            }

            $innerblock=preg_replace_callback("~.*(\Q".$innerif."\E.+?\{% ?endif ?%}).*~s", function ($found)
            {
                return $found[1];
            }, $string);

            $processedblock=$this->condition($innerblock);
            $innerblock=preg_quote($innerblock);
            $string=preg_replace("|".$innerblock."|s", $processedblock, $string);
        }

        return $string;
    }

    // CONDITION
    private function condition($string)
    {
        $string=preg_replace_callback("/(\{% ?if ?(\(.+?\)) ?%}(.+?)\{% ?endif ?%})/s", function ($found)
        {
            if (preg_match("/\{% ?if \(.+?\) ?%}/s",$found[3]))
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
                if (preg_match("/\{% ?else/", $found[3]))
                {
                    $truestring=preg_replace_callback("/^(.*?)\{% ?else.*$/s", function ($innerstring)
                    {
                        return $innerstring[1];
                    }, $found[3]);
                return $truestring;
                }
                else
                {
                    return $found[3];
                }
            }
            else
            {
                if (preg_match("/\{% ?elseif ?\(.+?\) ?%}/s", $found[3]))
                {
                    preg_match_all("/\{% ?elseif ?\(.+?\) ?%}/s", $found[3], $matches);
                    $matches=$matches[0];

                    foreach ($matches as $block)
                    {
                        $condstring=preg_replace_callback("/^.+(\(.+\)).+$/", function ($innerfound)
                        {
                            return $innerfound[1];
                        }, $block);

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
                            $truestring=preg_replace_callback("/^.*\Q".$block."\E(.+?)(\{% ?else.*)?$/s", function ($innerstring)
                            {
                                return $innerstring[1];
                            }, $found[3]);
                            return $truestring;
                        }
                    }
                }
                if (preg_match("/\{% ?else ?%}/", $found[3]))
                {
                    $falsestring=preg_replace_callback("/^.*\{% ?else ?%}(.*)$/s", function ($innerstring)
                    {
                        return $innerstring[1];
                    }, $found[3]);
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
        $string=preg_replace_callback("/(\{% ?for ((\w+?)=>)?(\w+?) in (\{\{ ?.+? ?}}) ?%}(.+?)\{% ?endfor ?%})/s", function($found)
        {
            if (preg_match("/\{% for (\w+?=>)?\w+ in \{\{ ?\w+? ?}} ?%}/s",$found[6]))
            {
                return $found[1];
            }

            $row=NULL;

            $myarray=$this->filters($found[5]);

            if ($myarray!="Array")
            {
                $myarray=str_split($this->values[$found[5]]);
            }
            else
            {
                $myarray=$this->looparray;
            }

            if ((count($myarray)==1) && (isset($myarray[0])) && ($myarray[0]==""))
            {
                return NULL;
            }

            if (preg_match("/\{\{ ?".$found[4]."(\[.+]) ?}}/", $found[6]))
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
                if (preg_match("/\{\{ ?".$found[4]."(\[.+]) ?}}/", $found[6]))
                {
                    $innerblock=$found[6];
                    while (preg_match("/\{\{ ?".$found[4]."(\[.+]) ?}}/", $innerblock))
                    {
                        $innerblock=preg_replace_callback("/\{\{ ?".$found[4]."(\[.+]) ?}}/sU", function($dim) use($element)
                        {
                            return eval('return $element'.$dim[1].';');
                        }, $innerblock, 1);
                    }
                    $row.=$innerblock;
                }
                else
                {
                    $string=preg_replace_callback("/\{\{ ?(".$found[4].")(.+?) ?}}/", function($filters) use($element)
                    {
                        return "{{ ".$element.$filters[2]." }}";
                    }, $found[6]);

                    if (!empty($found[3]))
                    {
                        $string=preg_replace_callback("/\{\{ ?(".$found[3].")(.+?) ?}}/", function($filters) use($key)
                        {
                        return "{{ ".$key.$filters[2]." }}";
                        }, $string);
                    }

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

        while (preg_match("/\{% for (\w+?=>)?\w+ in \{\{ ?.+? ?}} ?%}/", $string))
        {
            $lines=explode("\n", $string);

            foreach ($lines as $x)
            {
                if (preg_match("/\{% for (\w+?=>)?\w+ in \{\{.+? ?}} ?%}/", $x))
                {
                    $innerfor=$x;
                }

                if (preg_match("/\{% ?endfor ?%}/", $x))
                {
                    break;
                }
            }

            $innerblock=preg_replace_callback("~.*(\Q".$innerfor."\E.+?\{ ?endfor ?%}).*~s", function ($found)
            {
                return $found[1];
            }, $string);

            $processedblock=$this->loop($innerblock);
            $innerblock=preg_quote($innerblock);
            $string=preg_replace("|".$innerblock."|s", $processedblock, $string);
        }

        return $string;
    }

    // NEW FILTERS
    private function filters($string,$cond=false)
    {
        $string=preg_replace_callback("/\{\{ ?(.+?) ?}}/", function($found)
        {
            if (preg_match("/\|/", $found[1]))
            {
                $filterarray=explode("|", $found[1]);

                $mainvar=trim(array_shift($filterarray));

                if (preg_match("/\[.+?]/", $mainvar))
                {
                    $returnvar=preg_replace_callback("/^(.+?)(\[.+])$/", function($found2)
                    {
                        return @eval('return $this->values["'.$found2[1].'"]'.$found2[2].';');
                    }, $mainvar);

                if (empty($returnvar))
                {
                    $default=true;
                }
            }
            else
            {
                if (isset($this->values[$mainvar]))
                {
                    $returnvar=$this->values[$mainvar];
                }
                elseif (isset(self::$global_values[trim($mainvar)]))
                {
                    $returnvar=self::$global_values[trim($mainvar)];
                }
                else
                {
                    $returnvar=$mainvar;
                    $default=true;
                }
            }

            foreach ($filterarray as $filter)
            {
                $filter=trim($filter);

                if (is_array($returnvar))
                {
                    switch ($filter)
                    {
                        case "count":
                            $returnvar=count($returnvar);
                            break;
                        case "rand":
                            $returnvar=$returnvar[array_rand($returnvar)];
                            break;
                        case "first":
                            $returnvar=reset($returnvar);
                            break;
                        case "last":
                            $returnvar=end($returnvar);
                            break;
                        case "min":
                            $returnvar=min($returnvar);
                            break;
                        case "max":
                            $returnvar=end($returnvar);
                            break;
                        case "sort":
                            sort($returnvar);
                            break;
                        case "ksort":
                            ksort($returnvar);
                            break;
                        case "krsort":
                            krsort($returnvar);
                            break;
                        case "reverse":
                            rsort($returnvar);
                            break;
                        case "shuffle":
                            shuffle($returnvar);
                            break;
                        case "unique":
                            $returnvar=array_unique($returnvar);
                            break;
                        case (preg_match("/^join ?\(.*?\)$/", $filter) ? true : false):
                            $returnvar=preg_replace_callback("/^join ?\((.*?)\)$/", function($found2) use($returnvar)
                            {
                                return implode($found2[1], $returnvar);
                            }, $filter);
                            break;
                        case (preg_match("/^search ?\(.*?\)$/", $filter) ? true : false):
                            $returnvar=preg_replace_callback("/^search ?\((.*?)\)$/", function($found2) use($returnvar)
                            {
                                return in_array($found2[1], $returnvar);
                            }, $filter);
                            break;
                        case (preg_match("/^keysearch ?\(.*?\)$/", $filter) ? true : false):
                            $returnvar=preg_replace_callback("/^keysearch ?\((.*?)\)$/", function($found2) use($returnvar)
                            {
                                return array_key_exists($found2[1], $returnvar);
                            }, $filter);
                            break;
                    }
                }
                elseif (is_numeric($returnvar))
                {
                    switch ($filter)
                    {
                        case (preg_match("/^(sin|cos|tan|asin|acos|atan|log|
                        ceil|floor|round|abs|sqrt|log10|dechex|hexdec|
                        decoct|octdec|bindec|decbin)$/", $filter) ? true : false):
                            $returnvar=@eval("return $filter($returnvar);");
                            break;
                    }
                }
                elseif (is_string($returnvar))
                {
                    switch ($filter)
                    {
                        case "uc":
                            $returnvar=strtoupper($returnvar);
                            break;
                        case "ucf":
                            $returnvar=ucfirst($returnvar);
                            break;
                        case "lc":
                            $returnvar=strtolower($returnvar);
                            break;
                        case "lcf":
                            $returnvar=lcfirst($returnvar);
                            break;
                        case (preg_match("/^repeat ?\(\d+?\)$/", $filter) ? true : false):
                            $returnvar=preg_replace_callback("/^repeat ?\((\d+?)\)$/", function($found2) use($returnvar)
                            {
                                return str_repeat($returnvar, $found2[1]);
                            }, $filter);
                            break;
                        case "shuffle":
                            $returnvar=str_shuffle($returnvar);
                            break;
                        case "length":
                            $returnvar=strlen($returnvar);
                            break;
                        case (preg_match("/^search ?\(.*?\)$/", $filter) ? true : false):
                            $returnvar=preg_replace_callback("/^search ?\((.*?)\)$/", function($found2) use($returnvar)
                            {
                                return strpos($returnvar, $found2[1]);
                            }, $filter);
                            break;
                    }
                }

                switch ($filter)
                {
                    case "md5":
                        $returnvar=md5($returnvar);
                        break;
                    case "sha1":
                        $returnvar=sha1($returnvar);
                        break;
                    case "sha256":
                        $returnvar=sha256($returnvar);
                        break;
                    case "sha384":
                        $returnvar=sha384($returnvar);
                        break;
                    case "sha512":
                        $returnvar=sha512($returnvar);
                        break;
                    case "crc32":
                        $returnvar=crc32($returnvar);
                        break;
                    case (preg_match("/^default ?\(.*?\)$/", $filter) ? true : false):
                        if (isset($default))
                        {
                            $returnvar=preg_replace_callback("/^default ?\((.*?)\)$/", function($found2)
                            {
                                return $found2[1];
                            }, $filter);
                        }
                        break;
                    case "isset":
                        if (isset($default))
                        {
                            $returnvar=0;
                        }
                        else
                        {
                            $returnvar=1;
                        }
                }
            }
        }
        else
        {
            if (preg_match("/\[.+?]/", trim($found[1])))
            {
                $returnvar=preg_replace_callback("/^(.+?)(\[.+])$/", function($found2)
                {
                    return @eval('return $this->values["'.$found2[1].'"]'.$found2[2].';');
                }, $found[1]);
            }
            else
            {
                if (isset(self::$global_values[trim($found[1])]))
                {
                    $returnvar=self::$global_values[trim($found[1])];
                }
                elseif (isset($this->values[trim($found[1])]))
                {
                    $returnvar=$this->values[trim($found[1])];
                }
                else
                {
                    $returnvar=trim($found[1]);
                }
            }
        }

        if (is_array($returnvar))
        {
            $this->looparray=NULL;
            $this->looparray=$returnvar;
            $returnvar="Array";
        }

        return $returnvar;
        }, $string);

        return $string;
    }

    // ADD VARS
    private function add_vars($string)
    {
        $string=preg_replace_callback("/\{% ?(global)? var (.+?)=(.+?) ?%}/", function($found)
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

            return NULL;
        }, $string);

        return $string;
    }

    // ADD ARRAY
    private function add_array($string)
    {
        $string=preg_replace_callback("/\{% ?(global)? array (\w+?)= ?\[(.+?)\] ?%}/", function($found)
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

            return NULL;
        }, $string);
        return $string;
    }

    private function include_template($string)
    {
        $string=preg_replace_callback("/\{% ?include '(.+?)' ?%}/", function($found)
        {
            // substitue variables
            $found[1]=$this->filters($found[1]);
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

        if (preg_match("/\{% ?include '(.+?)' ?%}/", $string))
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
        $string=preg_replace_callback("/\{% ?macro (\w+?)\((.+?)\) ?%}(.+?){% ?endmacro ?%}/s", function($found)
        {
            $temparray=preg_replace("/\s/", "", $found[2]);
            $temparray=explode(",", $temparray);

            $macro=new macro($found[1], $temparray, $found[3]);
            $this->macros[$found[1]]=$macro;
            return NULL;
        }, $string);
        return $string;
    }

    private function call_macro($string)
    {
        $string=preg_replace_callback("/\{% ?call (\w+?)\((.+?)\) ?%}/", function($found)
        {
            $temparray=preg_replace("/,\s*?/", ",", $found[2]);
            $temparray=explode(",", $temparray);
            $inserted=isset($this->macros[$found[1]]) ? $this->macros[$found[1]]->call($found[1], $temparray) : NULL;
            return $inserted;
        }, $string);
        return $string;
    }

    private function comment($string)
    {
        $string=preg_replace("/\{#.*#}/s", NULL, $string);
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

        $return_string=preg_replace_callback("/\{\{ ?(.+?) ?}}/", function($found)
        {
            return $this->values[$found[1]];
        }, $this->body);
        return $return_string;
    }

}

?>
