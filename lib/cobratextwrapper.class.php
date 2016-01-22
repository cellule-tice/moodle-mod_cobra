<?php // $Id: elextextwrapper.class.php 267 2011-01-13 10:15:11Z jmeuriss $
class COBRATextWrapper
{
    //locally stored data (Claroline plugin
    private $id = 0;
    private $textId = 0;
    private $collectionId = 0;
    private $position = 1;
    private $visibility = true;

    //previously remotely stored data (E-Lex system)
    private $type = 'Lesson';
     //remotely stored data (E-Lex system)
    private $title = '';
    private $content = array();
    private $source = '';

    public function __construct( $id = 0 )
    {
        $this->setId( $id );
    }

    public function setId( $id )
    {
        $this->id = $id;
    }

    public function getId()
    {
        return (int)$this->id;
    }

    public function setTextId( $id )
    {
        $this->textId = $id;
    }

    public function getTextId()
    {
        return (int)$this->textId;
    }

    public function setCollectionId( $id )
    {
        $this->collectionId = $id;
    }

    public function getCollectionId()
    {
        return (int)$this->collectionId;
    }

    public function set_position( $index )
    {
        $this->position = (int)$index;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function set_visibility( $value )
    {
        $this->visibility = $value;
    }

    public function isVisible()
    {
        return true === $this->visibility ? true : false;
    }

    public function setType( $type )
    {
        $this->type = '' != $type ? $type : 'Lesson';
    }

    public function getType()
    {
        return $this->type;
    }

    public function setTitle( $title )
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent( $content )
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    function setSource( $source )
    {
        $this->source = $source;
    }

    function getSource()
    {
        return $this->source;
    }

    public function load()
    {
        global $DB,$course;
        if( !$this->getTextId() ) return false;
        $text = $DB->get_record_select('cobra_texts_config', "course='$course->id' AND id_text= ".$this->getTextId() );
            
        $this->setId( $text->id );
        $this->setTextId( $text->id_text );
        $this->setCollectionId( $text->id_collection );
        $this->setType( $text->text_type );
        $this->set_position( $text->position );
        $this->set_visibility( $text->visibility ? true : false );
        return true;
    }

    public function loadRemoteData()
    {
        $params = array( 'id_text' => (int)$this->getTextId() );
        $jsonObj = CobraRemoteService::call( 'loadTextData', $params );

        $this->setSource( utf8_decode( $jsonObj->source ) );
        $this->setTitle( utf8_decode( $jsonObj->title ) );
        $content = array();
        foreach( $jsonObj->content as $item )
        {
            $content[$item->num] = array( 'content' => utf8_decode( $item->content ) );
        }
        $this->setContent( $content );
        return true;
    }

    public function formatHtml()
    {
        $params = array( 'id_text' => (int)$this->getTextId() );
        $html = CobraRemoteService::call( 'getFormattedText', $params );
        return utf8_encode($html);
    }

    public function getAudioFileUrl()
    {
        $params = array( 'id_text' => (int)$this->getTextId() );
        $url = ElexRemoteService::call( 'getAudioFileUrl', $params );
        return utf8_decode( $url );
    }

    public function save()
    {
        global $DB,$course;
        if( $this->getId() )
        {
            $visibility =  ( true === $this->isVisible() ? '1' : '0' ) ;
            $dataObject = new  stdClass();
            $dataObject->id=$this->getiId();
            $dataObject->course=$course->id;
            $dataObject->id_text=$this->getTextId();
            $dataObject->id_collection=$this->getCollectionId();
            $dataObject->text_type=$this->getType();
            $dataObject->position=$this->getPosition();
            $dataObject->visibility= $visibility;
            return  $DB->update_record('cobra_texts_config', $dataObject) ;
           
        }
        else
        {
            
            $visibility =  ( true === $this->isVisible() ? '1' : '0' ) ;
            $dataObject = new  stdClass();
            $dataObject->course=$course->id;
            $dataObject->id_text=$this->getTextId();
            $dataObject->id_collection=$this->getCollectionId();
            $dataObject->text_type=$this->getType();
            $dataObject->position=$this->getPosition();
            $dataObject->visibility= $visibility;
            return  $DB->insert_record('cobra_texts_config', $dataObject) ;

        }
    }

    public function remove()
    {        
        global $DB,$course;
        return $DB->delete_records('cobra_text_config', array('course'=>$course->id, 'id'=>$this->getId()));      
    }

    public static function getMaxPosition()
    {
        $moduleTbl = get_module_course_tbl( array( 'elex_texts_config' ), claro_get_current_course_id() );
        $query = "SELECT MAX(`position`) AS `pos` FROM `" . $moduleTbl['elex_texts_config'] . "`";
        $result = Claroline::getDatabase()->query( $query );
        if( $item = $result->fetch() )
        {
            return $item['pos'];
        }
        else
        {
            return 0;
        }
    }
}