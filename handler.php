<?php

require_once("class.krumo.php"); // Dependency on krumo

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

function coalesce(&$x, $def = null)
{
    if(!isset($x))
        return $def;
    return $x;
}

function escapeInput($args)
{
    return array_map(function($x) {
        $y = htmlspecialchars($x);
        return "'$y' (" . (($x == $y) ? "original" : "escaped") . ")";
    }, $args);
}

function errorPage($id, $info)
{
    $bt = array();
    foreach($info["backtrace"] as $stack)
    {
        $fn = $stack["function"];
        $bt[$fn] = $stack;
    }
    $info["backtrace"] = $bt;
?>
<html>
    <head>
    </head>
    </body>
    <h2>Internal server error</h2>
    <p>Information about the error has been saved in (<?php echo $id ?>)</p>
        <?php echo krumo($info); ?>
    </body>
</html>
<?php
}

set_error_handler(function($errno, $errstr) {
    $bt = array_map('convertArgs', array_splice(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT), 1));
    $time = time();
    $id = md5($time . $errstr . combineFunctionNames($bt));
    $yaml = $id . ".yaml";
    $info = array(
        "time" => $time,
        "msg" => $errstr,
        "errno" => $errno,
        "post" => escapeInput($_POST),
        "get" => escapeInput($_GET),
        "uri" => coalesce($_SERVER["REQUEST_URI"]),
        "backtrace" => $bt);
    _yaml_emit_file($yaml, $info);
    errorPage($id, $info);
    return true;
});
