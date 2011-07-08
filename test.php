<?php

require("handler.php");
initializeErrorHandler();

interface Hello
{
}
class Foo implements Hello
{
    private $baz = "baz";
    function __construct($call)
    {
    }
    function __set($key, $value)
    {
        $this->$key = $value;
    }
    function set($key, $value)
    {
        $this->$key = $value;
        return $this;
    }
    function get($key)
    {
        return $this->$key;
    }
}

class Bar extends Foo
{
    function __construct()
    {
        parent::__construct(function() { return "foo"; });
    }
}

function chain($cl)
{
    if(is_string($cl))
    {
        $r = new ReflectionClass($cl);
        if($r->isInstantiable())
        {
            return $r->newInstance();
        }
    }
    if(is_object($cl))
        return $cl;
}

$b = chain("Bar")
    ->set("foo"   , "foo")
    ->set("bar"   , "bar")
    ->set("baz"   , "baz")
    ->set("xyzzy" , "xyzzy")
    ->set("call", function(int &$x, $y = "", Foo $z) { return "foo"; });

// $r = new ReflectionFunction(function() {
//     return "";
// });
// var_dump($r->getName());
$b -> get("hello");
// $r = new ReflectionClass($b);
// var_dump(get_object_vars($b));
