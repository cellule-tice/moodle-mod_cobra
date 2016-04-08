<?php
/**
 * Created by PhpStorm.
 * User: jmeuriss
 * Date: 13/01/2015
 * Time: 14:07
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir . '/medialib.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/lib/cobraremoteservice.class.php');
require_once(dirname(__FILE__).'/lib/cobracollectionwrapper.class.php');
require_once(dirname(__FILE__).'/lib/glossary.lib.php');

$call = json_decode(file_get_contents('php://input'));
$data = array();
switch($call->action)
{
    case 'loadText' :
        $params = array( 'id_text' => $call->textId );
        try {
            $data = CobraRemoteService::call( 'getFormattedText', $params, 'json', true );
            echo utf8_encode($data);
            die();
        } catch (Exception $e) {
            $data = $e;
            echo json_encode($data);
            die();
        }

        break;

    case 'loadGlossary' :
        $data = get_remote_glossary_info_for_student($call->textid, $call->courseid);
        break;

    case 'addToGlossary' :
        $lingentity = $call->lingEntity;
        add_to_glossary($lingentity, $call->textid, $call->courseid);
        break;

    case 'removeFromGlossary' :
        $lingentity = $call->lingEntity;
        remove_from_glossary($lingentity, $call->courseid);
        break;
}

echo json_encode($data);
die();