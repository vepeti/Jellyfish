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

## Variables
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
There is possible to define variables in template file too. You can add single or multiple variables in a block.
First these blocks run, so you can use the added variables before the declaration.
These blocks are not sensitive to whitespaces.

Example 1:
```
{{ var x = 15 }}
```
Now you can call [@x] variable everywhere

Example 2:
```
{{ var a,b,c=10,test,test2 }}
```
Now you can use all of created variables in template.

## Array elements
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

There is possible to define simple arrays in template file. First these blocks run, so you can use the added arrays before the declaration.
These blocks are not sensitive to whitespaces.

Example:
```
{{ array test=[10,35,text1,74] }}
```
Now you can use [@test] array.

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
The function blocks are not sensitive to whitespaces.

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
### Other functions
- default - set default value to a variable, if it doesn't exist

Example:
```
{{ [@variable] | default(abcde) }}
```

## Conditions
Jellyfish supports basic conditional blocks in template. If the condition's final value is true, the content between the {{ if }} and {{ endif }} will be printed to the output. Else print nothing. Optionally you can use else statement. In this case, the string between {{ else }} and {{ endif }} will be printed. In the condition section, the engine uses the PHP's eval() function, so you can use simple tests, like (10>2), or built-in PHP functions. Of course, you can test your template variables and array elements too. The conditional blocks have a special synthax. See below:
The condition blocks are not sensitive to whitespaces.
Note: The nested conditions actually doesn't work.

Example 1:
```
{{ if (10>5) }}
<u><b>It's true</u></b>
{{ endif }}

```
Because the test's value is true, this will generate the following output:
```
<u><b>It's true</u></b>
```

Example 2:
```
{{ if (2<=5) }}
<b>Printed text1</u>
<div>Printed text2</div>
{{endif}}
```
Because the test's value is true, this will generate the following output:
```
<b>Printed text1</u>
<div>Printed text2</div>
```

Of course, you can use your own template variables:

Example 3:
```
{{ if ([@variable]=="aaa") }}
<li>{{ [@variable] | uc }}</li>
{{ else }}
<li>Default value</li>
{{endif}}
```
In this case, if the @variable's value is "aaa", then printed with fully uppecase format. Else, the following string will be printed:
```
<li>Default value</li>
```

You can use multiple tests, if needed:

Example 4:
```
{{ if (([@element]>5) && ([@other_variable]<10)) }}
Multiple conditions working!
{{ endif }}
```

You can use built-in functions in the conditions.

Example 5:
```
{{ if ({{ [@number] | sqrt }}>3) }}
Printed text
{{ endif }}
```
In this case, Jellyfish first calculates the square root of @number, then run tests. If the value greater then 3, the block's content will be printed.

## For loops
With for loop, you can iterate over template arrays. It works for simple and multidimensional arrays too. You can use two types of for. The first doesn't use key variable, the second does. Built-in functions and conditions works too inside the loop's block. You can use custom names for loop variable and key. If you loop a single variable instead array, Jellyfish splits to characters, and loop over them.
Note: Nested loops are not supported!

Example 1:
If you have an arry, named "myarray", with following elements: 1, 2, 3, 4, 5
```
{{ for element in [@myarray]}}
<li>[@element]</li>
{{endfor}}
```
This will generate the following output:
```
<li>1</li>
<li>2</li>
<li>3</li>
<li>4</li>
<li>5</li>
```

Example 2:
If you have an array, named "myarray2" with following elements: a,b,c,d,e,f
```
{{ for key=>item in [@myarray2]}}
<b>[@key]->[@item]</b>
{{endfor}}
```
This will generate the following output:
```
<b>0->a</b>
<b>1->b</b>
<b>2->c</b>
<b>3->d</b>
<b>4->e</b>
<b>5->f</b>
```

Example 3:
If you have a string, named "string", and value: "value":
```
{{ for char in [@string]}}
<i>[@char]</i>
{{endfor}}
```
This will generate the following output:
```
<i>v</i>
<i>a</i>
<i>l</i>
<i>u</i>
<i>e</i>
```

## Include

You can include another template files. The included templates parsed too, like the parent. The chained includes are supported too, unlimited depth.

Example:
```
{{ include 'other_template.tpl' }}
```

## Macros
Macros are predefined blocks, with custom variables. Like functions in programming languages. You can define and call macros everywhere in template.
The macro blocks are not sensitive to whitespaces.

Example:
```
{{ macro formatted_text(color, size, face, text) }}
<font color="[@color]" face="[@face]" size=[@size]>[@text]</font>
{{ endmacro }}
```
Now you can use this macro with 'call' block:

Example:
```
{{ call formatted_text(red,12, Arial, Custom text) }}
```
This will generate the following output:
```
<font color="red" family="Arial" size=12>Custom text</font>
```
