<?php /* $Id: glossary.lib.php 283 2011-09-01 07:47:03Z ldumorti $ */

/**
 * @param $glossary : array
 * @param text : class Text
 * @return @glossary : array
 */
function getGlossaryEntryOfText( $glossary, $text, $num )
{
    $textId= $text['id_text'];
    $tab_glossary = array();
    foreach ($glossary as $key=>$entiteLingId)
    {
        $params = array('id_entite_ling'=>$entiteLingId);
        $tab_glossary[$key] =  ElexRemoteService::call( 'getGlossaryInfoForEntityLing', $params );
    }
    $glossary = array();
    foreach ( $tab_glossary as $key=>$glossaryEntry )
    {
        $glossary[$key]['id'] = $glossaryEntry->id;
        $glossary[$key]['type'] = $glossaryEntry->type;
        $glossary[$key]['entry'] = utf8_decode( $glossaryEntry->entry );
        $glossary[$key]['category'] = $glossaryEntry->category;
        $glossary[$key]['ss_cat'] = utf8_decode( $glossaryEntry->ss_cat );
        $glossary[$key]['traduction'] = utf8_decode( $glossaryEntry->traduction );
        $glossary[$key]['num']= $num;
        $glossary[$key]['title'] = strip_tags( $text['title'] );
    }
    return $glossary;
}

/**
 * @param $textId integer
 * @return $glossary array
 */
function getGlossaryTableEntries( $textId='0' )
{
    $moduleTbl = get_module_course_tbl( array( 'elex_glossaire' ), claro_get_current_course_id() );

    $glossary = array();
    if ( $textId== 0 )
    {
        $sql = "SELECT id_text, id_entite_ling FROM `".$moduleTbl['elex_glossaire']."` " .
                "ORDER BY id_text";
    }
    else
    {
        $sql = "SELECT id_text, id_entite_ling FROM `".$moduleTbl['elex_glossaire']."` " .
                "WHERE id_text='".(int)$textId."'";
    }
    $res = Claroline::getDatabase()->query( $sql );
    while ( $row = $res->fetch() )
    {
        $glossary[$row['id_text']][] = $row['id_entite_ling'];
    }
    return $glossary;
}

/**
 * @param $textId integer
 * @return $glossary array
 */

function getGlossaryForText ( $textId )
{
    $glossary = array();
    increaseScriptTime();
    $oldConceptList = array();
    for ( $i=1; $i<=2; $i++ )
    {
        switch ($i)
        {
            case '1' : $entryType = 'expression';
                break;
            case '2' : $entryType = 'lemma';
                break;
        }
        $conceptIdList = elex_list_concepts_in_text( $textId, $entryType );
        foreach ($conceptIdList as $conceptId)
        {
            if (!in_array($conceptId, $oldConceptList))
            {
                $params = array('conceptId'=>$conceptId, 'entryType'=>$entryType);
                $entityLingId = ElexRemoteService::call( 'getEntityLingIdFromConcept', $params );
                $glossary[$textId][] = $entityLingId;
                $oldConceptList[] = $conceptId;
            }
        }
    }
    return $glossary;
}

/**
 * @param $textId int
 * @param $entiteLingId int
 * @return boolean
 */
function insertGlossaryEntry ( $textId, $entiteLingId )
{
    $moduleTbl = get_module_course_tbl( array( 'elex_glossaire' ), claro_get_current_course_id() );
    $sql = "SELECT id_text FROM `".$moduleTbl['elex_glossaire']."` " .
            "WHERE id_entite_ling='".(int)$entiteLingId."'";
    $res = Claroline::getDatabase()->query( $sql );
    if ( !$res->count() )
    {
        $sql = "INSERT INTO `".$moduleTbl['elex_glossaire']."` SET id_text='".(int)$textId."', id_entite_ling='".(int)$entiteLingId."'";
        if( ! Claroline::getDatabase()->exec( $sql ) )
        {
            $console->pushMessage( mysql_error(), 'error' );
            return false;
        }
    }
    return true;
}


function buildGlossary ()
{
    set_time_limit(0);
    $moduleTbl = get_module_course_tbl( array( 'elex_glossaire' ), claro_get_current_course_id() );
    $query = "TRUNCATE TABLE `" . $moduleTbl['elex_glossaire'] . "`";
    Claroline::getDatabase()->exec( $query );
    $collectionList = getRegisteredCollections('visible');
    foreach( $collectionList as $collection )
    {
        $textList = loadTextList( $collection['id_collection'], 'visible' );
        foreach ($textList as $text)
        {
            // expressions
            if (!buildGlossaryText( $text['id_text'], 'expression' ))
            {
                return false;

            }
            // lemmas
            if ( !buildGlossaryText( $text['id_text'], 'lemma' ) )
            {
                return false;

            }
        }
    }
    return true;
}

function buildGlossaryText( $textId, $entryType )
{
    $moduleTbl = get_module_course_tbl( array( 'elex_glossaire' ), claro_get_current_course_id() );
    $conceptIdList = elex_list_concepts_in_text($textId, $entryType);
    foreach ($conceptIdList as $conceptId)
    {
        $params = array('conceptId'=>$conceptId, 'entryType'=>$entryType);
        $entityLingId = ElexRemoteService::call( 'getEntityLingIdFromConcept', $params );
        if (!insertGlossaryEntry($textId,$entityLingId))
        {
            $console->pushMessage('Problem in glossary construction');
            return false;
        }
    }
    return true;
}

    function array_sort_func( $a, $b=NULL )
    {
       static $keys;
       if($b===NULL) return $keys=$a;
       foreach($keys as $k)
       {
          if(@$k[0]=='!')
          {
             $k=substr($k,1);
             if(@$a[$k]!==@$b[$k])
             {
                return strcmp(strtolower(@$b[$k]),strtolower(@$a[$k]));
             }
          }
          else if(@$a[$k]!==@$b[$k])
          {
             return strcmp( strtolower(@$a[$k]), strtolower(@$b[$k]));
          }
       }
       return 0;
    }

    function array_sort ( $array )
    {
       $keys=func_get_args();
       array_shift($keys);
       array_sort_func($keys);
       usort($array,"array_sort_func");
       return $array;
    }

function exportCsvGlossaryEntries( $glossary )
{
     // contruction of XML flow
    $csv = export_glossary( $glossary );
    if( !empty($csv) )
    {
        header("Content-type: application/csv");
        header('Content-Disposition: attachment; filename="'.claro_get_current_course_id().'_glossary.csv"');
        echo $csv;
    }
    exit;
}

function export_glossary( $glossary )
{
    $csvGlossary = new csvGlossary();
    $csvGlossary->buildRecords( $glossary );
    $csvContent = $csvGlossary->export();
    return $csvContent;
}


include get_path('incRepositorySys') . '/lib/csv.class.php';

class csvGlossary extends CsvRecordlistExporter
{
    function csvGlossary()
    {
       parent::__construct(";"); // call constructor of parent class
    }

    function buildRecords($glossary)
    {
        $this->recordList[1] = array(get_lang('Lemma_form'), get_lang('Category'), get_lang('info'), get_lang('Translation'), get_lang('Text'));
        list( $lemmaGlossary, $expGlossary ) = explodeGlossaryIntoLemmasAndExpression( $glossary );
       /* $lemmaGlossary = array_sort ( $lemmaGlossary, 'entry', 'num', 'category', 'traduction' );
        $lemmaGlossary = array_sort ( $lemmaGlossary, 'num', 'entry',  'category', 'traduction' );*/
      //  $expGlossary = array_sort( $expGlossary, 'entry', 'category', 'num', 'traduction' );
        $entry = array();
        $category = array();
        $num = array();
        $traduction = array();
        foreach ($lemmaGlossary as $key=>$row)
        {
        	$entry[$key]= $row['entry'];
        	$category[$key] = $row['category'];
        	$num[$key]= $row['num'];
        	$traduction[$key] = $row['traduction'];
        }
        array_multisort ( $entry, SORT_ASC, $category, SORT_ASC, $num, SORT_NUMERIC, $traduction, SORT_ASC, $lemmaGlossary);
        $records = array();
        foreach ($lemmaGlossary as $key=>$entry)
        {
             // $this->recordList is defined in parent class csv
             $newRecord = array($entry["entry"], $entry["category"] , $entry["ss_cat"], $entry["traduction"]);
             if (!in_array($newRecord, $records))
             {
            	$this->recordList[] = array($entry["entry"], $entry["category"] , $entry["ss_cat"], $entry["traduction"], $entry['title']);
            	$records[] = $newRecord;
             }
        }
        $entry = array();
        $category = array();
        $num = array();
        $traduction = array();
        foreach ($expGlossary as $key=>$row)
        {
        	$entry[$key]= $row['entry'];
        	$category[$key] = $row['category'];
        	$num[$key]= $row['num'];
        	$traduction[$key] = $row['traduction'];
        }
        array_multisort ( $entry, SORT_ASC, $category, SORT_ASC, $num, SORT_NUMERIC, $traduction, SORT_ASC, $expGlossary);

        foreach ($expGlossary as $key=>$entry)
        {
        	$newRecord = array($entry["entry"], $entry["category"] , $entry["ss_cat"], $entry["traduction"]);
            if (!in_array($newRecord, $records))
             {
             	// $this->recordList is defined in parent class csv
            	$this->recordList[] = array($entry["entry"], $entry["category"] , '', $entry["traduction"], $entry['title']);
            	$records[] = $newRecord;
            }
        }

        if( is_array($this->recordList) && !empty($this->recordList) ) return true;
    }
}

function explodeGlossaryIntoLemmasAndExpression ( $glossary )
{
    $lemmaList = array();
    $expList = array();
    foreach ($glossary as $key=>$element)
    {
        if ($element['type'] == 'lemma')
        {
            $lemmaList[] = array("id"=>$element['id'],"entry"=>$element["entry"],"category"=>$element["category"],"ss_cat"=>$element["ss_cat"],"traduction"=>$element["traduction"], "title"=>$element['title'], "num"=>$element['num']);
        }
        else if ($element['type'] == 'expression')
        {
            $expList[] = array("id"=>$element['id'],"entry"=>$element["entry"],"category"=>$element["category"],"ss_cat"=>$element["ss_cat"],"traduction"=>$element["traduction"], "title"=>$element['title'], "num"=>$element['num']);
        }
    }
    return array( $lemmaList, $expList );
}

function  getConceptListFromPara( $para, $entryType, $tab_id_concept = array() )
{
    $sep1 = '<span class="'.$entryType.'" name="';
    $sep2 = '>';
     $para = " " . $para . " ";
    while ( $pos = strpos($para, $sep1) )
    {
        $pos_fin = strpos($para, $sep2, $pos);
        $id_concept = substr($para, $pos+strlen($sep1), $pos_fin-$pos-strlen($sep1)-1);
        if (!in_array($id_concept, $tab_id_concept))
        {
            $tab_id_concept[] = (int)$id_concept;
        }
        $para = substr($para, $pos_fin+1);
    } // while
    return $tab_id_concept;
}

function wordExistsAsFlexion( $word, $language )
{
    $params = array( ' word'=> $word, 'language'=>$language);
    $list =  ElexRemoteService::call( 'wordExistsAsFlexion', $params );
    return $list;
    /*$flexionList = array();
    foreach( $list as $listObject  )
    {
        $flexionList[] = $listObject->value ;
    }
    return $flexionList; */
}

function get_lemmaCatList_from_ff( $word, $language )
{
    $params = array('word'=>$word, 'language'=>$language);
    $list =  ElexRemoteService::call( 'getLemmaCatListFromFlexion', $params );
    $lemmaList = array();
    foreach( $list as $listObject  )
    {
        $lemmaList[] = $listObject->value ;
    }
    return $lemmaList;
}

function returnListOfWordsInText($myText, $language)
{
	increaseScriptTime();
	$paragraphs = explode ("\n", $myText);
	 $words = array();
	foreach ($paragraphs as $para)
	{
		$params = array( 'text'=> $para, 'language'=>$language);
    	$wordList = ElexRemoteService::call( 'returnListOfWordsInText', $params );
    	foreach( $wordList as $word )
	    {
	    	if (!in_array(utf8_decode($word->value), $words))
	    	{
	        	$words[] = utf8_decode($word->value) ;
	    	}
	    }
	}
	return $words;
	/** old one -> takes too much time for long text
    $params = array( 'text'=> $myText, 'language'=>$language);
    $wordList  = ElexRemoteService::call( 'returnListOfWordsInText', $params );
    $words = array();
    foreach( $wordList as $word )
    {
        $words[] = utf8_decode($word->value) ;
    }
    return $words;*/
}

function explodeArrayOnKey ($array, $key)
{
    $tab = array();
    foreach ($array as $key2=>$arrayValue)
    {
        $tab[$arrayValue[$key]] = $arrayValue;
    }
    return $tab;
}

function elex_list_concepts_in_text( $textId, $entryType )
{
    $conceptIdList = array();
    if (!in_array( $entryType, getValidEntryTypes() ) )
    {
        return false;
    }
    if ($textId != 0)
    {
        $text = new ElexTextWrapper();
        $text->setTextId( $textId );
        $text->load();
        $text->loadRemoteData();
        $titre = $text->getTitle();
        $content = $text->getContent();
        $conceptIdList =  getConceptListFromPara ($titre, $entryType);
        foreach ($content as $i=>$element)
        {
           $conceptIdList =  getConceptListFromPara ($element['content'], $entryType, $conceptIdList);
        }
    }
    else
    {
        $collectionList = getRegisteredCollections();
        foreach( $collectionList as $collection )
        {
             $textList = loadTextList( $collection['id_collection'], 'visible' );
             foreach( $textList as $text )
             {
                $tab_id_concept2 = elex_list_concepts_in_text( $text['id_text'], $entryType );
                foreach ($tab_id_concept2 as $id_concept)
                {
                    if (!in_array($id_concept,$conceptIdList))
                    {
                        $conceptIdList[] = $id_concept;
                    }
                }
            }
        }
    }
    return $conceptIdList;
}