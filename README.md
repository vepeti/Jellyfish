# Jellyfish
PHP Template Engine

# Description
Jellyfish is a very simple and useful PHP template engine, based on PCRE substitutions. It works in the following PHP versions:
5.3, 5.4, 5.5, 5.6, 7.0 and above

# Structure
Jellyfish builds output from a dynamic PHP file and a static template file. With this structure, you can separate your site's frontend and backend pages.

# Usage
Just include the template.php. Then create a new template object. Only one parameter needed: the file of the static template file.

Example:
```
$page=new template("/path/of/template.file");
```

After it, add your variables with the set() function. The first parameter is the name, you can refer with this name in your static template file. The second parameter is the value. You can use custom strings, variables, arrays and multidimensional arrays.

Example:
```
# Add simple string to engine
$page->set("myvar", "XYZ");

# Add variable to engine
$x="testvar";
$page->set("myvar2", $x);

# Add array to engine
$myarr=array("abc", "def", "ghi", 12);
$page->set("myvar3", $myarr);

# Add multidimensional array to engine
$mymultiarr=array(
array(1, 2, 3, 4),
array("x", "y", "z")
);
$page->set("myvar4", $mymultiarr);
```
When you added all your variables, you must generate the output from the template file and your variables. Just call the output() method:
```
$page->output();
```

That's all!

# Template files
Jellyfish supports some of useful template methods, like inner functions, conditions or loops. To use this, create a simple html file.

## Use variables
From the previous example, we will use those template variables. You can refer to a variable with the [@varname] syntax.

Example:
```
...
<b>[@myvar]</>
...
```
The engine will generate the following output:
```
...
<b>XYZ</>
...
```

You can use variables everywhere in template files.

Example:
```
<ul>
<li>[@myvar]</li>
<li>[@myvar2]</li>
</ul>
```
This will generate the following output:
```
<ul>
<li>XYZ</li>
<li>testvar</li>
</ul>
```

## Use array elements
Same as simple variables, you can use array elements everywhere. Don't forget the index.

Example:
```
[@myvar3][0]<br />
[@myvar3][2]<br />
```
This will generate the following output:
```
abc<br />
ghi<br />
```

## Multidimensional arrays
This is the same as simple arrays, just print all of indexes.

Example:
```
<i>[@myvar4][0][2]</i>
<u>[@myvar4][1][1]</u>
```
This will generate the following output:
```
<i>3</i>
<u>y</u>
```

## Built in functions
Jellyfish supports some simple functions. You can call these in template file with a special string: {{ [@variable] | function}}
The engine is not sensitive to whitespaces.

Example:
```
<div>{{ [@myvar3][3] | sin }}</div>
```
This will generate the following output:
```
<div>-0.53657291800043</div>
```

Actually you can not use multiple functions on a variable. The following code DOESN'T work:
```
{{ [@myvar3][3] | sin | cos }}
```

## Supported functions
### Math functions
- sin - returns the sinus of variable
- cos - returns the cosinus of the variable
- tan - returns the tangens of the variable
- asin - returns the arcus sinus of the variable
- acos - returns the arcus cosinus of the variable
- atan - returns the arcus tangens of the variable
- log - returns the natural logarithm the variable
- floor - rounding down variable
- round - rounding normally variable
- ceil - rounding up variable
- abs - returns absoulute value of variable
- sqrt - returns the square root of variable
- log10 - returns base-10 logarithm of variable
- dechex - converts decimal value to hexadecimal
- hexdec - converts hexadecimal value to decimal
- decoct - converts decimal value to octal
- octdec - converst octal variable to decimal
- bindec - converts binary variable to decimal
- decbin - converts decimal variable to binary

Example:
```
{{[@variable]|round}} // returns rounded value
{{ [@variable] | sqrt }} // returns square root value
{{[@variable] | cos}} // returns cosinus value
```

### String functions
- uc - converts variable to fully uppercae
- lc - converts variable to fully lowercase
- ucf - converts variable's first letter to uppercase
- lcf - converts variable's first letter to lowercase
- shuffle - shuffles the letters in variable

Example:
```
{{[@variable]| uc}} // converts fully uppercase
{{ [@variable] | shuffle }} // shuffles characters randomly
```

### Hash functions
- md5 - returns the md5 hash of variable
- sha1 - returns the sha1 hash of variable
- sha256 - returns the sha256 hash of variable
- sha384 - returns the sha384 hash of variable
- sha512 - returns the sha512 hash of variable
- crc32 - returns the crc32 hash of variable

Example:
```
{{[@variable]| md5 }} // returns md5 value
{{[@variable] | sha256 }} // returns sha256 value
```


### Array functions
- count - returns the element count of array
- rand - returns a random element from array
- first - returns the first element from array
- last - returns the last element from array
- min - returns the lowest element from array
- max - returns the highest element from array
- join(char) - Joins all elements from array, separated with char parameter

Example:
```
{{ [@variable] | count }} // returns number of elements
{{ [@variable] | join(-) }} // returns all elements of array, separated with "-"
```
