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


class MenuTable extends AbstractTableGateway
    implements AdapterAwareInterface
{
    protected $table = 'c_mu';
    
    public function setDbAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new HydratingResultSet();
 
        $this->initialize();
    }
    // Menu principal
    public function getMenu()
    {     
     // echo 'acaaa';
      $t = new LogFunc($this->adapter);
      $dt = $t->getDatLog(); 
     // print_r($dt);
      if ($dt['admin']==1)
         $con = "select * from c_mu where vista = 0 order by orden";      
      else // Usuario no administrador
         $con = "select distinct c.nombre, c.id
             from c_roles a 
                 inner join c_roles_o b on b.idRol=a.id
                 inner join c_mu c on c.id = b.idM 
                 inner join users d on d.idRol=a.id 
                 where c.vista =0 and d.id=".$dt['idUsu']." order by c.orden";  
         
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      //print_r($datos);
      return $datos;                        
    }
    // Menu 1 nivel 
    public function getMenu1($id)
    {
      $t = new LogFunc($this->adapter);
      $dt = $t->getDatLog();      
//echo 'menu 1 '.$id.'<br />';
      if ($dt['admin']==1)
         $con = "select * from c_mu1 where idM=".$id;      
      else // Usuario no administrador
         $con = "select distinct e.* 
              from c_roles a 
                 inner join c_roles_o b on b.idRol=a.id
                 inner join c_mu c on c.id=b.idM 
                 inner join c_mu1 e on e.idM=c.id and e.id=b.idM1  
                 inner join users d on d.idRol=a.id 
                 where d.id=".$dt['idUsu']." and c.id=".$id;        
      
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;                        
    }
    // Menu 2 nivel 
    public function getMenu2($id)
    {
      $t = new LogFunc($this->adapter);
      $dt = $t->getDatLog();      
//echo 'menu 2 '.$id.'<br />';
      if ($dt['admin']==1)
         $con = "select * from c_mu2 where idM1=".$id;      
      else // Usuario no administrador
         $con = "select distinct f.*  
              from c_roles a 
                 inner join c_roles_o b on b.idRol=a.id
                 inner join c_mu c on c.id=b.idM 
                 inner join c_mu1 e on e.idM=c.id and e.id=b.idM1 
                 inner join c_mu2 f on f.idM1=e.id and f.id=b.idM2
                 inner join users d on d.idRol=a.id 
                 where d.id=".$dt['idUsu']." and e.id=".$id;        
      
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);     

      $datos=$result->toArray();
      return $datos;                        
    }    
    // Menu 3 nivel opciones
    public function getMenu3($id)
    {
      $t = new LogFunc($this->adapter);
      $dt = $t->getDatLog();      
//echo 'menu 3 '.$id.'<br />';
      if ($dt['admin']==1)
         $con = "select * from c_mu3 where idM2=".$id ;      
      else // Usuario no administrador
         $con = "select distinct g.*  
               from c_roles a 
                 inner join c_roles_o b on b.idRol=a.id
                 inner join c_mu c on c.id=b.idM 
                 inner join c_mu1 e on e.idM=c.id and e.id=b.idM1 
                 inner join c_mu2 f on f.idM1=e.id and f.id=b.idM2
                 inner join c_mu3 g on g.idM2 = f.id and g.id = b.idM3                  
                 inner join users d on d.idRol=a.id 
                 where d.id=".$dt['idUsu']." and f.id=".$id;        
      
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);     

      $datos=$result->toArray();
      return $datos;                        
    }        
}

   
 
