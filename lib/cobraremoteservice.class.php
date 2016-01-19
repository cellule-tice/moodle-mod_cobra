<?php // $Id: elexremoteservice.class.php 249 2011-01-03 11:12:41Z jmeuriss $
class CobraRemoteService
{
    public static function call( $serviceName, $params = array(), $returnType = 'json' )
    {
        try 
        {
            $validReturnTypes = array( 'html', 'object', 'objectList', 'string', 'integer', 'boolean', 'error' );
           /* serveur tice
            *  $url = 'http://tice.det.fundp.ac.be/cobra/services/service_handler.php';
            * $params['caller'] = 'Moodle';
            */
            $url = 'http://tice.det.fundp.ac.be/cobra/services/service_handler.php';
             $params['caller'] = '';
        if( sizeof( $params ) )
        {
            $queryString = http_build_query( $params,'', '&' );
        }
        //echo '<br> requete = ' . $url . '?verb=' . $serviceName . '&' . htmlentities($queryString, ENT_COMPAT, 'ISO-8859-1') ;
        if( !$response = cobra_http_request( $url . '?verb=' . $serviceName . '&' . $queryString ) )
        {
            throw new Exception( 'Unable to access required URL' . $url );            
        }
         /* echo '<br> response = ';
        var_dump($response);*/
        $response = json_decode( $response );
      
        if( !in_array( $response->responseType, $validReturnTypes ) )
        {
            throw new Exception( get_string( 'Unhandled return type' ) . '&nbsp;:&nbsp;' . $response->responseType );
        }
        if( 'error' == $response->responseType )
        {
            throw new Exception( get_string( utf8_decode( $response->content ) ) );
        }
        elseif( 'html' == $response->responseType )
        {
            return utf8_decode( $response->content );
        }
        else
        {
            return $response->content;
        }
        }
        catch( Exception $e )
        {
            echo $e->getMessage() ;
        }
    }
}