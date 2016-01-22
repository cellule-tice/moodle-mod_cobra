<?php 


/*
 * Load elex main lib
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once(dirname(__FILE__).'/lib/cobra.lib.php');
    global $DB;

//init request vars
$acceptedCmdList = array(   
    'getDisplayParams',    
    'setVisible', 
    'setInvisible',
    'moveUp', 
    'moveDown',    
    'changeType'
);

if( isset( $_REQUEST['ajaxcall'] ) && in_array( $_REQUEST['ajaxcall'], $acceptedCmdList ) ) 
{
    $call = $_REQUEST['ajaxcall'];
}

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... cobra instance ID - it should be named as the first character of the module.

if (isset($_REQUEST['courseId']))
{
    $id = $_REQUEST['courseId'];
}

//echo '<br> id = '. $id . ' et n = '.$n;
if ($id) {
    $cm         = get_coursemodule_from_id('cobra', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $cobra  = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $cobra  = $DB->get_record('cobra', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cobra->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('cobra', $cobra->id, $course->id, false, MUST_EXIST);
} 

$resource = isset( $_REQUEST['resource_id'] ) && is_string( $_REQUEST['resource_id'] ) ? $_REQUEST['resource_id'] : null;
$resourceType = isset( $_REQUEST['resource_type'] ) && is_string( $_REQUEST['resource_type'] ) ? $_REQUEST['resource_type'] : null;
$sibling = isset( $_REQUEST['sibling_id'] ) && is_string( $_REQUEST['sibling_id'] ) ? $_REQUEST['sibling_id'] : null;

// force headers
header('Content-Type: text/html; charset=iso-8859-1'); // Charset
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

if( 'getDisplayParams' == $call )
{
    $displayPrefs = get_cobra_preferences();
    $ccOrder = getCorpusTypeDisplayOrder();
    $order = implode( ',', $ccOrder );
    $displayPrefs['ccOrder'] = $order;
    echo json_encode( $displayPrefs );
}

if( 'setVisible' == $call )
{
    if( set_visibility( $resource, true, $resourceType, $course->id ) )
    {
        echo 'true';
        return true;
    }
    return false;
}

if( 'setInvisible' == $call )
{
    if( set_visibility( $resource, false, $resourceType, $course->id ) )
    {
        echo 'true';
        return true;
    }
    return false;
}

if( 'moveDown' == $call )
{
    $position = isset( $_REQUEST['position'] ) && is_numeric( $_REQUEST['position'] ) ? (int)$_REQUEST['position'] : 0;
    if( $position 
        && set_position( $sibling, $position++, $resourceType, $course->id  )
        && set_position( $resource, $position, $resourceType, $course->id  ) )
    {
        echo 'true';
        return true;
    }
    return false;
}

if( 'moveUp' == $call )
{
    $position = isset( $_REQUEST['position'] ) && is_numeric( $_REQUEST['position'] ) ? (int)$_REQUEST['position'] : 0;
    if( $position 
        && set_position( $sibling, $position--, $resourceType, $course->id  )
        && set_position( $resource, $position, $resourceType, $course->id  ) )
    {
        echo 'true';
        return true;
    }
    return false;
}

if( 'changeType' == $call )
{
    $textId = isset( $_REQUEST['resource_id'] ) && is_numeric( $_REQUEST['resource_id'] ) ? (int)$_REQUEST['resource_id'] : 0;
    if( changeTextType( $textId, $course->id ) )
    {
        $newType = getTextType( $textId, $course->id );
        echo get_string($newType,'cobra');
        return true;
    }
    return false;
}