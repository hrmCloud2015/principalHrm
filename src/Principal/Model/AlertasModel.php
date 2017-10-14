<?php
/*
 * STANDAR DE NISSI MENUES
 * 
 */
namespace Principal\Model;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\AdapterAwareInterface;

use Principal\Model\LogFunc; // Traer datos de session activa y datos del pc 


class Alertas extends AbstractTableGateway
    implements AdapterAwareInterface
{
    protected $table = 'c_alertas';
    
    public function setDbAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new HydratingResultSet();
 
        $this->initialize();
    }
    // Alertas 
    public function getAlertas()
    {     
      $t = new LogFunc($this->adapter);
      $dt = $t->getDatLog();      

      if ($dt['admin']==1)
         $con = "select * from c_mu";      
      else // Usuario no administrador
         $con = "select distinct c.nombre, c.id from c_roles a 
                 inner join c_roles_o b on b.idRol=a.id
                 inner join c_mu c on c.id=b.idM1 
                 inner join users d on d.idRol=a.id where d.id=".$dt['idUsu'];  
         
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;                        
    }
}

   
 
