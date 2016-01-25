<?php
//require_once dirname( __FILE__ ) . '/glossary.lib.php';
//require_once claro_get_conf_repository() . 'ELEX.conf.php';
require_once dirname( __FILE__ ) . '/cobratextwrapper.class.php';
require_once dirname( __FILE__ ) . '/cobraremoteservice.class.php';
require_once($CFG->libdir.'/formslib.php');
//require_once get_path( 'coursesRepositorySys' ) . claro_get_current_course_id() . '/elex/' . 'ELEX_COURSE.conf.php';

/**
 * Loads the local list of E-Lex texts associated to a text collection (with filter on user profile)
 * @param $collection identifier of the text collection
 * @param $loadMode 'all' for course managers, 'visible' for students
 * @return array containing information on texts to display
 */
function load_text_list( $collection, $loadMode = 'all' )
{          
    global $DB;
    global $course;
    $andClause = 'visible' == $loadMode ? " AND `visibility` = '1' " : "";
    $list = $DB->get_records_select('cobra_texts_config', "course='$course->id' AND id_collection=$collection " . $andClause, null, 'position');
   
    if (empty($list)) return false;

    $textList = array();  

    $params = array( 'collection' => (int)$collection );
    $remoteTextObjectList = CobraRemoteService::call( 'loadTexts', $params );
      
    foreach ($list as $text )
    {
        $text->title = '';
        $text->source = '';
        foreach( $remoteTextObjectList as $textOjbect )
        {
            if( $text->id_text == $textOjbect->id )
            {
                $text->title =  $textOjbect->title ;
                $text->source =   $textOjbect->source ;
            }
        }
        $textList[] = $text;
    }
    return $textList;
}

/**
 * Changes the visibility status of the given resource (collection or text)
 * @param $resourceId identifier of the resource
 * @param $setVisible boolean 'true' to make the resource visible for students, 'false' to hide it
 * @return boolean true on success, false otherwise
 */
function set_visibility( $resourceId, $setVisible, $resourceType, $courseId )
{     
    global $DB;
    $visibility = $setVisible ? '1' : '0';
    $dataObject = new  stdClass();
    $dataObject->course = $courseId;
    if( 'text' == $resourceType )
    {
        $list = $DB->get_record_select('cobra_texts_config', "course='$courseId' AND id_text='$resourceId'");
        if (!empty($list))
        {
            // update
            $dataObject->id= $list->id; 
            $dataObject->visibility = $visibility;
            if (!$DB->update_record('cobra_texts_config', $dataObject) ) return false;
            return true;
        }
        return false;
    }
    elseif( 'collection' == $resourceType )
    {
        $list = $DB->get_record_select('cobra_registered_collections', "course='$courseId' AND id_collection='$resourceId'");
         if (!empty($list))
        {
            // update
            $dataObject->id= $list->id; 
            $dataObject->visibility = $visibility;
            if (!$DB->update_record('cobra_registered_collections', $dataObject) ) return false;
            return true;
        }
        return false;       
    }
    return true;
}

/**
 * Changes the position of the given resource (collection or text) in the list
 * @param $resourceId identifier of the resource
 * @param $position the new position to assign to the resource
 * @return boolean true on success, false otherwise
 */
function set_position( $resourceId, $position, $resourceType,  $courseId )
{
    global $DB;
    $dataObject = new  stdClass();
    $dataObject->course = $courseId;
    if( 'text' == $resourceType )
    {
        $list = $DB->get_record_select('cobra_texts_config', "course='$courseId' AND id_text='$resourceId'");
        if (!empty($list))
        {
            // update
            $dataObject->id= $list->id; 
            $dataObject->position = $position;
            if (!$DB->update_record('cobra_texts_config', $dataObject) ) return false;
            return true;
        }
        return false;
    }
    elseif( 'collection' == $resourceType )
    {
         $list = $DB->get_record_select('cobra_registered_collections', "course='$courseId' AND id_collection='$resourceId'");
          if (!empty($list))
        {
            // update
            $dataObject->id= $list->id; 
            $dataObject->position = $position;
            if (!$DB->update_record('cobra_registered_collections', $dataObject) ) return false;
            return true;
        }
        return false;  
    
    }
    return true;;
}

/**
 * Inserts a row in table 'ordre_concordances' representing a corpus type
 * @param int $typeId : identifier of corpus type
 */
function insert_corpus_type_display_order( $typeId )
{    
    global $DB,$course;     
    $dataObject = new  stdClass();
    $dataObject->course=$course->id;
    $dataObject->id_type=$typeId;
    return  $DB->insert_record('cobra_ordre_concordances', $dataObject) ;
}

/**
 * Gets the list of unregistered collections available for the language of current course
 * @param array $exclusionList the list of already registered collections
 * @return array containing the list of these collections
 */
function get_filtered_cobra_collections( $language, $exclusionList = array() )
{
    $collections = array();
    $params = array( 'language' => $language);
    $collectionsObjectList = CobraRemoteService::call( 'loadFilteredCollections', $params );
    foreach( $collectionsObjectList as $remoteCollection  )
    {
        if( in_array( $remoteCollection->id, $exclusionList ) ) continue;
        $collections[] = array( 'id' => utf8_decode( $remoteCollection->id ),
                                'label' =>  $remoteCollection->label  );
    }
    return $collections;
}

/**
 * Gives the list of registered collections for current course according to user status
 * --> all registered collections for course manager, visible ones for students
 * @param string $loadMode : 'all' or 'visible'
 * @return array the list of registered (visible) collections
 */
function get_registered_collections( $loadMode = 'all' )
{
    global $DB;
    global $course;
    $collectionsList = array();
    $params = null;
    $list = $DB->get_records_select('cobra_registered_collections', "course='$course->id'");
    foreach ($list as $collectionInfo) 
    {
       $collectionsList[$collectionInfo->id_collection]= array('id_collection'=>$collectionInfo->id_collection, 'label'=>$collectionInfo->label,'local_label'=>$collectionInfo->local_label,'visibility'=>$collectionInfo->visibility);
    }            
    return $collectionsList;
}

/**
 * Loads the list of E-Lex texts associated to a text collection from the remote E-Lex repository
 * @param $collection identifier of the text collection
 * @return array containing information on texts to display
 */
function load_remote_text_list( $collection )
{
    $textList = array();
    $params = array( 'collection' => (int)$collection );
    $remoteTextObjectList = CobraRemoteService::call( 'loadTexts', $params );

    foreach( $remoteTextObjectList as $textObject  )
    {
        $text['id'] = utf8_decode( $textObject->id );
        $text['title'] = utf8_decode( $textObject->title );
        $text['source'] = utf8_decode( $textObject->source );
        $textList[] = $text;
    }
    return $textList;
}

/**
 * Deletes local data associated to the texts of the collection given in args
 * @param int $collection identifier of the collection
 * @return boolean true on success, false otherwise
 */
function remote_text_list( $collection )
{
    global $course,$DB;
    return $DB->delete_records('cobra_texts_config', array('course'=>$course->id, 'id_collection'=>$collection)); 

}


/**
 * Returns an array containing module preferences for the current course
 * @return array
 */
function get_cobra_preferences()
{
    global $DB,$course;    
    
    $params = array();    
    $info = $DB->get_record_select('cobra',"course='$course->id'",null,'language');  
    $params['language']  = $info->language;
    // init : 
    $params['nextprevbuttons']  = 'HIDE';
    $params['gender'] = 'HIDE';
    $params['ff'] = 'HIDE';
    $params['player'] = 'HIDE';
    $params['translations'] = 'ALWAYS';
    $params['descriptions'] = 'ALWAYS';
    $params['illustrations'] = 'HIDE';
    $params['examples'] = 'bi-text';
    $params['occurrences'] = 'HIDE';
    
    $list = $DB->get_records_select('cobra_prefs', "course='$course->id'");
    foreach ($list as $elt)
    {
        $params[$elt->param] = $elt->value;
    }
    return $params;
}

/**
 * Records current course preferences regarding E-lex module
 * @param array $prefs
 * @return boolean true on success, false otherwise
 */
function save_Cobra_preferences( $prefs )
{
     global $DB,$course;    
    foreach ($prefs as $key=>$value)
    {
        $dataObject = new stdClass();
        $dataObject->course = $course->id;
        $dataObject->param = $key;
        $dataObject->value= $value;
        $list = $DB->get_record_select('cobra_prefs', "course='$course->id' AND param='$key'");
        if (!empty($list))
        {
            // update
            $dataObject->id= $list->id;
            if (!$DB->update_record('cobra_prefs', $dataObject) ) return false;
          
        }
        else
        {
            // insert
            if (!$DB->insert_record('cobra_prefs', $dataObject) ) return false;
        }
    }
    return true;
}

/**
 * Deletes current preferences linked to corpus selection and display order
 * @return boolean true on success, false otherwise
 */
function clear_corpus_selection()
{    
    global $DB, $course; 
    return $DB->delete_records('cobra_ordre_concordances', array('course'=>$course->id)); 
}



/**
 * Updates click count table
 * @param int $textId the text within which a word was clicked
 * @param int $lingEntityId identifier of the linguistic entity that was clicked
 */
function clic( $textId, $lingEntityId, $DB, $courseId, $userId )
{ 
    $info = $DB->get_record_select('cobra_clic',"course='$courseId' AND user_id='$userId' AND id_text='$textId' AND id_entite_ling='$lingEntityId'");  
    if (!$info)
    {
       $dataObject = new  stdClass();
          $dataObject->course=$courseId;
          $dataObject->user_id=$userId;
          $dataObject->id_text=$textId;
          $dataObject->id_entite_ling=$lingEntityId;
          $dataObject->nb_clics=1;
          $dataObject->datecreate=date("Y-m-d H:i:s");
          $dataObject->datemodif=date("Y-m-d H:i:s");
          return  $DB->insert_record('cobra_clic', $dataObject) ;
    }
    else
    {
        $dataObject = new  stdClass();
        $dataObject->id=$info->id;
        $dataObject->nb_clics = ($info->nb_clics + 1);
        return  $DB->update_record('cobra_clic', $dataObject) ;
    }
}


/**
 * Collects the title of the text given in args and produces an html-free string
 * Handled with remote call
 * @param int $textId the identifier of the text
 * @return an html-free string with the text title
 */
/*not used
 * function getTitleFromId( $textId )
{
    $params = array( 'id_text' => (int)$textId );
    $textTitle = CobraRemoteService::call( 'getTextTitle', $params );
    return strip_tags( utf8_decode ( $textTitle ) );
}*/

/**
 * Collects the set of synonyms for the concept given in args and produces a string representation of it
 * Handled with remote call
 * @param int $conceptId identifier of the concept
 * @param string $entryType the type of lexicon entry ('lemma' or 'expression')
 */
function get_translations( $conceptId, $entryType )
{
    $params = array( 'id_concept' => (int)$conceptId, 'entry_type' => $entryType );
    $translations = CobraRemoteService::call( 'get_translations', $params );
    return $translations ;
}


/**
 * Collects information associated to the given linguistic entity
 * Handled with remote call
 * @param int $lingEntityId identifier of the linguistic entity
 * @return array containing information on this linguistic entity
 */
function get_concept_info_from_ling_entity( $lingEntityId )
{
    $params = array( 'ling_entity_id' => (int)$lingEntityId );
    $conceptInfo = CobraRemoteService::call( 'get_concept_info_from_ling_entity', $params );
    return array( $conceptInfo->id_concept, $conceptInfo->construction , $conceptInfo->entry_type, $conceptInfo->entry_category, $conceptInfo->entry, $conceptInfo->entry_id );
}

/**
 * Gives the list of corpus types for the language given in args
 * Handled with remote call
 * @param string $langue
 * @return array $listOfCorpusType
 */
function returnValidListTypeCorpus ( $language )
{
    $params = array( 'language' => $language );
    $remotelistOfCorpusType = CobraRemoteService::call( 'returnValidListTypeCorpus', $params );
    $listOfCorpusType = array();
    foreach( $remotelistOfCorpusType as $corpusObject  )
    {
        $corpus['id'] = $corpusObject->id ;
        $corpus['name'] =  $corpusObject->name ;
        $listOfCorpusType[] = $corpus;
    }
    return $listOfCorpusType;
}

/**
 * Gets the list and order of corpus types that must be taken into account when displaying concordances
 * @return array of corpus types sorted according to the recorded order of display
 */
function getCorpusTypeDisplayOrder()
{
    global $DB, $course;
    $list = $DB->get_records_select('cobra_ordre_concordances', "course='$course->id'",null,'id');
    $typeList = array();
    $i=0;
    foreach ($list as $info)
    {
        $i++;
        $typeList[$i] = $info->id_type;
    }
      return $typeList;
}

/**
 * Collects information associated to the given corpus
 * Handled with remote call
 * @param int $lingEntityId identifier of the corpus
 * @return array containing information about the corpus
 */
/*function get_corpus_iInfo( $corpusId )
{
    $params = array( 'id_corpus' => $corpusId );
    $corpusInfo = CobraRemoteService::call( 'getCorpusInfo', $params );
    if (is_array($corpusInfo))
    {
        return array( $corpusInfo->id_groupe, $corpusInfo->nom_corpus, utf8_decode( $corpusInfo->reference ), $corpusInfo->langue, $corpusInfo->id_type );
    }
    return array();
}*/

/**
 * Gets the css class associated to the corpus type given in args
 * Handled with remote call
 * @param int $typeId
 * @return string css class
 */
function find_background( $typeId )
{
    $params = array( 'typeId' => $typeId );
    $backGroundClass = CobraRemoteService::call( 'findBackGround', $params );
    return $backGroundClass;
}

/**
 * Checks existence of a given corpus type for a given language
 * @param int $typeId
 * @param string $language
 * @return boolean true if the given corpus type exists for that language, false otherwise
 */
function corpus_type_exists( $typeId, $language )
{
    $params = array( 'language' => $language, 'typeId' => $typeId );
    $ok = CobraRemoteService::call( 'corpusTypeExists', $params );
    return $ok;
}

/**
 * Gives the list of valid entry types in the lexicon (currently 'lemma' and 'expression')
 * @return array containing the list
 */
function get_valid_entry_types()
{
    return array( 'lemma', 'expression' );
}

/**
 * Tries various methods to handle http request
 * @param string $url url address of the http request
 * @throws Exception
 * @return the response obtained on success, false otherwise
 */
function cobra_http_request( $url )
{
    if( ini_get( 'allow_url_fopen' ) )
    {
        if( false === $response = @file_get_contents( $url ) )
        {
            return false;
        }
        else
        {
            return $response;
        }
    }
    elseif( function_exists('curl_init') )
    {
        if( !$response = cobra_curl_request( $url ) )
        {
            return false;
        }
        else
        {
            return $response;
        }
    }
    else
    {
        throw new Exception( "Your PHP install does not support url access." );
    }
}

/**
 * Handles curl request
 * @param string $url url address of the request
 * @return the response obtained on success, false otherwise
 */
function cobra_curl_request( $url )
{
    $handle = curl_init();

    $options = array( CURLOPT_URL => $url,
                      CURLOPT_HEADER => false,
                      CURLOPT_RETURNTRANSFER => true
                    );
    curl_setopt_array( $handle, $options );

    if( !$content = curl_exec( $handle ) )
    {
        return false;
    }
    curl_close( $handle );
    return $content;
}

/**
 * Changes the type of the text with id given in args
 * Possible modes are 'Lesson', 'Reading' and 'Exercise'
 * @param int $textId
 * @return boolean true on success, false otherwise
 */
function change_text_type( $textId, $courseId )
{
    global $DB;
    $list = $DB->get_record_select('cobra_texts_config', "course='$courseId' AND id_text='$textId'");
    if (!empty($list))
    {
      
        $textType = $list->text_type;
        $newType = getNextType( $textType );
         $dataObject = new stdClass();
        $dataObject->id= $list->id; 
        $dataObject->text_type=$newType;
        if (!$DB->update_record('cobra_texts_config', $dataObject) ) return false;
        return true;
    }
    return false;            
}

/**
 * Gives the type of the text with id given in args
 * @param int $textId
 * @return string the type of the text
 */
function getTextType( $textId, $courseId )
{
    global $DB;
     $list = $DB->get_record_select('cobra_texts_config', "course='$courseId' AND id_text='$textId'");
    if (!empty($list))
    {
        return $list->text_type;
    }
    return false; 
}

/**
 * Gives the "next" type of text according to a definite order : Lesson -> Reading -> Exercise
 * @param string $textType the current text type
 * @return string the "next" text type according to the current one
 */
function getNextType( $textType )
{
    switch( $textType )
    {
        case 'Lesson' : $newType = 'Reading'; break;
        case 'Reading' : $newType = 'Exercise'; break;
        case 'Exercise' : $newType = 'Lesson'; break;
        default : $newType = 'Lesson';
    }
    return $newType;
}

function getDistinctAccessForText($textId)
{
     global $DB, $course;
    $userList = array();
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND id_text='$textId' GROUP BY user_id");
    foreach ($list as $info)
    {
        $userList[] = $info->user_id;
    }

    if (hasAnonymousClic())
    {
        $userList[] = 0;
    }
    return $userList;
}

function hasAnonymousClic ()
{
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND user_id='0'");
    if (!empty($list)) return true;
    return false;     
}


function getNbClicsForText ($textId)
{
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND id_text='$textId'");
    $nb = 0;
    foreach ($list as $info)
    {
        $nb += $info->nb_clics;
    }
    return $nb;
}

function getNbTextsForUser ($userId)
{
    global $DB, $course;
    $list = $DB->get_recordset_select('cobra_clic', "course='$course->id' AND user_id='$userId'", null,'','DISTINCT id_text');
    return sizeof($list);
}


function getNbClicForUser ($userId)
{
    global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id' AND user_id='$userId'");
    $nb = 0;
    foreach ($list as $info)
    {
        $nb += $info->nb_clics;
    }
    return $nb;
}

function getUserListForClic ()
{
     global $DB, $course;
    $list = $DB->get_records_select('cobra_clic', "course='$course->id'", null, '', 'DISTINCT user_id');
    $userList = array();
    
    foreach ($list as $info)
    {
        $user = $DB->get_record_select('user',"id='$info->user_id'");
        $userList[] = array('userId'=>$info->user_id, 'lastName'=>$user->lastname, 'firstName'=>$user->firstname);
    }
    return $userList;
}

function getNbTagsInText ($textId)
{
    $text = new CobraTextWrapper();
    $text->setTextId( $textId );
    $text->load();
    $html = $text->formatHtml();
    $nb = substr_count($html,'</span>');
    return $nb;
}

function increaseScriptTime( $time = 0)
{
    set_time_limit( $time );
}

function clean_all_stats( $courseId)
{
    global $DB;
    return $DB->delete_records('cobra_clic', array('course'=>$courseId)); 
}

function cleanStatsBeforeDate ($courseId, $myDate)
{
    global $DB;
    $dateModif = ' < FROM_UNIXTIME('. $myDate.')';
    return $DB->delete_records('cobra_clic', array('course'=>$courseId, 'datemodif' => $dateModif));	
}

function getNextTextId($text)
{
     global $DB, $course;
     $textCollectionId = $text->getCollectionId();
     $textPosition = $text->getPosition();
    $list = $DB->get_records_select('cobra_texts_config', "course='$course->id' AND id_collection='$textCollectionId' AND position > '$textPosition'", array(), 'position ASC', 'id_text', 0,1);
    if (empty($list)) return false;
    $keys = array_keys($list);
    return $list[$keys[0]]->id_text;
}

function getPreviousTextId($text)
{
     global $DB, $course;
     $textCollectionId = $text->getCollectionId();
     $textPosition = $text->getPosition();
    $list = $DB->get_records_select('cobra_texts_config', "course='$course->id' AND id_collection='$textCollectionId' AND position < '$textPosition'", array(), 'position ASC', 'id_text', 0,1);
    if (empty($list)) return false;
    $keys = array_keys($list);
    return $list[$keys[0]]->id_text;
}

function get_clicked_texts_frequency ( $courseId )
{
    global $DB;
    $tab_nb_clics = array();
    $params = array('GROUP BY id_text', 'HAVING nb >=5');
    $list = $DB->get_records_select('cobra_clic', "course='$courseId'", $params, 'nb DESC, id_text', 'id_text, SUM(nb_clics) AS nb');
    foreach ($list as $info)
    {
        $id_text = $info->id_text;
        $nb_total_clics = $info->nb;
        $tab_nb_clics[$id_text] = $nb_total_clics;
     }    
     arsort( $tab_nb_clics );
     return $tab_nb_clics;
     
}

function get_clicked_entries ($courseId, $nb = 20)
{
    $tab_nb_clics = array();
    global $DB;
     $params = array( );
    $list = $DB->get_records_select('cobra_clic', "course='$courseId' GROUP BY id_entite_ling HAVING nb >=' $nb' ", $params, 'id_entite_ling ASC LIMIT 100', 'id_entite_ling, SUM(nb_clics) AS nb');
     foreach ($list as $info)
    {
        $nb_total_clics = $info->nb;
        $tab_nb_clics[$info->id_entite_ling] = $nb_total_clics;
     }  
    return $tab_nb_clics;
}

class mod_cleanStats_form extends moodleform {
    /**
     * Define this form - called by the parent constructor
     */
    public function definition() 
   {
        $mform = $this->_form;        
        $options = array('' => '', 'ALL'=>get_string('All','cobra'), 'BEFORE'=>get_string('Before','cobra'));
        $mform->addElement('select','scope', get_string('Delete', 'cobra'), $options);      
        $mform->addElement('date_selector', 'before_date', get_string('Before', 'cobra'));                  
        $this->add_action_buttons(true, get_string('OK','cobra'));         
   }
}


