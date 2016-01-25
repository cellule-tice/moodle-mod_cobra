<?php

/**
 * @param $textId integer
 * @return $glossary array
 * @todo : used in myglossary ?
 */
function get_glossary_table_entries( $textId='0' )
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



/*
 * 
 * @todo : used in myGlossary ?
 */

function build_glossary ()
{
    set_time_limit(0);
    $moduleTbl = get_module_course_tbl( array( 'elex_glossaire' ), claro_get_current_course_id() );
    $query = "TRUNCATE TABLE `" . $moduleTbl['elex_glossaire'] . "`";
    Claroline::getDatabase()->exec( $query );
    $collectionList = get_registered_collections('visible');
    foreach( $collectionList as $collection )
    {
        $textList = load_text_list( $collection['id_collection'], 'visible' );
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


/*
 * 
 * @todo : used in myGlossary ?
 */
function build_glossary_text( $textId, $entryType )
{
    $moduleTbl = get_module_course_tbl( array( 'elex_glossaire' ), claro_get_current_course_id() );
    $conceptIdList = cobra_list_concepts_in_text($textId, $entryType);
    foreach ($conceptIdList as $conceptId)
    {
        $params = array('conceptId'=>$conceptId, 'entryType'=>$entryType);
        $entityLingId = CobraRemoteService::call( 'getEntityLingIdFromConcept', $params );
        if (!insertGlossaryEntry($textId,$entityLingId))
        {
            $console->pushMessage('Problem in glossary construction');
            return false;
        }
    }
    return true;
}
