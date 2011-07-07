<?php

function convertArg($a)
{
    if(is_callable($a))
        $a = "Callable";
    if(is_array($a))
        $a = array_map('convertArgs', $a);
    if(is_object($a))
        $a = "Object";
    return $a;
}

function convertArgs($x)
{
    if(isset($x['args']))
        $x['args'] = array_map('convertArg', $x['args']);
    return $x;
}

function combineFunctionNames($bt)
{
    return array_reduce(array_map(function($x) {
        return $x['function'];
    }, $bt), function($acc, $x) {
        return $acc .= $x;
    });
}

function _yaml_emit_file($file, $data)
{
    file_put_contents($file, yaml_emit($data));
}

set_error_handler(function($errno, $errstr) {
    $bt = array_map('convertArgs', array_splice(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT), 1));
    $time = time();
    $yaml = md5($time . $errstr . combineFunctionNames($bt)) . ".yaml";
    echo _yaml_emit_file($yaml, array(
        "time" => $time,
        "msg" => $errstr,
        "no" => $errno,
        "backtrace" => $bt));
    return true;
});
require("test.php");
