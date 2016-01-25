<?php
/**
 * CoBRA module for Moodle
 *
 * @copyright (c) 2015 Universite dce Namur
 *
 * @package CoBRA
 *
 * @author Cellule TICE <tice@fundp.ac.be>
 *
 */

try
{
    //loading Claroline kernel
    /*
     * Load elex main lib
     */
    
    require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once(dirname(__FILE__).'/locallib.php');
    
    //init request vars
    $acceptedCmdList = array(
        'displayEntry',
        'displayCC',
        'displayCard'
    );
    //security checks
    if( !isset( $_SERVER['HTTP_REFERER'] ) )
    {
        throw new Exception( 'Unauthorized access' );
    }
    if( isset( $_REQUEST['verb'] ) && in_array( $_REQUEST['verb'], $acceptedCmdList ) )
    {
        $call = $_REQUEST['verb'];
    }
    else
    {
        throw new Exception( 'Missing or invalid command' );
    }

    // force headers
    header('Content-Type: text/html; charset=iso-8859-1'); // Charset
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

    if( 'displayEntry' == $call )
    {
        $conceptId = isset( $_REQUEST['concept_id'] ) && is_numeric( $_REQUEST['concept_id'] ) ? $_REQUEST['concept_id'] : null ;
        $resourceId = isset( $_REQUEST['resource_id'] ) && is_numeric( $_REQUEST['resource_id'] ) ? $_REQUEST['resource_id'] : null ;
        $isExpr = isset( $_REQUEST['is_expr'] ) && is_numeric( $_REQUEST['is_expr'] ) ? $_REQUEST['is_expr'] : null ;       
        $encodeClic = (isset($_REQUEST['encodeClic']))?$_REQUEST['encodeClic'] : 1;
        $courseId = (isset($_REQUEST['courseId']))?$_REQUEST['courseId'] : 0;
        $userId = (isset($_REQUEST['userId']))?$_REQUEST['userId'] : 0;
        $pref = isset($_REQUEST['params']) ? $_REQUEST['params'] : null; 
        $params = array( 'concept_id' => $conceptId, 'resource_id' => $resourceId, 'is_expr' => $isExpr, 'params' => $pref );
        
        $html = CobraRemoteService::call( 'displayEntry', $params, 'json' );
        $entryType = $isExpr ? 'expression' : 'lemma';
        $params = array('conceptId' => $conceptId,'entryType' => $entryType);
        $lingEntity = CobraRemoteService::call( 'getEntityLingIdFromConcept', $params, 'html' );
        $lingEntity = str_replace("\"","", $lingEntity);
        if ($encodeClic)
        {
            clic( $resourceId, $lingEntity, $DB, $courseId, $userId );
        }
        echo $html;
    }

    if( 'displayCC' == $call )
    {
        $id = isset( $_REQUEST['id_cc'] ) && is_numeric( $_REQUEST['id_cc'] ) ? $_REQUEST['id_cc'] : null ;
        $occId = isset( $_REQUEST['id_occ'] ) && is_numeric( $_REQUEST['id_occ'] ) ? $_REQUEST['id_occ'] : null ;
        $color = isset( $_REQUEST['bg_color'] ) && is_string( $_REQUEST['bg_color'] ) ? $_REQUEST['bg_color'] : null ;  
        $pref = isset($_REQUEST['params']) ? $_REQUEST['params'] : null; 
        $params = array( 'id_cc' => $id, 'id_occ' => $occId, 'params' => $pref);
        $html = CobraRemoteService::call( 'displayCC', $params, 'html' );
        echo $html;
    }

    if( 'displayCard' == $call )
    {
        $entryId = isset( $_REQUEST['entry_id'] ) && is_numeric( $_REQUEST['entry_id'] ) ? $_REQUEST['entry_id'] : null;
        $isExpr = isset( $_REQUEST['is_expr'] ) && is_numeric( $_REQUEST['is_expr'] ) ? (bool)$_REQUEST['is_expr'] : false;
        $construction = isset( $_REQUEST['currentConstruction'] ) && is_string( $_REQUEST['currentConstruction'] ) ? $_REQUEST['currentConstruction'] : null;
        $preferences = isset( $_REQUEST['params'] ) ? $_REQUEST['params'] : null;
        $params = array( 'entry_id' => $entryId, 'is_expr' => $isExpr, 'currentConstruction' => $construction, 'params' => $preferences );
        $html = CobraRemoteService::call( 'displayCard', $params, 'html' );
        echo $html;
    }
}
catch( Exception $e )
{
    die( $e->getMessage() );
}