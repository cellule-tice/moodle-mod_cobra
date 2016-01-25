<?php /* $Id: glossary.lib.php 283 2011-09-01 07:47:03Z ldumorti $ */


function export_glossary ($glossary)
{
    global $CFG;
     require_once($CFG->libdir . '/csvlib.class.php');

    $filename = clean_filename(get_string('glossary', 'cobra'));

    $csvexport = new csv_export_writer('semicolon');
    $csvexport->set_filename($filename);
    $records = array();
    $records[0] = array(get_string('Lemma_form','cobra'), get_string('Category','cobra'), get_string('info','cobra'), get_string('Translation','cobra'), get_string('Text','cobra'));
        list( $lemmaGlossary, $expGlossary ) = explode_glossary_into_lemmas_and_expression( $glossary );
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

        foreach ($lemmaGlossary as $key=>$entry)
        {
             // $this->recordList is defined in parent class csv
             $newRecord = array($entry["entry"], $entry["category"] , $entry["ss_cat"], $entry["traduction"]);
             if (!in_array($newRecord, $records))
             {
            	$records[]= array($entry["entry"], $entry["category"] , $entry["ss_cat"], $entry["traduction"], $entry['title']);
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
            	$records[] = array($entry["entry"], $entry["category"] , '', $entry["traduction"], $entry['title']);
            }
        }
        foreach ($records as $record)
        {
            $csvexport->add_data($record);
        }
     
    $csvexport->download_file();
    die;
}


/**
 * @param $textId integer
 * @return $glossary array
 */

function get_glossary_for_text ( $textId )
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
        $conceptIdList = cobra_list_concepts_in_text( $textId, $entryType );
        foreach ($conceptIdList as $conceptId)
        {
            if (!in_array($conceptId, $oldConceptList))
            {
                $params = array('conceptId'=>$conceptId, 'entryType'=>$entryType);
                $entityLingId = CobraRemoteService::call( 'getEntityLingIdFromConcept', $params );
                $glossary[$textId][] = $entityLingId;
                $oldConceptList[] = $conceptId;
            }
        }
    }
    return $glossary;
}


function cobra_list_concepts_in_text( $textId, $entryType )
{
    $conceptIdList = array();
    if (!in_array( $entryType, get_valid_entry_types() ) )
    {
        return false;
    }
    if ($textId != 0)
    {
        $text = new CobraTextWrapper();
       
        $text->setTextId( $textId );
        $text->loadRemoteData();
        $titre = $text->getTitle();
        $content = $text->getContent();
        $conceptIdList =  get_concept_list_from_para ($titre, $entryType);
        foreach ($content as $i=>$element)
        {
           $conceptIdList =  get_concept_list_from_para ($element['content'], $entryType, $conceptIdList);
        }
    }
    else
    {
        $collectionList = get_registered_collections();
        foreach( $collectionList as $collection )
        {
             $textList = load_text_list( $collection['id_collection'], 'visible' );
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


function  get_concept_list_from_para( $para, $entryType, $tab_id_concept = array() )
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



/**
 * @param $glossary : array
 * @param text : class Text
 * @return @glossary : array
 */
function get_glossary_entry_of_text( $glossary, $text, $num )
{
    $textId= $text->id_text;
    $tab_glossary = array();
    foreach ($glossary as $key=>$entiteLingId)
    {
        $params = array('id_entite_ling'=>$entiteLingId);
        $tab_glossary[$key] =  CobraRemoteService::call( 'getGlossaryInfoForEntityLing', $params );
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
        $glossary[$key]['title'] = strip_tags( $text->title );
    }
    return $glossary;
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



function word_exists_as_flexion( $word, $language )
{
    $params = array( ' word'=> $word, 'language'=>$language);
    $list =  CobraRemoteService::call( 'wordExistsAsFlexion', $params );
    return $list;
}

function get_lemmaCatList_from_ff( $word, $language )
{
    $params = array('word'=>$word, 'language'=>$language);
    $list =  CobraRemoteService::call( 'getLemmaCatListFromFlexion', $params );
    $lemmaList = array();
    foreach( $list as $listObject  )
    {
        $lemmaList[] = $listObject->value ;
    }
    return $lemmaList;
}

function return_list_of_words_in_text($myText, $language)
{
	increaseScriptTime();
	$paragraphs = explode ("\n", $myText);
	 $words = array();
	foreach ($paragraphs as $para)
	{
            $params = array( 'text'=> $para, 'language'=>$language);
            $wordList = CobraRemoteService::call( 'returnListOfWordsInText', $params );
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
    $wordList  = ElexRemoteService::call( 'return_list_of_words_in_text', $params );
    $words = array();
    foreach( $wordList as $word )
    {
        $words[] = utf8_decode($word->value) ;
    }
    return $words;*/
}

function explode_array_on_key ($array, $key)
{
    $tab = array();
    foreach ($array as $key2=>$arrayValue)
    {
        $tab[$arrayValue[$key]] = $arrayValue;
    }
    return $tab;
}



function explode_glossary_into_lemmas_and_expression ( $glossary )
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