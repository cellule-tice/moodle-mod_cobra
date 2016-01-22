<?php // $Id: elexcollectionwrapper.class.php 253 2011-01-03 15:22:00Z jmeuriss $
class CobraCollectionWrapper
{
    private $id = 0;
    private $language = '';
    private $remoteName = '';
    private $localName = '';
    private $position = 1;
    private $visibility = true;

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
    
    public function setLanguage( $language )
    {
        $this->language = $language;
    }
    
    public function getLanguage()
    {
        return $this->language;
    }
    
    public function setRemoteName( $name )
    {
        $this->remoteName = $name;
    }
    
    public function getRemoteName()
    {
        return $this->remoteName;   
    }
    
    public function setLocalName( $name )
    {
        $this->localName = $name;
    }
    
    public function getLocalName()
    {
        return $this->localName;
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
    
    public function load()
    {
        global $DB,$course;
        if( !$this->getId() ) return false;
        
        $list = $DB->get_records_select('cobra_registered_collections', "course='$course->id' AND id_collection='".(int)$this->getId()."'");
       
        if( empty($list)) return false;

        foreach ($list as $collection)
        {            

            $this->setRemoteName( $collection->label );
            $this->setLocalName( $collection->local_label );
            $this->set_position( $collection->position );
            $this->set_visibility( $collection->visibility ? true : false );
            return true;      
        }
    }
    
    public function save()
    {
        global $DB,$course;
        $list = $DB->get_records_select('cobra_registered_collections', "course='$course->id' AND id_collection='".(int)$this->getId()."'");
        if (!empty($list))
        {
            return $this->update();
        }
        
        $visibility =  ( true === $this->isVisible() ? '1' : '0' ) ;
        $dataObject = new stdClass();
        $dataObject->course = $course->id;
        $dataObject->id_collection= $this->getId();
        $dataObject->label = $this->getRemoteName();
        $dataObject->local_label = $this->getLocalName();
        $dataObject->position = $this->getPosition();
        $dataObject->visibility = $visibility;
      
        if ($DB->insert_record('cobra_registered_collections', $dataObject) )
        {
            return 'saved';
        }
        else 
        {
            return 'error';
        }
    }
    
    public function update()
    {
        global $DB,$course;               
        if( $this->getId() )
        {
            $visibility =  ( true === $this->isVisible() ? '1' : '0' ) ;
            $dataObject = new stdClass();
            $dataObject->id = $this->getId();
            $dataObject->course = $course->id;
            $dataObject->id_collection= $this->getId();
            $dataObject->label = $this->getRemoteName();
            $dataObject->local_label = $this->getLocalName();
            $dataObject->position = $this->getPosition();
            $dataObject->visibility = $visibility;

            if ($DB->update_record('cobra_registered_collections', $dataObject) )
            {
                return 'updated';
            }
            else 
            {
                return 'error';
            }
        }
        else 
        {
            return false;
        }
    }
    
    public function wrapRemote( $remoteId )
    {
        $params = array( 'id_collection' => (int)$remoteId );
        $remoteCollection = CobraRemoteService::call( 'getCollection', $params );
    
        $this->setId( $remoteId );
        $this->setLanguage( $remoteCollection->language );
        $this->setRemoteName( $remoteCollection->label ) ;
        $this->setLocalName(  $remoteCollection->label ) ;
        $this->set_position( self::getMaxPosition() +1 );
        return true;
    }
    
    public function remove()
    {        
       global $DB,$course;
       return $DB->delete_records('cobra_registered_collections',array('course'=>$course->id, 'id_collection'=>$this->getId()));        
    }
    
    public static function getMaxPosition()
    {
        global $DB,$course;
        $list = $DB->get_records_select('cobra_registered_collections', "course='$course->id'",null,'position DESC', 'POSITION');
        if (!empty($list))
        {
            foreach ($list as $elt)
            {
                $value = $elt->position;   
                return $value;
            }
        }
        else 
        {
            return '0';
        }
    }            

}