<?php
/*
 * STANDAR DE NISSI : PARAMETROS DE NOMINA
 * 
 */
namespace Principal\Model;
 
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
 
/// INDICE

// Consulta a tabla de parametro de nominas 1 valor
// Consulta a tabla de parametro de nominas mas de 1 valor

class Paranomina extends AbstractTableGateway
{
   protected $table  = '';
   
   
   public $dbAdapter;
    
   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
   }   
                       
   // Consulta a tabla de parametro de nominas 1 valor
   public function getGeneral($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }               
   // Consulta a tabla de parametro de nominas mas de 1 valor
   public function getGeneral1($id)
   {
       
      $result=$this->adapter->query("select * from c_nomina where id=".$id,Adapter::QUERY_MODE_EXECUTE);
      //$datos=$result->toArray();
      $datos = $result->current();
      return $datos;
    }                   
    
}
?>