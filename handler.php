<?php

require_once("class.krumo.php"); // Dependency on krumo

function convertParam($refparam)
{
    $info = array();
    $info["name"] = $refparam->getName();
    if($refparam->isDefaultValueAvailable())
        $info["default"] = $refparam->getDefaultValue();
    $info["optional"] = $refparam->isOptional();
    $info["passedByReference"] = $refparam->isPassedByReference();
    return $info;
}
/** Convert argument to printable format
 */
function convertArg($a)
{
    if(is_callable($a))
    {
        // PHP Can't serialize closures
        $r = new ReflectionFunction($a);
        $a = array($r->getName() => array(
            "parameters" => array_map('convertParam', $r->getParameters())
        ));
    }
    if(is_array($a))
    {
        // Recursively convert arrays
        $a = convertArray($a);
    }
    if(is_object($a))
    {
        $r = new ReflectionClass($a);
        $a = array($r->getName() => array(
            "properties" => convertArray(get_object_vars($a)),
            "methods" => $r->getMethods()));
    }
    return $a;
}

function convertArray($x)
{
    return array_map('convertArg', $x);
}

function convertArgs($x)
{
    $x['args'] = convertArray(coalesce($x['args'], array()));
    if(isset($x['object']))
        $x['object'] = convertArg($x['object']);
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
    header("HTTP/1.0 500 Internal Server Error");
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

function formatStackTrace()
{
    return array_map('convertArgs', debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT));
}

function errorInfo($errstr)
{
    $bt = formatStackTrace();
    $time = time();
    return array(
        "time" => $time,
        "msg" => $errstr,
        "post" => escapeInput($_POST),
        "get" => escapeInput($_GET),
        "uri" => coalesce($_SERVER["REQUEST_URI"]),
        "backtrace" => $bt);
}

function saveInfo($id, $info)
{
    $yaml = $id . ".yaml";
    _yaml_emit_file($yaml, $info);
}

function makeId($bt, $errstr)
{
    return md5(time() . $errstr . combineFunctionNames($bt));
}

function initializeErrorHandler($precall = null)
{
    if(is_callable($precall))
        call_user_func($precall);
    set_error_handler(function($errno, $errstr) {
        $info = errorInfo($errstr);
        $info["errno"] = $errno;
        $info["backtrace"] = array_splice($info["backtrace"], 3);
        $id = makeId($info['backtrace'], $errstr);
        saveInfo($id, $info);
        errorPage($id, $info);
        return true;
    });
}
