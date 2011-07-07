<?php

require("handler.php");
initializeErrorHandler();

interface Hello
{
}
class Foo implements Hello
{
    function __construct($call)
    {
        fread($call());
    }
}

class Bar extends Foo
{
    function __construct()
    {
        parent::__construct(function() { return "foo"; });
    }
}

new Bar();

