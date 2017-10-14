<?php
/*
 * STANDAR DE NISSI FUNCIONES PRINCIPALES
 * 
 */
namespace Principal\Model;


use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Principal\Model\AlbumTable; 

use Presupuestos\Model\Entity\CotizaD; // (C) Guardado de materiales
use Presupuestos\Model\Entity\CotizaS; // (C) Guardado de servicios
use Presupuestos\Model\Entity\CotizaM; // (C) Guardado de materiales adicionales

/// INDICE
// Calculo precio producto
// Extraccion de variables
// Filtros de materiales en sistemas
// Guardado ficha tecnica 

class Funciones extends AbstractTableGateway
{
   protected $table  = '';   
   
   public $dbAdapter;
    
   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
   }   
   // Calculo precio producto
   public function getPrecios($idDis, $idCot, $idCoti)
   {
      
      $u = new AlbumTable($this->adapter);
      $datosV      = $u->getVarCot($idDis,$idCot,$idCoti );   // Variables con valor en cotizaciones
      $datosVcom   = $u->getVarComp($idDis);          // Variables de los componetnes del sistema en el diseño
      $datVari     = $u->getVarTip2Dis($idDis);       // Variables internas dentro de diseños tipo 2            
      $datosMat    = $u->getMatDise($idDis,$idCoti);          // Materiales del sistema
      $datosVmat   = $u->getMatDiseV($idDis,$idCot,$idCoti);  // Materiales de los vidrios
      $datosVmatA  = $u->getMatDiseVm2($idCoti);      // Materiales adjuntos al vidrio 
      $datosMatFor = $u->getMatForm($idCoti);         // Materiales desde la formula en el diseño
      $datosSerFor = $u->getSerDis($idCoti);          // Servicios en diseños               
      $datosAdiCom = $u->getComDis($idCoti);          // Componentes adicionales en disenos              
      //// ------------------------------------------------------------------------------------
      // VARIABLES INICIALES
      // ------------------------------------------------------------------------------------
      //print_r($datosV);
      for ($i=1;$i<=3;$i++)
      { // VARIABLES GUARDADAS
        if (!empty($datosV['var'.$i]) ) 
        {
          $str='$'.$datosV['var'.$i].'='.$datosV['valVar'.$i];
          // echo $str.' <br />';
          eval("\$str =$str;");   
        }
      }
      foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
       // echo $str.' <br />';
        eval("\$str =$str;"); 
        if ($dat_f['variables']!='') // Variables del sistema
        {
           $str = $dat_f['variables'];
           eval("\$str =$str;");
        }        
      }
      foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
       // echo $str.' <br />';
        eval("\$str =$str;");   
      }
      // ------------------------------------------------------------------------------------
      // RECORRER Y CALCULAR PRECIO --------------------------------------------------------
      // ------------------------------------------------------------------------------------
      $costoMat = 0;// Materiales de diseños
      foreach ($datosMat as $dat_f){ 
        $str=$dat_f['formMat'];
        //echo $str.' - ';
        eval("\$str =$str;");        
        $val = $str * $dat_f['precio1']*$dat_f["canMdis"]*$dat_f["canMcom"];        
        //echo $str.' <br />';
        $costoMat = $costoMat + $val; // Costo de materiales        
      }
      $costoV = 0;// Vidrios
      foreach ($datosVmat as $dat_f){ 
        $str=$dat_f['formVid'];
        //echo $str.' - ';
        eval("\$str =$str;");        
        $val = $str * $dat_f['precio1'] * $dat_f['canVid'];        
        //echo $str.' <br />';
        $costoV = $costoV + $val; // Costo de materiales        
      }      
      //
      $costoVm = 0;// Vidrios materiales       
      foreach ($datosVmatA as $dat_f){ 
        $str=$dat_f['formMv'];
        if ($str=='$perimetro')
            $str = $dat_f['formPer'] ;                          
        //echo $str.' - ';
        eval("\$str =$str;");        
        $val = $str * $dat_f['precio1'] * $dat_f['canCom'];
        //echo $val.' <br />';
        $costoVm = $costoVm + $val; // Costo de materiales
      }      
      //     
      $costoOm = 0;// Materiales desde la formula
      foreach ($datosMatFor as $dat_f){ 
        $str=$dat_f['formFfm'];
        //echo $str.' - ';
        eval("\$str =$str;");      
        $val = $str * $dat_f['precio1'];
        //echo $str.' <br />';
        $costoOm = $costoOm + $val; // Costo de materiales
      }      
      //
      $costoSm = 0;// Servicios en las formulas
      foreach ($datosSerFor as $dat_f){ 
        $str=$dat_f['formSer'];
        //echo $str.' - ';
        eval("\$str =$str;");        
        //echo $str.' <br />';
        $val = $dat_f['precio1'] * $str;
        $costoSm = $costoSm + $val; // Costo de materiales
      }
      //
      $costoCa = 0;// Componenes adicionales
      foreach ($datosAdiCom as $dat_f){ 
        $str=$dat_f['formMat'];
        //echo $str.' - ';
        eval("\$str =$str;");        
        //echo $str.' <br />';
        $val = $dat_f['precio1'] * $str;
        $costoCa = $costoCa + $val; // Costo de materiales
      }
      //      
      $costos = array( "costoMat"     => $costoMat, 
                       "costoMatV"    => $costoV,
                       "costoMatVa"   => $costoVm,      
                       "costoMatFor"  => $costoOm,                
                       "costoSerFor"  => $costoSm,                
                       "costoCamFor"  => $costoCa,                          
          );
      return $costos;
   }                          
   // Extraccion de variables
   public function getVariables($idSis,$tipo)
   {   
      $u = new AlbumTable($this->adapter);
      $datos = $u->getDefVar($idSis);   // Definicion de variabels segun sistema
              $variablesg=array(); 
              $variablesv=array(); 
              $variablesi='';// Variables internas
              $permitidos = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
              $y=1;
              foreach ($datos as $dato){ 
                $cadena=ltrim($dato['formula']);
   	        // Buscar variables  	
	        $var = '';
                for( $i=0; $i<=strlen($cadena); $i++)
                {
		   $caracter = substr(ltrim($cadena),$i,1);
                   if (strstr($permitidos, $caracter ))
		   { 
                      $var .=$caracter;			
                   }else{                    
		      // Buscar nombre de la variable                       
                      $dv = $u->getGeneral1("select * from c_variables where tipo=1 and variable='".$var."'");                       
   		      if ($dv['nombre']!='')
			{
		          if (!(in_array($var, $variablesg)) )
		          {
                              $idc=$dv['id']; 
                              if (!(in_array($idc, $variablesg)) )
                              {
                                $variablesg[$y]= $idc;                                                            
                                $variablesv[$y]= $dv['nombre']; // Variable que se piden al cotizar                                                            
                                $i++;$y++;
                              }
                          }// Fin si variable 
                        } // Fin valdiar en blanco
                        $var = ''; 
                   } // Valdiacion caracteres
                }// Fin recorrido de cadenas              
              } // Fin recorrido            
       //$datos = array( "variablesg" => $variablesg, "variablesv" => $variablesv );
       if ($tipo==1)   // Id de variable    
          return $variablesg;
       else // Nombres de las variables
          return $variablesv; 
   }
   
   // Extraccion de variables segun cotizaciones
   public function getVariablesC($idSis,$tipo,$idDis,$tipCom )
   {   
      $u = new AlbumTable($this->adapter);
      $datos = $u->getDefVarC($idSis ,$idDis, $tipCom);   // Definicion de variabels segun sistema
              $variablesg=array(); 
              $variablesv=array(); 
              $variablese=array(); 
              $variablesf=array(); 
              $variableso=array(); 
              
              $variablesi='';// Variables internas
              $permitidos = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
              $y=1;
              foreach ($datos as $dato){ 
                $cadena=ltrim($dato['formula']);
   	        // Buscar variables  	
	        $var = '';
                for( $i=0; $i<=strlen($cadena); $i++)
                {
		   $caracter = substr(ltrim($cadena),$i,1);
                   if (strstr($permitidos, $caracter ))
		   { 
                      $var .=$caracter;			
                   }else{                    
		      // Buscar nombre de la variable                       
                      $dv = $u->getGeneral1("select * from c_variables where tipo=1 and variable='".$var."' order by orden");                       
   		      if ($dv['nombre']!='')
			{
		          if (!(in_array($var, $variablesg)) )
		          {
                              $idc=$dv['id']; 
                              if (!(in_array($idc, $variablesg)) )
                              {
                                $variablesg[$dv['id']]= $idc;                                                            
                                $variablesv[$dv['id']]= $dv['nombre']; // Nombre de la variables
                                $variablese[$dv['id']]= $dv['opcion']; // Estado de la variable
                                $variablesf[$dv['id']]= $dv['variable']; // Formula de variable
                                $variableso[$dv['id']]= $dv['orden']; // Ordenes de variable    
                                $i++;$y++;
                              }
                          }// Fin si variable 
                        } // Fin valdiar en blanco
                        $var = ''; 
                   } // Valdiacion caracteres
                }// Fin recorrido de cadenas              
              } // Fin recorrido            
       //$datos = array( "variablesg" => $variablesg, "variablesv" => $variablesv );              
       ksort($variableso);
//       print_r($variablesg);
       switch ($tipo) {
           case 1:
               return $variablesg; // Id de las variables
               break;
           case 2:
               return $variablesv; // Nombre de las variables
               break;
           case 3:
               return $variablese; // Estado de las variables (Requerida / opcional)
               break;           
           case 4:
               return $variablesf; // Nombre de la formula 
               break;                      
           case 5:
               return $variableso; // Orden de la formula 
               break;                                 
           default:
               break;
       }      

   }
   // Filtros de materiales en sistemas  
   public function getFiltrosMat( $idIsis, $tipo, $origen )
   {   
      // 1: Extraer desde el sistema
      // 2: Extraer desde cotizacion
      // 3: Extraer la ordend e produccion
      // Origen : 1: de sistemas y 2: es de la cotizacion
       
      $d = new AlbumTable($this->adapter); 
      switch ($origen) {
          case 1:
             $datos = $d->getGeneral("select distinct c.idLin, c.idGrup 
                from c_sistemas_c a 
                inner join c_sistemas b on b.id = a.idSis
                inner join c_grupos_lm c on c.idGrup = b.idGrup where a.id=".$idIsis);         
              break;
          case 2:
             $datos = $d->getGeneral("select d.idLin, e.idLin as idLinA 
                            from c_cotizaciones_i a 
                            inner join c_cotizaciones_s b on a.idCotS = b.id
                            inner join c_sistemas c on c.id = b.idSis
                            inner join c_grupos_lm d on d.idGrup = c.idGrup 
                            left join c_grupos_mc e on e.idGrup = c.idGrup 
                            where a.id = ".$idIsis);                  
              break;
          case 3:
             $datos = $d->getGeneral("Select distinct f.idLin 
                   from c_pre_ordenes a
                   inner join c_pre_ordenes_i_d b on b.idPord = a.id 
                   inner join c_cotizaciones_i c on c.id = b.idIcot 
                   inner join c_cotizaciones_s d on d.id = c.idCotS
                   inner join c_sistemas e on e.id = d.idSis
                   inner join c_grupos_lm f on f.idGrup = e.idGrup  
                   where a.id =".$idIsis);         
              break;          
          default:
              break;
      }

      // Armar filtro
      $filLin = '';
      $idGrup = 0;
      foreach ($datos as $dat){
         if ($filLin=='')
         {
            $filLin = "'".$dat['idLin']."'" ;
         }else{
            $filLin = $filLin.",'".$dat['idLin']."'" ;
         }
         if ($origen==1)
            $idGrup = $dat['idGrup'];
      }
      
      
      if ($origen==2)// Sumar adicionales      
      {
        foreach ($datos as $dat){
            if ($filLin=='')
            {
               $filLin = "'".$dat['idLinA']."'" ;
            }else{
               $filLin = $filLin.",'".$dat['idLinA']."'" ;
            }
        }          
      }
      if ( $filLin != '')
         $filLin = " where idLin in (".$filLin.")";       
      //echo $filLin;
      if ($tipo==0)
         return $idGrup; 
      else
         return $filLin;     
   }   
   // Despiece de materiales por ordenes
   public function getDespiece($id, $tipo)
   {
        $d = new AlbumTable($this->adapter); 
        $datVarG = $d->getVariables(""); // Variables generales
        
        /// DESPIECE POR PIEZAS LINEALES Y UNIDADES
        // TODAS LAS VARIABLES         
        foreach ($datVarG as $dat_f)
        { 
            $str='$'.$dat_f['variable'].'=0';
            eval("\$str =$str;");  
        }        

        /// -******----------------------------///
        //******** DESPIECE POR CONSUMO *******////        
        /// ----------------------------///-----****        
        ini_set('max_execution_time', 3000); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        switch ($tipo) {
            case 1: // Materiales de la base
                $datos = $d->getDespieceM($id, " and g.tipo=1 and ii.despTotal = 0 and ( m.ancho > 0 and m.alto = 0 )"
                        . "and ( kkk.idPres=0 or kkk.idPres is null or kkk.idPres = c.idPres ) 
                         and ( ( q.id is null and k.idOri = 0 ) or ( q.idOri = k.idOri )  )  and  m.optimizar=0 # Mostrar solo los materiales que tenga orientacion 
                         group by m.codMat ");
                break;
            case 2: // Materiales adicionales del componente
                $datos = $d->getDespieceMca($id);
                break;
            case 3: // Materiales por recubrimientos
                $datos = $d->getDespieceMre2($id); # NUeva formula para verificar fallos
                break;            
            case 4: // Materiales adjuntos a los recubrimientos
                $datos = $d->getDespieceMreM($id, " and ( i.ancho > 0.001 and i.alto = 0 ) ");
                break;                        
            case 5: // Materiales adicionales en items de cotizaciones
                $datos = $d->getMateAdic($id, " and ( d.ancho>0 and d.alto=0 ) ");
                break;                                        
            default:
                break;
        }
           //$d->modGeneral("update  c_pre_ordenes_despiece set cantMat = 0 where idPord=".$id); // Poner todo en 0 		
          foreach ($datos as $datM)
          {         
            $codMat  = $datM['CodMat'];           		
			      if ( $datM['descar'] != -8989899999 ) // Que no tenga material descargado es la forma para recalcular 
            {
			// Validar 
            $ancho   = $datM['ancho']; // Ancho de la pieza
                        
            if (  $tipo == 3  ) // Recubrimientos 
            {
               $cantOrd  = $datM['cantOrd']; 
               $cantEle  = 1;
               $ancho    = 0;
               $desCorte = 0;
               //$med      = round( $datM['medida'], 3 );
               $med      = $datM['medida'];
               // Verificar que el mismo material con la misma medida no se guarde 2 veces
               $datDes = $d->getGeneral1("select id  
                                           from c_pre_ordenes_despiece 
                                           where idPord=".$id." 
                                           and tipo=".$tipo." and codMat='".$codMat."' and cantProd=".$cantOrd." and round(medida,2)=".$med);
               //print_r($datDes);
               if ($datDes['id']==0) // Se modifica el registro
               { 
                  // Guardar datos en la tabla de despieces de la orden de produccion
                  $d->modGeneral("insert into c_pre_ordenes_despiece "
                          . "(idPord, codMat, medida, cantProd, cantEle, cantReq, limite, desCorte, componente, tipo )"
                        . " values(".$id.",'".$codMat."',".$med.",".$cantOrd.",".$cantEle.", ".$med." ,".$ancho.", ".$desCorte.",'Recubrimiento' , ".$tipo.")");
               }else{
                  $d->modGeneral("update c_pre_ordenes_despiece set cantReq = ".$med." where idPord=".$id." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd." and replace(medida,',','.')=".$med);                
               }            

            }else{// Manejo de materiales con optimizacion 
               
              $nomLin  = $datM['nomLin']; // Linea de materiales
              $existen = $datM['existen']; // Existencia actual            
              $desCorte = $datM['desCorte'];// Desperdicio en corte
              $cantOrd  = 0;
              //echo $codMat.'<br />'; 
              // Buscar materiales asociados  
              $matVal = array(); // Medidas del material
              // Valor de variables de materiales en diferentes items de la orden
              $idIpd = 0;

              $datos = $d->getDespiece($id, $codMat, $tipo);  // Buscar tiems que contienen material activo ------------------------------           
              //if ($tipo==5)
                //  print_r($datos);
              //if (empty($datos))            
              foreach ($datos as $dato)
              {
                 $cantOrd = $dato['cantProd'] * $dato['cantMat'] ;// Cantidad a descargar
                 $nomComp = $dato['nomComp'] ;// Componente del material
                 if ($dato['desp'] > 0) // Se toma el desperdicio en el sistema por encima del del material
                    $desCorte = $dato['desp'];// Desperdicio en corte por sistema
                 $cantidad = $dato['cantidad']; // Cantidad del elemento

                 if ( $idIpd != $dato['idIprod'] ) // SE IDENTIDICA EL ITEMS DE LA ORDEN PARA CARGAR VALORES DE VARIABLES
                 {
                    $idIpd = $dato['idIprod'];              
                    $cantidad = $dato['cantidad']; // Cantidad del elemento
                    $datosP = $d->getVarProdI($idIpd); // VARIABLES Y VALOR EN LAS ORDENES
                    for ($i=1;$i<=10;$i++)
                    {               
                        if ( ($datosP[ 'var'.$i] != null) and ($datosP[ 'var'.$i] != ' ') )
                        {
                            $str = '$'.$datosP[ 'var'.$i].'='.$datosP[ 'valVar'.$i];
                         //   echo $str.' : ';                            
                            eval("\$str =$str;");     
                          //  echo $str.'<br />';
                        }
                    }
                    $datos = $d->getGeneral1("select b.idDis from c_pre_ordenes_i_d a 
                                inner join c_cotizaciones_i b on b.id = a.idIcot 
                                where a.id = ".$idIpd);            
                    $idDis    = $datos['idDis'];      

                    // VARIABLE DE LOS COMPONENTES
                    $datosVcom = $d->getVarComp($datos['idDis']); // Variables de los componetnes del sistema en el diseño
////                    if ($tipo==4)
    //                    print_r($datosVcom);                                                                                
                    foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
//                          echo $str.' : ';                        
                          eval("\$str =$str;");   
    //                       echo $str.'<br />';  
                           if ($dat_f['variables']!='') // Variables del sistema
                           {
                               $str = $dat_f['variables'];
                               eval("\$str =$str;");
                           }                                                 
                    }  
                    // VARIABLES INTERNAS DEL DISEÑO
                    $datVari = $d->getVarTip2Dis($datos['idDis']);
                    foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
                       // Validacion en variables
                       $swVal = 0; // Validacion
                       //echo $dat_f['validacion'].'<br />';
                       if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
                       {
                           $val = trim($dat_f['validacion']);  
                           eval(
                            'if (!('.$val.')){'.
                               '$swVal=1;'.
                            '}');               
                        }    
                        if ($swVal==0)
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        else // Toma el si no de la condicion
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];   

  //                      echo 'Hoja O: '.$hojaO.' Hoja X: '.$hojaX;    
//$hojaX=1;
//           echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                   

                        $var = '$hojaX'; 
                        $pos = strpos($str, $var);
                        //echo 'd'.$pos; 
                        if ( $pos > 0 )
                           if ( ($hojaX==0) or ($hojaX=='') )
                              $hojaX = 1; 
                           
                        eval("\$str =$str;");   
                    }                                     
                  } // FIN CAMBIO DE DISEÑO ITEMS DE ORDEN DE PRODUCCION -------------------------------***********************
                  //***********************************************************************------------------******************
                
                  // --- VALIDAR SI EL MATERIAL VA O NO VA
                  $swVal = 0;
                  if ( ($dato['validacion']!='') and ($dato['validacion']!=NULL) )
                  {
                     $val = trim($dato['validacion']);  
                     eval(
                       'if (!('.$val.')){'.
                        '$swVal=1;'.
                     '}');       
//                    if ( $codMat == 'ROY61436' )
  //                     echo ' val ---'.$codMat.' : '.$str.' : '.$med.' <br />';                                                
                  }                                       
                  if ($swVal == 0)
                  { 
                    // REGISTRO EN TABLA DE DESPIECE
                    $str = $dato['formEle'];// Medida de la pieza     
                    $pos = strpos($dato['formEle'], "perimetro");
                    if ($pos>0)
                    {                
                      $str = str_replace('$perimetro','('.$dato['formPer'].')',$str);                      
                      //echo ' FORMULA PERIMETRO: '.$str.' <br />';                    
                    }
                    $pos = strpos($dato['formEle'], "area");
                    if ($pos>0)
                    {                
                      $str = str_replace('$area','('.$dato['formArea'].')',$str);                      
                      //echo ' FORMULA AREA: '.$str.' <br />';                    
                    }
                    // La formula especial reemplaza a todas las anteriores 
                    $valEsp = 0;
                    if (ltrim($dato['formEspe']) != '')
	                  {
                       $str = '('.$dato['formEle'].') * ('.$dato['formEspe'].')';  
                       //$str = ' ('.$dato['formEspe'].')';  
                       $strEsp = $dato['formEspe'];  
                       eval("\$valEsp =$strEsp;");

                       //echo $codMat.': FORMULA ESPECIAL: '.$str.' : '.$valEsp.'<br />';                                               
	                  }                   
                    $med = 0;
                    // Empanada momentena para eviar division entre 0  -----
                    $sw=0;  
                    $var = '$cv'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                           $sw=1;
                    if ( $sw==0 ) 
                    {
                       $var = '$ch'; 
                       $pos = strpos($str, $var);
                      // echo 'd'.$pos; 
                       if ( $pos > 0 )
                          if ( $ch == 0)
                              $sw=1;
                    }
                    $var = '$v'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                           $sw=1;
                    $var = '$hojaO'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $hojaO == 0)
                           $sw=1;                    

                    if ( $str == '|' )
                        $sw = 1;
                    // Fin empanada momentena para eviar division entre 0 
                       // echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                                         
                    if ( $sw==0 ) 
                    {
                       eval("\$med =$str;");
                    }

                    $cantEle = $cantidad;
                    if ( $med > 0 )
                    {
                       // REGISTRO EN TABLA DE DESPIECE        
                       // Verificar que el mismo material con la misma medida no se guarde 2 veces
                      $datDes = $d->getGeneral1("select id  
                                           from c_pre_ordenes_despiece 
                                           where idPord=".$id." 
                                           and tipo=".$tipo." and codMat='".$codMat."' and cantProd=".$cantOrd." and replace(medida,',','.')=".$med);
//                      echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                   
                       if ($datDes['id']>0) // Se modifica el registro
                       {
                          // Se modifican las cantidades del material con la misma medida en el despiece
                          $d->modGeneral("update c_pre_ordenes_despiece set cantMat=cantMat+1 where id=".$datDes['id']);                                                           
                       }else{
                         // Guardar datos en la tabla de despieces de la orden de produccion
//                        echo $id.' - '.$codMat.' - '.$med.'- '.$valEsp.'<br />';
                         $d->modGeneral("insert into c_pre_ordenes_despiece "
                            . "(idPord, codMat, medida, medEsp, cantProd, cantEle, limite, desCorte, formula, componente, tipo )"
                           . " values(".$id.",'".$codMat."', ".$med.",".$valEsp.",".$cantOrd.",".$cantEle.",".$ancho.", ".$desCorte.",'".$str."', '".$nomComp."', ".$tipo." )");                                       
                       }
                     }// Valdiacion medida mayor que cero 
                  }// Fin validacion inclucion del material 
                /////---- FIN GUARDAR REGISTRO DEL DESPIECE --------------------------           
                }// Fin validacion especial para recubrimientos 
            }// FIN RECORRIDO MATERIAL PARA DESPIECE  
            
          }// FIN RECORRIDO MATERIALES DE LA OBRA                
        
          ///------------------------------------------------- ////
          /////---- RECORRIDO DE MATERIALES PARA DESPIEZAR --------------------------
          ///--------------------------------------------------////        
          $datDes = $d->getGeneral("select * 
                                  from c_pre_ordenes_despiece 
                                       where idPord=".$id."  
                                       and tipo=".$tipo." and limite > 0 order by codMat, medida desc"); // Completo
          $matMatT = '';
          $medMatT = '';
          $marMatT = ''; // Marcar material para ubicar su pieza
          $i = 1;
          foreach($datDes as $dat) // Se sacan todas las cantidades de piezas de la orden de produccion y se guarda en una matriz
          {                
            
            $cant = $dat['cantMat']*$dat['cantEle']*$dat['cantProd'];
            //if ( $dat['codMat'] == 'H54' )
               //echo $dat['codMat'].' '.$dat['medEsp'].' '.$cant.' <br />' ;

            for ($z = 1; $z <= $cant; $z++)
            {
                $matMatT[$i] = $dat['codMat'];
                if ( $dat['medEsp'] > 0) // Formula especial                 
                   $medMatT[$i] = $dat['medida']*$dat['medEsp']; 
                else   
                   $medMatT[$i] = $dat['medida']; 

                $marMatT[$i] = 0;
                $i++;
            }
         }   
         // Materiales para optimizacion de materiales 
         $datDes = $d->getGeneral("select * 
                                  from c_pre_ordenes_despiece 
                                       where idPord=".$id." and tipo=".$tipo." and limite > 0  
                                       group by codMat 
                                       order by codMat, medida desc"); // Codigos aagrupado        
         foreach($datDes as $dat) // Se sacan todas las cantidades de piezas de la orden de produccion y se guarda en una matriz
         {                
            $idD    = $dat['id'];
            $codMat = $dat['codMat'];
            $lim    = $dat['limite'];            
            $cant   = $dat['cantProd']; 
            $desCorte = $dat['desCorte']; ;// Desperdicio en corte
            $i=1;
            $unidades = 0; // Unidades requeridas de un material                       
            $distri   = ''; 
            $distriT   = ''; 
            // REALIZAR CALCULOS PARA OPTIMIZACION DE MATERIAL
              //if ( $codMat == 'T167N') // Pruebas 
              //    echo 'COD '.$codMat.'<br />';  

              for ($i=1; $i <= (count($matMatT)) ; $i++)           
              {   

                //echo '--'.$matMatT[$i].'-'.$medMatT[$i].' marca '.$marMatT[$i].'<br />';  
                if ( ( $codMat == $matMatT[$i] ) and ($marMatT[$i]==0) )    
                {                    
                    //if ( $matMatT[$i] == 'T167N') // PRuebas 
                      // echo '--'.$medMatT[$i].'-'.$medMatT[$i].'<br />';
                    $sumMed = 0; // Suma de dedidas
                    $swUni = 0;            
                    // Recorrer para armar 1 pieza ----------------------------------------------        
                    for ($y=1; $y <= (count($matMatT)) ; $y++) 
                    {      
                        if ( ( $codMat == $matMatT[$y]  ) and ($marMatT[$y]==0) )
                        {                        
                           //if ( $matMatT[$i] == 'ROY50136A')  // Prueba 
                              //echo '---Mrca :'.$medMatT[$y].' corte:'..'<br />'; 

                           $medMat = $medMatT[$y] + $desCorte;
                           
                           if ( ($sumMed + $medMat ) <= $lim ) // Si es menor que el limite de la pieza
                           {
                               $sumMed = $sumMed + $medMat ;
                               $marMatT[$y]=1; // Marcar pieza 
                               $swUni = 1; // Activar unidad
                               $distri = $distri.$medMat.'+';
                           }else{ 
                               if ( ( $medMat ) > $lim ) // Cuando la cantidad sea mayor que la pieza tiene se divide pára obtener lo requerido
                               {
                                  $marMatT[$y]=1; // Marcar pieza  
                                  $unidades = $unidades + ( $medMat / $lim );                              
                                  //$distriP = $distriP.$medMat.'+';
                               }  
                           }                          
                        }          
                    } // Fin Recorrer para armar 1 pieza ----------------------------------------------
                    if ( $swUni == 1)
                    {                           
                        $unidades++;                    
                        $distriT = $distriT.' ('.$distri.')'  ;
                        $distri   = ''; 
                    }

                }// FIn validacion no este marcada la pieza
              }// Fin validacion manejo de limite en medida   
              // GUARDAR REQUERIMIENTO DE UNIDADES            
              $d->modGeneral("update c_pre_ordenes_despiece 
                   Set cantReq = ".$unidades.", distribucion='".$distriT."' where id = ".$idD);                                                                   
            } // Fin validacion si tuvo no descargue de materiales 
        } // Finr ecorrido materiales
       
       /// -******--------------------------------///
       //******** FIN DESPIECE POR CONSUMO *******////        
       /// -------------------------------///-----****       
       
       
       
       
       /// -******--------------------------------///
       //******** DESPIECE POR UNIDAD *******////        
       /// -------------------------------///-----****       

        switch ($tipo) {
            case 1: // Materiales de la base
                $datos = $d->getDespieceM($id, " and g.tipo=1
                 and ( ( m.ancho=0 and m.alto=0 ) or ( ii.despTotal = 1) ) # Despiece incluyendo cantidad*cantDesp*formula  
                 and ( k.idPres=0 or k.idPres is null or k.idPres = c.idPres )
                         and ( ( q.id is null and k.idOri = 0 ) or ( q.idOri = k.idOri )  ) or m.optimizar=1 # Mostrar solo los materiales que tenga orientacion 
                         group by m.codMat ");
                break;
            case 2: // Materiales adicionales del componente
                $datos = $d->getDespieceMcaU($id);
                break;
            case 4: // Materiales adjuntos a los recubrimientos
                $datos = $d->getDespieceMreM($id, " and ( i.ancho=0 and i.alto=0 ) ");
                break;                        
            case 5: // Materiales adicionales por items
                $datos = $d->getMateAdic($id, " and ( d.ancho=0 and d.alto=0 ) ");
                break;                                        
            default:
                break;
        }       
        if ($tipo != 3) // Diferente de vidrios
        {
          //print_r($datos);
          foreach ($datos as $datM)
          {         
            $codMat  = $datM['CodMat'];
            if ( $datM['descar'] != 797979790 ) // Que no tenga material descargado es la forma para recalcular 
            {

            //echo $codMat.'<br />'; 
            // Buscar materiales asociados  
            // Valor de variables de materiales en diferentes items de la orden
            $idIpd = 0;
            $datos = $d->getDespieceU($id, $codMat, $tipo);  // Buscar tiems que contienen material activo            
            //if (empty($datos))            
            foreach ($datos as $dato)
            {
                $cantOrd = $dato['cantProd'] * $dato['cantMat'] ;// Cantidad a descargar
                $nomComp = $dato['nomComp'] ;// Componente del material              
                $limite  = $dato['ancho'] ;// Componente del material              
                $med = '';
                $unidades = $dato['cantMat'] * $dato['cantProd'];                     
                $cantMat  = $dato['cantMat']; // Cantidad del material                
                $cantEle = 1;
                $valEsp = 0;
                if ( $idIpd != $dato['idIprod'] ) // SE IDENTIDICA EL ITEMS DE LA ORDEN PARA CARGAR VALORES DE VARIABLES
                {
                    $idIpd    = $dato['idIprod'];              
                    $cantEle = 1;
                    
                    $datosP = $d->getVarProdI($idIpd); // VARIABLES Y VALOR EN LAS ORDENES
                    for ($i=1;$i<=10;$i++)
                    {               
                        if ( ($datosP[ 'var'.$i] != null) and ($datosP[ 'var'.$i] != ' ') )
                        {
                            $str = '$'.$datosP[ 'var'.$i].'='.$datosP[ 'valVar'.$i];
                            eval("\$str =$str;");     
                        }
                    }
                    $datos = $d->getGeneral1("select b.idDis 
                              from c_pre_ordenes_i_d a 
                                inner join c_cotizaciones_i b on b.id = a.idIcot 
                                where a.id = ".$idIpd);            
                    $idDis    = $datos['idDis'];      

                    // VARIABLE DE LOS COMPONENTES
                    $datosVcom = $d->getVarComp($datos['idDis']); // Variables de los componetnes del sistema en el diseño
                    //if ($tipo==4)
//                        print_r($datosVcom);                                                                                
                    foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        //  echo $str.'<br />';                        
                        eval("\$str =$str;");   
                        if ($dat_f['variables']!='') 
                        {
                           $variables = $dat_f['variables'];
                           eval("\$str =$variables;");                          
                        }    
                            //echo $str.'<br />';                        
                    }  
                    // VARIABLES INTERNAS DEL DISEÑO
                    $datVari = $d->getVarTip2Dis($datos['idDis']);
                    foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
                       // Validacion en variables
                       $swVal = 0; // Validacion
                       //echo $dat_f['validacion'].'<br />';
                       if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
                       {
                           $val = trim($dat_f['validacion']);  
                           eval(
                            'if (!('.$val.')){'.
                               '$swVal=1;'.
                            '}');               
                        }    
                        if ($swVal==0)
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        else // Toma el si no de la condicion
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];   
                        // Empanada     
                        $sw=0;  
                        $var = '$hojaX'; 
                        $pos = strpos($str, $var);
                        if ( $pos > 0 )
                           if ( $hojaX == 0)
                                $sw=1;
                        // fin empanada     
                        if ($sw==0)      
                           eval("\$str =$str;");   
                    }                                     
                } // FIN CAMBIO DE DISEÑO ITEMS DE ORDEN DE PRODUCCION -------------------------------
                
                // --- VALIDAR SI EL MATERIAL VA O NO VA
                $swVal = 0;
                if ( ($dato['validacion']!='') and ($dato['validacion']!=NULL) )
                {
                   $val = trim($dato['validacion']);  
                   eval(
                      'if (!('.$val.')){'.
                        '$swVal=1;'.
                   '}');               
                }                     
             $valEsp = 0;
                if ($swVal == 0)
                { 
                   // REGISTRO EN TABLA DE DESPIECE
                   $str = $dato['formEle'];// Medida de la pieza     
                   $pos = strpos($dato['formEle'], "perimetro");
                   if ($pos>0)
                   {                
                      $str = str_replace('$perimetro','('.$dato['formPer'].')',$str);                      
                   }
                   $pos = strpos($dato['formEle'], "area");
                   if ($pos>0)
                   {                
                      $str = str_replace('$area','('.$dato['formArea'].')',$str);                      
                   }
                    // La formula especial reemplaza a todas las anteriores                    
                    if (ltrim($dato['formEspe']) != '')
                    {
                       //$str = '('.$dato['formEle'].') * ('.$dato['formEspe'].')';  
                       //$str = ' ('.$dato['formEspe'].')';  
                       $strEsp = $dato['formEspe'];  
                       eval("\$valEsp =$strEsp;");
                       if ( $valEsp > 0 )
                            $valEsp = $valEsp ;
                       else 
                            $valEsp = 0;    
                       //echo $codMat.': FORMULA ESPECIAL: '.$str.' : '.$valEsp.'<br />';                                               
                    }                   

                   $cantMat  = $dato['cantMat']; // Cantidad del material
                   $cantProd = $dato['cantProd']; // Cantidad a producir
                    
                    $med = 0;

                    // Empanada momentena para eviar division entre 0  -----
                    $sw=0;  
                    $var = '$cv'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                       {
                           $sw=1;
                       //   $str = str_replace('$cv','1',$str);                      
                       }

                    if ( $sw==0 ) 
                    {
                       $var = '$ch'; 
                       $pos = strpos($str, $var);
                       //echo 'd'.$pos; 
                       if ( $pos > 0 )
                          if ( $ch == 0)
                              $sw=1;

                    }
                       //if ($codMat=='STAC')
                          //echo $codMat.' : '.$str.' : '.$med.' <br />';                   
                    // Fin empanada momentena para eviar division entre 0 
                    if ( $sw==0 ) 
                    {
                       eval("\$med =$str;");
                    }

                   if ( $valEsp > 0)
                      $unidades = $dato['cantMat'] * $dato['cantProd'] * $med * $valEsp ;                    
                   else
                      $unidades = $dato['cantMat'] * $dato['cantProd'] * $med;                     

                   $despTotal = $dato['despTotal'];  
                   if ( ( $dato['despTotal'] == 1) and ( $dato['ancho'] > 0 ) ) // Despiece total se divide entre el tamaño del perfil si lo tuviera 
                      $unidades = $unidades / $dato['ancho'];
                   //if ( $codMat == 'T167N')
                       //echo $dato['cantMat'].' '.$med.'<br />';

//                   echo $med.' <br />';                   
                   // DESPIECE DETALLADO------------------------------------------------------
                   // Verificar que el mismo material con la misma medida no se guarde 2 veces
                   $datDes = $d->getGeneral1("select id  
                                           from c_pre_ordenes_despiece_d 
                                           where idPord=".$id." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd
                                            ." and replace(medida,',','.')=".$med." and idIpord=".$idIpd  );
                   if ($datDes['id']>0) // Se modifica el registro
                   {
                     //                  if ( $codMat == 'T10X11/2IX')
                       //echo $unidades.'<br />';                    
                       // Se modifican las cantidades del material con la misma medida en el despiece
                       $d->modGeneral("update c_pre_ordenes_despiece_d 
                                set cantReq  = cantReq  + ".$unidades.", 
                                    cantMat  = cantMat  + ".$cantMat." 
                                    where id = ".$datDes['id']);
                   }else{
                       // Guardar datos en la tabla de despieces de la orden de produccion

//echo 'valor a insertar'.$id.", ".$idIpd." ,'".$codMat."',".$med.",".$cantOrd.",".$cantEle.",".$cantMat.'<br />';

                       $d->modGeneral("insert into c_pre_ordenes_despiece_d 
                       (idPord, idIpord , codMat, medida,  cantProd, cantEle, cantMat, limite, desCorte, cantReq )
                       values(".$id.", ".$idIpd." ,'".$codMat."',".$med.",".$cantOrd.",".$cantEle.",".$cantMat.",0,0, ".$unidades." )");
                    }                    
                   //************-----------------------------------------------------------
                    
                   // DEPIECE TOTAL ----------------------------------------------------------
                   // Verificar que el mismo material con la misma medida no se guarde 2 veces
                   $datDes = $d->getGeneral1("select id  
                                           from c_pre_ordenes_despiece 
                                           where idPord=".$id." 
                                           and tipo=".$tipo." and codMat='".$codMat."'");
                   if ($datDes['id']>0) // Se modifica el registro
                   {
                        //               if ( $codMat == 'T10X11/2IX')
                      // echo $datDes['id'].' = '.$unidades.'<br />';
                       // Se modifican las cantidades del material con la misma medida en el despiece
                       $d->modGeneral("update c_pre_ordenes_despiece
                                set cantReq  = cantReq  + ".$unidades.", 
                                    cantMat  = cantMat  + ".$cantMat."  
                                    where id = ".$datDes['id']);
                   }else{
                       // Guardar datos en la tabla de despieces de la orden de produccion
//                    echo $id." codmat ".$codMat." med ".$med." medEsp".$valEsp." cantEle".$cantEle." cantMat ".$cantMat." limite".$limite." un ".$unidades." str ".$str." comp".$nomComp.'<br />';
                       $d->modGeneral("insert into c_pre_ordenes_despiece 
                       (idPord, codMat, medida, medEsp ,cantEle, cantMat, limite, desCorte, cantReq, formula, componente, tipo  )
                       values(".$id.",'".$codMat."',".$med.",".$valEsp." ,".$cantEle.",".$cantMat.",".$limite.",0, ".$unidades." ,'".$str."', '".$nomComp."', ".$tipo." )"); 
                    }                    
                    //************-----------------------------------------------------------
                    
                }// Fin validacion inclucion del material 
                /////---- FIN GUARDAR REGISTRO DEL DESPIECE --------------------------           
              }// FIN VALIDACION QUE NO TENGA DESCRGUES
            }// FIN RECORRIDO MATERIAL PARA DESPIECE  
          }// FIN RECORRIDO MATERIALES DE LA OBRA                       
        }
       /// -******--------------------------------///
       //******** FIN DESPIECE POR UNIDAD *******////        
       /// -------------------------------///-----****          
       
   }   
   // Campos de una tabla 
   public function getTablaCam($tabla, $ignorar)
   {
       $d = new AlbumTable($this->adapter); 
       $datos = $d->getGeneral("SHOW COLUMNS FROM ".$tabla );        
       $cam = '';
       foreach($datos as $dat)
       {
          if ($cam == '') 
             $cam = $dat['Field'];     
          else
             $cam = $cam.','.$dat['Field'];               
       }       
       //echo $cam;
       // Buscar campos a ignorar 
       $cam = str_replace("id,", "", $cam);

       return $cam;
   }   
   // Clonar cotizacion (Pendiente por errores logicos en id)
   public function getCopCoti($id)
   {
       $f = new AlbumTable($this->adapter); 
       
        $campos = $this->getTablaCam("c_cotizaciones", "id"); // Extraer listado de campos                     
            $f->modGeneral("insert into c_cotizaciones (".$campos.") "
                                    . " select ".$campos." from c_cotizaciones where id =".$id);                     
        $dat = $f->getGeneral1("SELECT LAST_INSERT_ID() as id ;");
        $idCot = $dat['id'];

        // Linea de cotizacion        
        $campos = $this->getTablaCam("c_cotizaciones_l", "id"); // Extraer listado de campos                             
        $camposV = str_replace("idCot,", "'".$idCot."',", $campos);        
                     $f->modGeneral("insert into c_cotizaciones_l (".$campos.") "
                                    . "( select ".$camposV." from c_cotizaciones_l where idCot = ".$id.")");                                                  
        $dat = $f->getGeneral1("SELECT LAST_INSERT_ID() as id ;");
        $idCotL = $dat['id'];
        
        // Sistemas en cotizacion                     
        $campos = $this->getTablaCam("c_cotizaciones_s", "id"); // Extraer listado de campos                                          
        $camposV = str_replace("idCot,", "'".$idCot."',", $campos);                
        $camposV = str_replace("idCotL,", "'".$idCotL."',", $camposV);                
                     $f->modGeneral("insert into c_cotizaciones_s (".$campos.") "
                                    . " select ".$campos." from c_cotizaciones_s where idCot = ".$id);                                                               
        
        // Items en cotizacion
        $campos = $this->getTablaCam("c_cotizaciones_i", "id"); // Extraer listado de campos                                          
                     $f->modGeneral("insert into c_cotizaciones_i (".$campos.") "
                                    . " select ".$campos." from c_cotizaciones_i where idCot = ".$id);   
                     
        // Sistemas en cotizacion                     
        $campos = $this->getTablaCam("c_cotizaciones_ica", "id"); // Extraer listado de campos                                          
                     $f->modGeneral("insert into c_cotizaciones_ica (".$campos.") " 
                                    . " select ".$campos." from c_cotizaciones_ica where idCot = ".$id); 
                     
        $campos = $this->getTablaCam("c_cotizaciones_ica_v", "id"); // Extraer listado de campos                                          
                     $f->modGeneral("insert into c_cotizaciones_ica_v (".$campos.") " 
                                    . " select ".$campos." from c_cotizaciones_ica_v where idCot = ".$id);
                     
        $campos = $this->getTablaCam("c_cotizaciones_ico", "id"); // Extraer listado de campos                                          
                     $f->modGeneral("insert into c_cotizaciones_ico (".$campos.") " 
                                    . " select ".$campos." from c_cotizaciones_ico where idCot = ".$id);                                                                                                         
                     
        $campos = $this->getTablaCam("c_cotizaciones_ima", "id"); // Extraer listado de campos                                          
                     $f->modGeneral("insert into c_cotizaciones_ima (".$campos.") " 
                                    . " select ".$campos." from c_cotizaciones_ima where idCot = ".$id); 
                     
        $campos = $this->getTablaCam("c_cotizaciones_o", "id"); // Extraer listado de campos                                          
                     $f->modGeneral("insert into c_cotizaciones_o (".$campos.") " 
                                    . " select ".$campos." from c_cotizaciones_o where idCot = ".$id);                                                                                                                              
                     
        $campos = $this->getTablaCam("c_cotizaciones_r", "id"); // Extraer listado de campos                                          
                     $f->modGeneral("insert into c_cotizaciones_r (".$campos.") " 
                                    . " select ".$campos." from c_cotizaciones_r where idCot = ".$id);                                                                                                                                                   

       return $cam;
   }      
   
   
   // Guardado ficha tecnica
   public function getFichaTecnica( $id )
   {   
       // $tipo : 0 = nuevo , 1 = eliminar
       $f = new Funciones($this->adapter);        
       $u = new AlbumTable($this->adapter);      
       
               $datos = $u->getGeneral1("select a.idDis, a.idCot, b.idSis, a.idPres  
                                from c_cotizaciones_i a 
                                inner join c_cotizaciones_s b on b.id=a.idCotS   
                                where a.id=".$id);  

               $idSis  = $datos['idSis'];
               $idDis  = $datos['idDis'];
               $idCot  = $datos['idCot'];
               $idPres = $datos['idPres'];

               $datCot    = $u->getDatCCot($idCot); // Datos de la cabecera                     
               $datIcot   = $u->getDatICot($id);// Datos informacion item y cotiza 
               $datosM = $u->getDatCotM($id);// Datos de margenes
               $datCal = $u->getCalculos(" where variable != ''"); // Calculos
               $datLis      = $u->getListas(""); // Listas
               $datos = $u->getProC(" where c.id=".$idSis." and (jj.idPres=".$idPres.
                                         " or jj.idPres=0 or jj.idPres is null) and ( jjj.idOri=0 or jjj.idOri is null ) ", $id, $idSis);// Materiales del en diseño   
//print_r($datos);
               $datosOri    = $u->getProCori($id, $idSis, $idPres);// Materiales del en diseño             
               $datosA      = $u->getProCa(" where c.id=".$idSis, $id );// Componentes del sistema               
               $datosV      = $u->getVarCot( $idDis , $idCot ,$id ); // Variables con valor en cotizaciones          
               $datosVO     = $u->getVarCotO( $idDis , $idCot ,$id ); // Variables con valor en componentes opcionales cotizaciones
               $datosVcom   = $u->getVarComp( $idDis ); // Variables de los componetnes del sistema en el diseño
               $datVari     = $u->getVarTip2Dis( $idDis ); // Variables internas dentro de diseños tipo 2
               $datRecu     = $u->getRecubrimiento($id, $idPres); // Recubrimiento
               $datMatA     = $u->getMatAdiCoti($id); // Materiales adicionales
               $datSerA     = $u->getSerAdiCoti($id); // Servicios adicionales
               $datMatV     = $u->datMatV($id); // Materiales adjuntos al vidrio
               $datVarG     = $u->getVariables(""); // Variables generales
               // *-----------------------------------//
               // ----- CALCULOS ---------------------------
               // *-----------------------------------               
               $p = New CotizaD($this->adapter); // Funcion para materiales adicionales
               $s = New CotizaS($this->adapter); // Funcion para servicios adicionales
               $m = New CotizaM($this->adapter);               

                     
                     $i=1;
                     //print_r($datVarG);
                     foreach ($datVarG as $dat_f)// TODAS LAS VARIABLES 
                     { 
                         $str='$'.$dat_f['variable'].'=0';
                         eval("\$str =$str;");  
                     }
                     for ($i=1;$i<=10;$i++)// VARIABLES GUARDADAS EN LAS COTIZACIONES
                     { 
                         if (isset($datosV['var'.$i]) ) 
                            {    
                            if (!empty($datosV['var'.$i]) ) 
                            {
                                if ($datosV['valVar'.$i]>0)
                                {
                                    $str='$'.$datosV['var'.$i].'='.$datosV['valVar'.$i];
                                    eval("\$str =$str;");   
                                }
                           }
                        }  
                     }
                     for ($i=1;$i<=10;$i++)// VARIABLES GUARDADAS EN LOS COMPONENTES OPCIONALES EN LAS COTIZACIONES
                     { 
                        if (isset($datosVO['var'.$i]) ) 
                        {    
                            if (!empty($datosVO['var'.$i]) ) 
                            {
                                if ($datosVO['valVar'.$i]>0)
                                {
                                    $str='$'.$datosVO['var'.$i].'='.$datosVO['valVar'.$i];
                                    eval("\$str =$str;");   
                                }
                            }
                        }  
                    }
                    $formVol = 0;
                    $formAreaS = 0;
                    //print_r($datosVcom);
                    foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        eval("\$str =$str;"); 
                        $formVol = $dat_f['formVol']; // Formula del volumen
                        $formAreaS = $dat_f['formArea']; // Formula del area
                    }
                    foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
                        // Validacion en variables
                        $swVal = 0; // Validacion 
                        if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
                        {
                            $val = trim($dat_f['validacion']);  
                            eval(
                                'if (!('.$val.')){'.
                                    '$swVal=1;'.
                                '}');               
                        }    
                        if ($swVal==0)
                             $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        else // Toma el si no de la condicion
                             $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];          
                        eval("\$str =$str;");   
                    }
                    $pres    = $datIcot['nomPres'];
                    $porDescLinea = round($datIcot['porDesc'],2); // Descuento de la linea

                    $calculo = ''; // Calculo fin de documento
                    $por = 0; // Porcentaje lista items
                    $var = ''; // Variable lista
                    foreach ($datosM as $datM){ // Datos de calculos
                        $calculo = $datM['calculo']; // Calculo
                        $por = $datM['por']; // Porcentaje lista items
                        $var = $datM['variable']; // Variable lista
                    }                       
                    $total = 0;
                    $totalMat = 0; // materiales
                    $totalSer = 0; // Servicios
                    $totalMatO = 0; // materiales orientacion
                    $totalSerO = 0; // Servicios orientacion
                    $totalRec = 0; // Recubrimientos        
                    $totComponente = 0; // Total por componente
                    $totComponenteR = 0; // Total por componente recubrimiento
                    $totComponenteO = 0; // Total por componente orientacion o sentido
                    $adicionales = 0; // Total adicionales

                    $area  = 0; // Maneja/No maneja area para recubrimiento                        
                    $formArea = '';
                    $preArea  = 0;
                    $formPer  = 0;
                    $idEle = '';

                    $sw=0; 
                    $idComs = 0; // Sw para validar si el componente es diferente para totalizar

                    //$p->delete($id); // Borrar informacion del items materiales
                    $u->modGeneral("delete from c_cotizaciones_i_despiece where idIcot=".$id);
                    $u->modGeneral("delete from c_cotizaciones_i_matadi where idIcot=".$id);
                    $u->modGeneral("delete from c_cotizaciones_i_servicios where idIcot=".$id);
                    //$s->delete($id); // Borrar informacion del items servicios
                    //$m->delete($id); // Borrar informacion del items servicios    
    
                    foreach ($datosA as $dato){ ////// DATOS COMPONENTES PESTAÑAS                          
                        if ( ($dato['cantComp'] > 0) ) { // Importante ára validar existencia de componentes adicionales
                            $idCom  = $dato['idCom'];
                            $idPres = $dato['idPres'];

                            $cantCom = $dato['cantComp'];
                            if ( (ltrim($dato['formComp']) != '') and ( $dato['formComp'] != NULL ) )
	                    {
                                $formula = $dato['formComp'];  
                                eval("\$valor = $formula;");
                                $cantCom  = $cantCom * $valor;
	                    }             
                            if ($sw==0){ 
                                $sw=1; 
                            }else{ 
                                $clase='tab-pane';
                            }

                            $area  = 0; // Maneja/No maneja area para recubrimiento                        
                            $formArea = '';
                            $preArea  = 0;
                            $formPer  = 0;
                            $idEle = '';
                            //////-- DATOS ELEMENTOS Y MATERIALES DEL COMPONENTE --////
                            foreach ($datos as $dat){           
                                if ( ( $idCom==$dat['idCom'])) // FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE
                                {      
                                    $exclu = $dat['excluida'];
                                    if ($dat['formArea']!= NULL)
                                    {
                                        $area = 1;                        
                                        $formArea = $dat['formArea'];
                                        $preArea  = $dat['preArea']; // Este no va OJO
                                        $formPer  = $dat['formPer'];
                                    }
                                    // Elementos y materiales 
                                    $ele     = '';
                                    $imagen  = ''; // Imagen 
                                    if ( ( $idEle!=$dat['idFor'])) 
                                    {
                                        $idEle = $dat['idFor']; 
                                        $ele     = $dat['nomFor'];    
                                        $imagen  = $dat['icoEle']; // Imagen 
                                    }                           
                                    if ($dat['tipEle']==1)
                                    {
                                        $nombre  = $dat['CodMat'].' - '.$dat['nomMat'];
                                        $numero  = $dat['cantMat']*$cantCom; 
                                        $precio  = $dat['precio'];
                                    }else{// Servicios 
                                        $nombre  = $dat['idSer'].' - '.$dat['nomSer'];
                                        $numero  = $dat['canServ']*$cantCom; 
                                        $precio  = $dat['preServ'];                                   
                                    }                      
                                    $formula = $dat['formula'];          
                                    // Ejecutar formula 
                                    $forEle = '';
                                    $medEle = '';
                                    if ($formula != '')
	                            {
                                        $formula = $formula;  
                                        eval("\$valor = $formula;");
                                        $forEle = $formula;  
                                        $medEle = number_format($valor,3);
	                            }
                                    // Formula especial              
                                    $formMat = ' ';
                                    $medMat =  0;
                                    $valorE = 0;
                                    $swNv=0;// Para verificar que entre en formulas especiales
                                    if (ltrim($dat['formEspe']) != '')
	                            {
                                        $formula = $dat['formEspe'];  
                                        eval("\$valor = $formula;");
                                        $formMat = $formula;
                                        $medMat  = number_format($valor ,3); // Medida materiales
                                        $valorE = $valor; 
                                        $swNv=1;
	                            }  
                                    $porDesp = $dat['porDesp'];                            
                                    // Total medidas multiplicacion de formulas, pero sera parametrizable             
                                    if ($medMat>0)
                                    {
                                        $valor = ( $medEle * $valorE ) ;
                                    }else
                                    {
                                        $valor = ( $medEle ) ;
                                    }
                                    $swVal = 0; // Validacion 
                                    if ( ($dat['validacion']!='') and ($dat['validacion']!=NULL) )
                                    {
                                        $val = trim($dat['validacion']);  
                                        eval(
                                          'if (!('.$val.')){'.
                                             '$swVal=1;'.
                                           '}');               
                                    }
                                    // Validar que la medida del material sea mayor que cero para mostrar valor
                                    if ( ($medMat==0) and ($swNv==1) )
                                        $numero = 0;
              
                                    if ( ($dat['CodMat']!='') or ($dat['idSer']!='') ){                                  
                                       if ($swVal==0)
                                       {                   
                                           // GUARDAR DESPIECE
                                           $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );
                                           if ( $dat['porScosto'] > 0) // Sobre costo
                                           {
                                              $precio = $precio + ( $precio*($dat['porScosto']/100) ) ; 
                                           }
                                           $total = $precio * ( $valorU * $numero );                    
                                           if ($total>0)
                                           {
                                               if (($dat['CodMat']!='') )                        
                                               {
                                                   $p->actRegistro($idCot, $id, $idCom ,$dat['idIcom'], $dat['CodMat'], $medEle, $total,0,$dat['excluida'] );
                                                   $totalMat = $totalMat + ( $total );
                                               }
                                               if (($dat['idSer']!='') )                      
                                               {
                                                   $s->actRegistro($idCot, $id, $idCom ,$dat['idIcom'], $dat['idSer'], $medEle, $total,$dat['excluida'] ); 
                                                   $totalSer = $totalSer + $total;                     
                                               }
                                            }                      
                                            $totComponente = $totComponente + $total; // Total componente unidad
                                        }
                                    }
                                } // FIN FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE 
                            } ////// FIN DATOS ELEMENTOS Y MATERIALES ------------------------------------------------------------
                            ////// ORIENTACIONES O SENTIDOS ////
                            $idEle = '';
                            $sw = 0;
                            if ($sw==0)
                            { 
                                $sw=1  ;          
                            }  
                            //print_r($datosOri);
                            foreach ($datosOri as $dat){ ////// DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO-------------------------------------------------------                         
                                
                               if ( ( $idCom==$dat['idCom'])) // FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE
                               {
                                   // Elementos y materiales                                           
                                    $ele     = $dat['nomOri'];
                                    $imagen  = ''; // Imagen 
                                    if ( ( $idEle!=$dat['idFor'])) 
                                    {
                                       $idEle = $dat['idFor']; 
                                       $imagen  = $dat['icoEle']; // Imagen 
                                    }                            
                                    if ($dat['tipEle']==1)
                                    {
                                        $nombre  = $dat['CodMat'].' - '.$dat['nomMat'];
                                        $numero  = $dat['cantMat']*$cantCom; 
                                        $precio  = $dat['precio'];
                                    }else{// Servicios 
                                        $nombre  = $dat['idSer'].' - '.$dat['nomSer'];
                                        $numero  = $dat['canServ']*$cantCom; 
                                        $precio  = $dat['preServ'];                                   
                                    }                      
                                    $formula = $dat['formula'];          
                                    // Ejecutar formula 
                                    $forEle = '';
                                    $medEle = '';
                                    if ($formula != '')
	                            {
                                        $formula = $formula;  
                                        eval("\$valor = $formula;");
                                        $forEle = $formula;  
                                        $medEle = number_format($valor,3);
	                            }
                                    // Formula especial              
                                    $formMat = ' ';
                                    $medMat =  '';
                                    $valorE = 0; 
                                    if (ltrim($dat['formEspe']) != '')
	                            {
                                        $formula = $dat['formEspe'];  
                                        eval("\$valor = $formula;");
                                        $formMat = $formula;
                                        $medMat  = number_format($valor,3); // Medida materiales
                                        $valorE = $valor; 
	                            }               
                                    $porDesp = 0;
                                    // Total medidas multiplicacion de formulas, pero sera parametrizable             
                                    if ($medMat!='')
                                       $valor = ( $medEle * $valorE ) ;
                                    else
                                       $valor = ( $medEle ) ;
              
                                    $swVal = 0; // Validacion 
                                    if ( ($dat['validacion']!='') and ($dat['validacion']!=NULL) )
                                    {
                                        $val = trim($dat['validacion']);  
                                        eval(
                                            'if (!('.$val.')){'.
                                          '$swVal=1;'.
                                        '}');               
                                    }
                            
                                    if ( ($dat['CodMat']!='') or ($dat['idSer']!='') ){
                                       if ($swVal==0)
                                       {
                                           // GUARDAR DESPIECE
                                           $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                                           $total = $precio * ( $valorU * $numero );                    
                                           if ($total>0)
                                           {
                                               if (($dat['CodMat']!='') )                        
                                                   $p->actRegistro($idCot, $id, $idCom, $dat['idIcom'], $dat['CodMat'], $medEle, $total,0, 0);
                                               if (($dat['idSer']!='') )                       
                                                   $s->actRegistro($idCot, $id, $idCom ,$dat['idIcom'], $dat['idSer'], $medEle, $total, 0); 
                                            }                    
                                            if (($dat['CodMat']!='') )
                                                $totalMatO = $totalMatO + $total;
                                            if (($dat['idSer']!='') )
                                                $totalSerO = $totalSerO + $total;                      
                                            $totComponenteO = $totComponenteO + $total; // Total componente orientaciones o sentidos
                                        }                  
                                    }
                                }  // FIN FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE 
                            } ////// FIN DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO ------------------------------------------------------------
                            // RECUBRIMIENTOS ----------------------------------------------------------------------
                            $num = 0; 
                            $cantComR = 1;
                            // Materiales
                            if ($area==1)
                            {      
                                $componente = 'Recubrimiento';
                                foreach ($datRecu as $datMv){            
                                    // Cambio de numero de componente
                                    if ($idCom == $datMv['idCom'] )
                                    {
                                        $ele     = "";      
                                        $preArea  = $datMv['preVid']; // este si es 
                                        // Si tiene formula especial reeemplza la del padre
                                        if ( $datMv['formVid'] != '') 
                                           $formArea = $datMv['formVid'] ;                                        
                                        
                                        if  ($datMv['numero'] != $num )  
                                        {
                                            $num = $datMv['numero'];
                                            $ele = 'Componente '.$num;    
                                            // Vidrios
                                            $imagen  = ''; // Imagen 
                                            $nombre  = $datMv['codVid'].' - '.$datMv['nomVid'];               
                                            $numero  = $cantComR; 
                                            $precio  = $preArea;                                                                    
                                            $formula = $formArea;          
                                            $formMat = ' ';
                                            $medEle  = ' ';
                                            $medMat  = ' ';
                                            //$area      = $formArea;
                                            $perimetro = $formPer;
                                            //$espesor   = $dat['espesor'];
                                            // Ejecutar formula 
                                            $valor = 0;
                                            $forEle = '';
                                            $medEle = '';        
                                            if ($datMv['variables']!='') // Variables del sistema
                                            {
                                               $str = $datMv['variables'];
                                               eval("\$str =$str;");
                                               echo $str;
                                            }                                                                        
                                            if ($formula != '')
	                                    {
                                                $formula = $formula;  
                                                eval("\$valor = $formula;");
                                                $forEle = $formula;
                                                $medEle = $valor;
	                                    }       
                                            $porDesp = $datMv['porDespV'];
                                            $valor = ( $medEle ) ;
                                            // GUARDAR DESPIECE
                                            $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                                            $total = $precio * ( $valorU * $numero );                    
                                            if ($total>0)
                                                $p->actRegistro($idCot, $id, $idCom, 0, $datMv['codVid'], $medEle, $total, 1,0);                              
                                            $totalRec = $totalRec + $total;
                                            $totComponenteR = $totComponenteR + $total; // Total componente orientaciones o sentidos
                                        }        
       if ( ( ($idCom == $datMv['idComM']) or ($datMv['idComM']==0) ) and ( ($idPres == $datMv['idPresM']) or ( $datMv['idPresM'] == 0 ) ) )// Verificar si los materialas adjuntos al recubrimiento son del componente actual
                                        {                                        
                                            $ele = '';    
                                            $imagen  = "com3Prod"; // Imagen 
                                            $nombre  = $datMv['CodMat'].' - '.$datMv['nomMat'];               
                                            $numero  = $datMv['cantidad']*$cantComR; 
                                            $precio  = $datMv['preMat'];                                                                    
                                            $formula = $datMv['formMat'];          
                    
                                            $pos = strpos($datMv['formMat'], "perimetro");
                                            if ($pos>0)
                                            {
                                                $cadena=$datMv['formMat'];
                                                $cadena_cambiada = str_replace('$perimetro','('.$perimetro.')',$cadena);
                                               $formula = $cadena_cambiada;                                                                   
                                            }                                                           
                                            // Ejecutar formula 
                                            $valor = 0;
                                            $forEle = '';
                                            $medEle = '';          
                                            $porDesp = 0;
                                            if ($formula != '')
       	                                    {
                                                $formula = $formula;  
                                                eval("\$valor = $formula;");
                                                $forEle = $formula;
                                               $medEle = $valor;
	                                    }                                
                                            $swVal = 0; // Validacion 
                                            $cadVal = str_replace("[" , '"' , $datMv['validacion']);             
                                            $cadVal = str_replace("]" , '"' , $cadVal );             
           
                                            $swVal = 0; // Validacion 
                                            if ( ($cadVal!='') and ($cadVal!=NULL) )
                                            {
                                                $val = trim($cadVal);  
                                                eval(
                                                  'if (!('.$val.')){'.
                                                     '$swVal=1;'.
                                                  '}');               
                                            }            
                                            if ($swVal==0)
                                            {            
                                               $porDesp = $datMv['porDesp']; 
                                               $valor = ( $medEle ) ;                                   
                                               // GUARDAR DESPIECE
                                               $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                                               $total = $precio * ( $valorU * $numero );                    
                                               if ($total>0)
                                                   $p->actRegistro($idCot, $id, $idCom, 0, $datMv['CodMat'], $medEle, $total,1,0);               
                                               $totalRec = $totalRec + $total;
                                               $totComponenteR = $totComponenteR + $total; // Total componente orientaciones o sentidos
                                           }            
                                        }// Fin validacion hace parte del componente 
                                   } // Fin valdiacion area 
                                }
                            } // Fin validación manejo de recubrimiento
                        } // Fin validacion numero de componentes      
                    } // Recorrido componentes del sistema 	        
                    foreach ($datMatA as $dat){ ////// MATERIALES ADICIONALES -------------------------------------------------------                         
                        // GUARDAR DESPIECE
                        //$valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                        //$total = $precio * ( $valorU * $numero );                    
                        //if ($total>0)
                            $p->actRegistro($idCot, $id, $idCom, 0, $dat['idMat'], $dat['medida'], $dat['medida']*$dat['precio'] ,0,0);               
                            
                            //$p->actRegistro($idCot, $id, $idCom, 0, $datMv['CodMat'], $medEle, $total,1,0);               
                        $adicionales = $adicionales + ($dat['medida']*$dat['precio']); 
                        //echo 'costo '.$adicionales;
                    } ////// FIN DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO ------------------------------------------------------------                 
                    foreach ($datSerA as $dat){ ////// SERVICIOS ADICIONALES -------------------------------------------------------                         
                        // GUARDAR DESPIECE
                       $s->actRegistro($idCot, $id, $idCom , 0 , $dat['idSer'], $dat['medida'], $dat['medida']*$dat['precio'] ,0 ); 
                            
                            //$p->actRegistro($idCot, $id, $idCom, 0, $datMv['CodMat'], $medEle, $total,1,0);               
                        $adicionales = $adicionales + ($dat['medida']*$dat['precio']); 
                        //echo 'costo '.$adicionales;
                    } ////// FIN SERVICIOS POR ORIENTACION O SENTIDO ------------------------------------------------------------                                     
                    // *-----------------------------------//
                    // ----- FIN CALCULOS ---------------------------
                    // *-----------------------------------               
                    //echo 'Mat: '.$totalMat.' Op :'.$totalMatO.' Rec '.$totalRec.' Adi '.$adicionales.'<br />';
                    $vlrMat = $totalMat + $totalMatO + $totalRec + $adicionales ;
                    $vlrSer = $totalSer + $totalSerO;        
                    $vlrTotal  = round($vlrMat + $vlrSer,0);    

                    ///// MARGENES ------------------------------------------------------                    
                    $grantotalM = $totalMat + $totalMatO + $totalRec + $adicionales;
                    $grantotalS = $totalSer + $totalSerO;        
                    $grantotal  = $grantotalM + $grantotalS ;                        
                    $margenC  = $por;          
                    //print_r($datCal);
                    $calculado = 0 ;
                    foreach ($datCal as $dat){ 
                        $var = '$'.$dat['variable']; 
                        $str = $var.'='.$dat['formula'];   
                        if ( ($str!='') and ($str != NULL) )
                        {
                            echo $var.' = ';
                            eval("\$var =$str;");   
                            echo $var.'<br />';
                        }
                    }  
                    // Ejecutar formula
                    $str = $calculo;
                    if ($calculo!=''){                        
                        echo $str.' = ';                        
                        eval("\$str =$str;");   
                        echo $str.'<br />';
                    }
                    else
                        $str = 0;
                    $calculado = $str;   
                    $grantotal  = $grantotalM + $grantotalS + $calculado ;                        
                    
                    //Buscar valores adicionales 
                    
                    // Costos de adicionales 
                    $datA = $u->getGeneral("select case when sum(a.costo) is null then 0 else sum(a.costo) end as costo
                                               from c_cotizaciones_i_servicios a
                                               where a.idIcot = ".$id." and a.excluida = 1
                                               union all 
                     select case when sum(b.costo) is null then 0 else sum(b.costo) end as costo
                                               from c_cotizaciones_i_despiece b
                                               where b.idIcot = ".$id." and b.excluida = 1                                               ");                    
                    $cos = 0;
                    foreach($datA as $datS)
                    {
                        $cos = $cos + $datS['costo'];
                    }
                    // Costos del producto 
                    $datA = $u->getGeneral("select case when sum(a.costo) is null then 0 else sum(a.costo) end as costo
                                               from c_cotizaciones_i_servicios a
                                               where a.idIcot = ".$id." and a.excluida = 0
                                               union all 
                     select case when sum(b.costo) is null then 0 else sum(b.costo) end as costo
                                               from c_cotizaciones_i_despiece b
                                               where b.idIcot = ".$id." and b.excluida = 0                                               ");                    
                    $cosP = 0;
                    foreach($datA as $datS)
                    {
                        $cosP = $cosP + $datS['costo'];
                    }
                    $formVolSG = 0;                    
                    if ($formVol != '')
                    {
                       eval("\$formVolSG = $formVol;");
                    }
                    //echo 'dd '.$formAreaS;
                    $formAreaSG = 0; 
                    if ($formAreaS != '')
                    {
                       eval("\$formAreaSG = $formAreaS;");
                    }                    
                    // Actualizar valor de item en cotizacion                    
                    $u->modGeneral("update c_cotizaciones_i set costoT = ".$grantotal." ,"
                            . " margen= ".$margenC.", costoA = ".$cos.", costoP = ".$cosP.","
                            . " descuento=".$porDescLinea.", volumen=cantidad*".$formVolSG.", area=cantidad*".$formAreaSG." where id = ".$id);       
                    
                    //----------------- IMPORTANTE Y VITAL ---------------------------------------------
                    // GUARDAR MARGENES Y RENTABILIDAD -------------------------------------------------
                    //----------------------------------------------------------------------------------
                    
                    $u->modGeneral("delete from c_cotizaciones_i_margenes where idIcot = ".$id);                                                                                                       
                    echo '----<br />';
                    foreach ($datCal as $dat){ 
                        $var = $dat['variable']; 
                        // Buscar si esta dentro de la cadena
                        $pos = strpos( $calculo , $var);
                        if ($pos>0)
                        {
                            $v = '$'.$dat['variable']; 
                            $formula = $v.'='.$dat['formula'];   
                            eval("\$val =$formula;");                               
                            // Buscar variables dentro del calculo            
                            foreach ($datCal as $datC){ // Buscar variables dentro de la formula del calculo                        
                               $variable = $datC['variable'];  
                               if ($dat['variable'] != $datC['variable'] )// Si es diferente a la formula en cuestion
                               {
                                  $pos = strpos( $formula , $variable);
                                  if ( $pos > 0 )
                                  {
                                      $valor = $v.'='.$datC['formula'];   
                                      eval("\$valor =$valor;");                                                                  
                                      $u->modGeneral("insert into c_cotizaciones_i_margenes (idCot, idIcot, formula,detalle, valor)
                                         values (".$idCot.",".$id.",'".$datC['formula']."', '".$variable."',".$valor.")");
                                  }
                               }// Fin validacion formulas del calculo
                            }
                            
                            $u->modGeneral("insert into c_cotizaciones_i_margenes (idCot, idIcot, formula,detalle, valor)
                               values (".$idCot.",".$id.",'".$dat['formula']."', '".$var."',".$val.")");                                    
                        }
                        //$calculo
                    }                             
                    // 2        
                    // Guardar margen total
                    $u->modGeneral("insert into c_cotizaciones_i_margenes (idCot, idIcot, formula,detalle,valor,total)
                                     values (".$idCot.",".$id.",'".$calculo."', 'Margen aplicado', ".$calculado.",1)");        
       
   }
   
   // Despiece de servicios 
   public function getDespieceS($id, $idLiq)
   {
        $d = new AlbumTable($this->adapter); 
		$hojaO =0;
    $hojaBO =0;
      $dat = $d->getGeneral1("Select count(a.id) as num , b.idCua
             from c_liquidacion_i a 
                inner join c_liquidacion b on b.id = a.idLiq 
                where a.idPord = ".$id." and a.idLiq=".$idLiq);  
      $dat = $d->getGeneral1("Select idCua
                from c_liquidacion where id = ".$idLiq);  
      $idCua = $dat['idCua'];

      $dat = $d->getGeneral1("Select tipo
                from c_pre_ordenes where id = ".$id);        
      if ( $dat['tipo']== 2 )// Si es reproceso borro los servicios calculados
      {
         $d->modGeneral("delete from c_pre_ordenes_servicios where tipo=0 and idPord=".$id);
      }
       
        // Consulta de los servicios para despiece
        $datos = $d->getGeneral("select b.id as idIprod, m.id as idSer,
            m.nombre as nomSer, m.valor, ( k.cantidad * b.cantidad ) as cantProd, 
            l.formula as formEle, l.validacion, aa.tipo       	
         from c_pre_ordenes_i a 
         inner join c_pre_ordenes aa on aa.id = a.idPord 
         inner join c_pre_ordenes_i_d b on b.idIpord = a.id 
         inner join c_cotizaciones_i c on c.id = b.idIcot
         inner join c_cotizaciones_s cc on cc.id = c.idCotS
         inner join c_disenos d on d.id=c.idDis and d.idSis = cc.idSis
         inner join c_plantilla_dis e on e.id = d.idPla
         inner join c_plantilla_dis_c f on f.idPla =  e.id
         inner join c_componentes g on g.id = f.idCom and g.tipo = 1 # Componentes fijo  
         inner join c_componentes_d h on h.idCom = g.id 
         inner join c_sistemas ii on ii.id = cc.idSis  
         inner join c_sistemas_c j on j.idSis = ii.id
			inner join c_sistemas_c_s k on k.idIsis = j.id and k.idIcom = h.id  
			inner join c_formulas l on l.id = h.idFor 
			left join c_servicios m on m.id = k.idSer 
      left join c_cuadrillas n on n.id = ".$idCua."  
      left join c_cuadrillas_s o on o.idCua = n.id 
         where aa.tipo in ('1','0','3') and a.idPord = ".$id." and m.idTser = o.idTser group by b.id, m.id 
         union all
         select b.id as idIprod, f.id as idSer, f.nombre as nomSer,
         f.valor, ( e.cantidad * b.cantidad * c.cantidad ) as cantProd, h.formula as formEle, 
         h.validacion, aa.tipo            
         from c_pre_ordenes_i a
         inner join c_pre_ordenes aa on aa.id = a.idPord 
         inner join c_pre_ordenes_i_d b on b.idIpord = a.id 
         inner join c_cotizaciones_ica c on c.idCotI = a.idIcot 
         inner join c_sistemas_c d on d.id = c.idSisM 
         inner join c_sistemas_c_s e on e.idIsis = d.id 
         inner join c_servicios f on f.id = e.idSer 
         inner join c_componentes_d g on g.id = e.idIcom
         inner join c_formulas h on h.id = g.idFor 
         left join c_cuadrillas i on i.id = ".$idCua." 
         left join c_cuadrillas_s j on j.idCua = i.id 
         where aa.tipo in ('1','0','3') and a.idPord = ".$id." and f.idTser = j.idTser group by b.id, f.id");                
        $idIpd = 0;                                
              $idCot    = 0;
             // print_r($datos);
         foreach ($datos as $dato)/// RECORRIDO 1
         {
                $cantOrd = $dato['cantProd'];// Cantidad a descargar
                $idSer   = $dato['idSer'];// 
          
                //if ( $idIpd != $dato['idIprod'] ) // SE IDENTIDICA EL ITEMS DE LA ORDEN PARA CARGAR VALORES DE VARIABLES
                //{
                    $idIpd = $dato['idIprod'];              
                    // VARGAR TODAS LAS VARIABLES DEL SISTEMA 
                    $datosVar = $d->getVariables(""); // Variables de los componetnes del sistema en el diseño
                    foreach ($datosVar as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'=0';
                        eval("\$str =$str;");   
                    }                     
                    
                    $datosP = $d->getVarProdI($idIpd); // VARIABLES Y VALOR EN LAS ORDENES
                    //print_r($datosP);
                    for ($i=1;$i<=10;$i++)
                    {               
                        if ( ($datosP[ 'var'.$i] != null) and ($datosP[ 'var'.$i] != ' ') )
                        {
                            $str = '$'.$datosP[ 'var'.$i].'='.$datosP[ 'valVar'.$i];
                            //echo $str.':';                            
                            eval("\$str =$str;");   
                            //echo $str.'<br />';                                                        
                        }
                    }
                    //echo $a.'<br />';
                    $datos = $d->getGeneral1("select b.idDis, b.idCot  
                                from c_pre_ordenes_i_d a 
                                inner join c_cotizaciones_i b on b.id = a.idIcot 
                                where a.id = ".$idIpd);            
                    $idDis    = $datos['idDis'];      
                    $idCot    = $datos['idCot'];      
                    // VARIABLE DE LOS COMPONENTES
                    $datosVcom = $d->getVarComp($datos['idDis']); // Variables de los componetnes del sistema en el diseño
                    //print_r($datosVcom);
                    foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                          //echo $str.'<br />';                        
                        eval("\$str =$str;");   
                            //echo $str.'<br />';                        
                    } 
                    // VARIABLES INTERNAS DEL DISEÑO
                    $datVari = $d->getVarTip2Dis($datos['idDis']);
                    //print_r($datVari);
                    foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
                       // Validacion en variables
                       $swVal = 0; // Validacion
                       //echo $dat_f['validacion'].'<br />';
                       if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
                       {
                           $val = trim($dat_f['validacion']);  
                           eval(
                            'if (!('.$val.')){'.
                               '$swVal=1;'.
                            '}');               
                        }    
                        if ($swVal==0)
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        else // Toma el si no de la condicion
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];   
                        eval("\$str =$str;");   
                    }   
                    // FORMULAS DEL COMPOMENTE
                    //echo $idIpd.'<br/>';
                    $datosA = $d->getProCaP( $idIpd, $idSer );
                    $formCom  = 0;
                    foreach ($datosA as $datComp)
                    { ////// DATOS COMPONENTES PESTAÑAS                          
                         if ( (ltrim($datComp['formComp']) != '') and ( $datComp['formComp'] != NULL ) )
                         { 
                            $formula = $datComp['formComp'];  
                            eval("\$valor = $formula;");
                            $formCom  = $valor;
                          }
                      }// FIN RECORRIDO FORMULA DE COMPONENTES 
                      //echo $idSer.'Formula '.$formCom.'<br />';
               // } // FIN CAMBIO DE DISEÑO ITEMS DE ORDEN DE PRODUCCION -------------------------------
                
                // --- VALIDAR SI EL MATERIAL VA O NO VA
                $swVal = 0;
                if ( ($dato['validacion']!='') and ($dato['validacion']!=NULL) )
                {
                   $val = trim($dato['validacion']);  
                   eval(
                      'if (!('.$val.')){'.
                        '$swVal=1;'.
                   '}');               
                }                     
                if ($swVal == 0)
                { 
                   // REGISTRO EN TABLA DE DESPIECE
                   $str = $dato['formEle'];// Medida de la pieza     
                   $pos = strpos($dato['formEle'], "perimetro");
                   if ($pos>0)
                   {                
                      $str = str_replace('$perimetro','('.$dato['formPer'].')',$str);                      
                   }
                   $pos = strpos($dato['formEle'], "area");
                   if ($pos>0)
                   {                
                      $str = str_replace('$area','('.$dato['formArea'].')',$str);                      
                   }
                   eval("\$med =$str;");
                   

                  $datv = $d->getGeneral1("Select count(id) as num , id 
                      from c_pre_ordenes_servicios where tipo=0 and idPord = ".$id."
                        and idIpord=".$idIpd." and idSer=".$idSer);  
                  if ($datv['num']==0)
                  {              
                      $d->modGeneral("insert into c_pre_ordenes_servicios (idPord, idIpord, idSer, cantidad, medida, formComp ) 
                              values( ".$id." ,".$idIpd." ,".$dato['idSer'].", ".$dato['cantProd']." , ".$med." ,".$formCom." )");                     
                  }else{ // Modificar solo cantidad formulada del componente                      
                      $d->modGeneral("update c_pre_ordenes_servicios set formComp = ".$formCom." where id=".$datv['id']);
                  }       
                  // De la consulta se obtiene el id del item de la pre orden 
                  // para consultar los servicios por reprocesos para formular y actualizar formula
                  $datv = $d->getGeneral("Select * 
                      from c_pre_ordenes_servicios 
                        where tipo=1 and idPord = ".$id."
                        and idIpord=".$idIpd);  
                  foreach ($datv as $datSer) 
                  {
                      //echo $datSer['id'].'<br />';
                  }                  
                   
                }// Fin validacion inclucion del material 
                /////---- FIN GUARDAR REGISTRO DEL DESPIECE --------------------------           
            }// FIN RECORRIDO MATERIAL PARA DESPIECE
            
            // SERVICIO EN COTIZACIONES 
            $d->modGeneral("insert into c_pre_ordenes_servicios 
              (idPord, idIcot, idSer , cantidad, medida , tipo , comentario) 
                   select ".$id.", b.id, a.idSer, sum(c.cantidad) as cantidad, a.medida, 1, 'Servicio adicional contrato'   
                      from c_cotizaciones_iser a
                        inner join c_cotizaciones_i b on  b.id = a.idCotI 
                        inner join c_pre_ordenes_i_d c on c.idIcot = b.id 
                          where not exists (SELECT null from c_pre_ordenes_servicios d   
                                where d.idPord = ".$id." and d.idIcot = b.id ) 
                          and a.idCot = ".$idCot." and c.idPord =".$id." group by b.id , a.idSer "  );
            
           // $d->modGeneral("insert into c_liquidacion_i_s (idLiq, idPord, idSer, medida )
           //     (select a.idLiq, a.idPord, idSer,
           //  sum(a.cantidad  * a.medida ) as total  
           //  from c_liquidacion_i a              
           //   where a.idPord = ".$id." and a.idLiq=".$idLiq." group by a.idSer  ) "); 

      //}// Fin validacion existencia de la liquidacion                
   }
   // Despiece de servicios 
   public function getDespieceSmat($id, $idLiq)
   {
      $d = new AlbumTable($this->adapter); 

      $dat = $d->getGeneral1("Select idCua
                from c_liquidacion where id = ".$idLiq);  
      $idCua = $dat['idCua'];

      $dat = $d->getGeneral1("Select * 
                from c_orden_mate where id = ".$id);        
      $idCot = $dat['idCot'];
      // SERVICIO EN COTIZACIONES 
      $d->modGeneral("insert into c_pre_ordenes_servicios 
              (idPord, idIcot, idSer , cantidad, medida , tipo , comentario, origen )
                   select ".$id.", a.id, a.idSer, a.cantidad,1, 2, 'Servicio materiales', 2    
                      from c_cotizaciones_serv_d a
                          where not exists (SELECT null from c_pre_ordenes_servicios   
                                where idIcot = a.id ) and a.idCot = ".$idCot );
   }   
   // Despiece de servicios 
   public function getClonacionCotiza($id, $version)
   {   
      $d = new AlbumTable($this->adapter); 
      // INICIO DE TRANSACCIONES

          // CABECERA
          $datos = $d->getGeneral1("select * from c_cotizaciones where id =".$id);
          $idCotA = $id;
          $d->modGeneral("insert into c_cotizaciones 
              ( idCotC, version, fecha, idCli, nomCli, sitio, contacto, dir, resp, forma, telefonos, idCiu, idVen, idForm, flete, viatico, proyecto )
                        ( select ".$idCotA.", ".$version." ,now(), a.idCli, a.nomCli, a.sitio, a.contacto, a.dir, a.resp, 
                          a.forma, a.telefonos, a.idCiu, a.idVen, a.idForm , a.flete, a.viatico, a.proyecto  
                           from c_cotizaciones a where a.id = ".$idCotA.") " );
          $datId = $d->getGeneral1("SELECT LAST_INSERT_ID() as id");
          $idCotN = $datId['id'];
          $datCon = $d->getGeneral("Select * from c_cotizaciones_l where idCot = ".$idCotA);
          //print_r($datCon);
          foreach ($datCon as $dat) 
          {
             $idCotLA = $dat['id']; 
             // C_COTIZACIONES_L se inserta todo el contenido de la tabla de lineas de un solo tajo
             $d->modGeneral("insert into c_cotizaciones_l ( idCot, idLin, activa, descuento, vista ) 
                        ( select ".$idCotN.", a.idLin, a.activa, a.descuento, a.vista 
                           from c_cotizaciones_l a where a.idCot = ".$idCotA." and a.id = ".$idCotLA.") " );
             $datId = $d->getGeneral1("SELECT LAST_INSERT_ID() as id");                          
             $idCotLN = $datId['id'];
             // C_COTIZACIONES_S // Se recorre sistemna por sistema para obtener el id original
             $datCon = $d->getGeneral("Select * from c_cotizaciones_s where idCot = ".$idCotA." and idCotL = ".$idCotLA);
             //print_r($datCon);
             foreach ($datCon as $datS) 
             {            
                $idCotSA = $datS['id'];
                $d->modGeneral("insert into c_cotizaciones_s ( idCot, idCotL , idSis, item, vista ) 
                          ( select ".$idCotN.", ".$idCotLN.", a.idSis, a.item, a.vista 
                             from c_cotizaciones_s a where a.idCot = ".$idCotA." and a.id = ".$idCotSA." ) " );             
                $datId = $d->getGeneral1("SELECT LAST_INSERT_ID() as id");                          
                $idCotSN = $datId['id'];             
                $datCon = $d->getGeneral("Select * from c_cotizaciones_s
                                    where idCot = ".$idCotA." and idCotL=".$idCotLA." and id=".$idCotSA);
                foreach ($datCon as $datS2) // recorrido items del sistema original
                {
                   $idCotSA = $datS2['id'];
                   // C_COTIZACIONES_I // Se insertan los items de la cotizacion
                 $datCon = $d->getGeneral("Select * from c_cotizaciones_i where cantidad > 0 and idCot = ".$idCotA." and idCotS=".$idCotSA." order by id, idCotIp");
                 // print_r($datCon);
                 foreach ($datCon as $datI) 
                 {                 
                    $idCotIA = $datI['id'];
                    $d->modGeneral("insert into c_cotizaciones_i ( idCot, idCotIp, idCotS, idDis, idDisP, idPres, 
codVid, cantidad, produccido, costoT, costoA, descuento, costoMat, costoP,
costoV, costoVm, costoMfor, costoSer, costoCam, 
idVar1, valVar1, idVar2, valVar2, idVar3, valVar3,idVar4, valVar4, idVar5, valVar5,idVar6, valVar6,
idVar7, valVar7,idVar8, valVar8,idVar9, valVar9,idVar10, valVar10, idLista, costoS, margen, 
volumen, area, mas, menos, item, itemH, descrip, ubi , idIcotAnt, idIcotPant  ) 
                          ( select ".$idCotN.", 0, ".$idCotSN.", a.idDis, a.idDisP, a.idPres, 
a.codVid, a.cantidad, a.produccido, a.costoT, a.costoA, a.descuento, a.costoMat, a.costoP,
a.costoV, a.costoVm, a.costoMfor, a.costoSer, a.costoCam, 
a.idVar1, a.valVar1, a.idVar2, a.valVar2, a.idVar3, a.valVar3,a.idVar4, a.valVar4, a.idVar5, a.valVar5,a.idVar6, a.valVar6,
a.idVar7, a.valVar7,a.idVar8, a.valVar8,a.idVar9, a.valVar9,a.idVar10, a.valVar10, a.idLista, a.costoS, a.margen, 
a.volumen, a.area, a.mas, a.menos, a.item, a.itemH, a.descrip, a.ubi , ".$idCotIA.", idCotIp  
                       from c_cotizaciones_i a where a.cantidad > 0 and idCot = ".$idCotA." and a.id=".$idCotIA.") " );
                      $datId = $d->getGeneral1("SELECT LAST_INSERT_ID() as id");                          
                      $idCotIN = $datId['id'];      
                      $d->modGeneral("update c_cotizaciones_i set idCotIp=id where id=".$idCotIN);       
                      // Verificar si hay ites hijos
                      // Activacion copiado items padre
                      $datIpa = $d->getGeneral("select * from c_cotizaciones_i where cantidad > 0 and idCot = ".$idCotN." order by idIcotPant ");       
                      $itemPadre = 0;
                      $idCotINp = $idCotIN; // GUardar el id del item para organizar cotizacion por items padres
                      foreach ($datIpa as $datIpa) 
                      {
                         $idCotIN = $datIpa['id']; 
                         if ($datIpa['itemH']==0) // Es item padre
                         {
                             $idPadre = $datIpa['id']; 
                             $d->modGeneral("update c_cotizaciones_i set idCotIp=id where id=".$idCotIN);       
                         }
                         else  
                         {
                             $d->modGeneral("update c_cotizaciones_i set idCotIp=".$idPadre." where id=".$idCotIN);                                 
                         }
                      }
                      $idCotIN = $idCotINp;  // Traspaso a id original 
                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS ADICIONALES 
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_ica 
                                   where idCot = ".$idCotA." and idCotI=".$idCotIA);
                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_ICA
                          $d->modGeneral("insert into c_cotizaciones_ica ( idCot, idCotI , idSisM, cantidad ) 
                           ( select ".$idCotN.", ".$idCotIN.", idSisM, cantidad  
                             from c_cotizaciones_ica a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );
                          $datId = $d->getGeneral1("SELECT LAST_INSERT_ID() as id");                          
                          $idCotIAicaN = $datId['id'];                                       
                          //  C_COTIZACIONES_ICA_V variables asociadas a los componentes adicionales
                          $datCon = $d->getGeneral("Select * from c_cotizaciones_ica_v  
                                      where idCot = ".$idCotA." and idCotica = ".$idCotIAicaA);
                          foreach ($datCon as $datIca) 
                          {
                             $d->modGeneral("insert into c_cotizaciones_ica_v ( idCot, idCotica, idCotI,
idVar1, valVar1, idVar2, valVar2, idVar3, valVar3,idVar4, valVar4, idVar5, valVar5,idVar6, valVar6,
idVar7, valVar7,idVar8, valVar8,idVar9, valVar9,idVar10, valVar10) 
                               ( select ".$idCotN.", ".$idCotIAicaN.", ".$idCotIN.", 
a.idVar1, a.valVar1, a.idVar2, a.valVar2, a.idVar3, a.valVar3,a.idVar4, a.valVar4, a.idVar5, a.valVar5,a.idVar6, a.valVar6,
a.idVar7, a.valVar7,a.idVar8, a.valVar8,a.idVar9, a.valVar9,a.idVar10, a.valVar10  
                                  from c_cotizaciones_ica_v a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );                            
                          }// Fin recorrido items componentes adicionales                               
                      }// Fin recorrido items los componentes adicionales del sistema                     

                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS ADICIONALES 
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_ico  
                                   where idCot = ".$idCotA." and idCotI=".$idCotIA);
                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_ICO
                          $d->modGeneral("insert into c_cotizaciones_ico ( idCot, idCotI , idDisC ) 
                           ( select ".$idCotN.", ".$idCotIN.", idDisC 
                             from c_cotizaciones_ico a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );

                      }// Fin recorrido items del sistema                     

                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS MATERIALES
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_ima   
                                   where idCot = ".$idCotA." and idCotI=".$idCotIA);
                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_IMA
                          $d->modGeneral("insert into c_cotizaciones_ima ( idCot, idCotI , idMat, medida ) 
                           ( select ".$idCotN.", ".$idCotIN.", idMat, medida 
                             from c_cotizaciones_ima a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );

                      }// Fin recorrido items del sistema                     

                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS DESPIECES
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_i_despiece     
                                   where idCot = ".$idCotA." and idIcot=".$idCotIA);
                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_DESPIECE
                          $d->modGeneral("insert into c_cotizaciones_i_despiece 
                            ( idCot, idIcot , idCom, idIcom, codMat, medida, costo, recubrimiento, excluida ) 
                           ( select ".$idCotN.", ".$idCotIN.", idCom, idIcom, codMat, medida, costo, recubrimiento, excluida 
                             from c_cotizaciones_i_despiece a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );

                      }// Fin recorrido items del sistema                                           

                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS MARGENES
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_i_margenes     
                                   where idCot = ".$idCotA." and idIcot=".$idCotIA);
                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_MARGENES
                          $d->modGeneral("insert into c_cotizaciones_i_margenes  
                            ( idCot, idIcot , formula, detalle, valor, total ) 
                           ( select ".$idCotN.", ".$idCotIN.", formula, detalle, valor, total 
                             from c_cotizaciones_i_margenes a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );

                      }// Fin recorrido items del sistema                                                                 

                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS MATERIALES
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_i_matadi     
                                   where idCot = ".$idCotA." and idIcot=".$idCotIA);
                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_MATADI
                          $d->modGeneral("insert into c_cotizaciones_i_matadi   
                            ( idCot, idIcot , idCom, idMat, medida, costo ) 
                           ( select ".$idCotN.", ".$idCotIN.", idCom, idMat, medida, costo  
                             from c_cotizaciones_i_matadi a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );

                      }// Fin recorrido items del sistema                                                                 

                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS SERVICIOS
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_i_servicios      
                                   where idCot = ".$idCotA." and idIcot=".$idCotIA);
                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_MATADI
                          $d->modGeneral("insert into c_cotizaciones_i_servicios    
                            ( idCot, idIcot , idCom, idIcom, idSer, medida, costo, excluida ) 
                           ( select ".$idCotN.", ".$idCotIN.", idCom, idIcom, idSer, medida, costo, excluida   
                             from c_cotizaciones_i_servicios a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );

                      }// Fin recorrido items del sistema                                                                 

                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS ORIENTACIONES
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_o    
                                   where idCot = ".$idCotA." and idCotI=".$idCotIA);
                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_O
                          $d->modGeneral("insert into c_cotizaciones_o ( idCot, idCotI , idCom, idOri, numero ) 
                           ( select ".$idCotN.", ".$idCotIN.", idCom, idOri, numero 
                             from c_cotizaciones_o a where a.idCot = ".$idCotA." and a.id = ".$idCotIAicaA." ) " );

                      }// Fin recorrido items del sistema                     

                      // RECORRO LOS ITMES DE LA NUEVA COTIZACION Y BUSCO EN LA ANTERIOR SUS Recubrimientos
//                      echo $idCotA." idCotI=".$idCotIA.'<br />';
                      $datCon = $d->getGeneral("Select * from c_cotizaciones_r      
                                   where idCot = ".$idCotA." and idCotI=".$idCotIA);
                      //if ($idCotIA==7411)
                        //  print_r($datCon);

                      foreach ($datCon as $datIca) 
                      {
                          $idCotIAicaA = $datIca['id'];
                          //  C_COTIZACIONES_R
                          $d->modGeneral("insert into c_cotizaciones_r ( idCot, idCotI , idCom, idMat, numero ) 
                           ( select ".$idCotN.", ".$idCotIN.", idCom, idMat, numero 
                             from c_cotizaciones_r a where a.id = ".$idCotIAicaA." ) " );
                      }// Fin recorrido items del sistema                                           

                 }// Fin recorrido items del sistema original                          
                  
                }// Fin recorrido sistemas             
             }// Fin recorrido items de los sistemas originales
            }// Fin recorrido lineas en cotizaciones           
     return $idCotN ;
   }

   // REsta y sumas en bodegas de materiales
   public function getExistencias($accion, $cantidad, $codMat, $idMat)
   {   
      $d = new AlbumTable($this->adapter); 
      if ( $codMat!='' )
         $con = " a.codmat = '".$codMat."' ";
      else  
         $con = " a.idMat = '".$idMat."' ";

      if ($accion==1) // Restar existencias 
          $d->modGeneral("update c_materiales a
                        inner join c_bodegas_mat b on b.idMat = a.id
                         set b.existen = b.existen - ".$cantidad." 
                      where a.codmat = '".$codMat."'");      
      if ($accion==2) // Sumar existencias 
          $d->modGeneral("update c_materiales a
                        inner join c_bodegas_mat b on b.idMat = a.id
                         set b.existen = b.existen + ".$cantidad." 
                      where a.codmat = '".$codMat."'");                
   }   

   // Totales de la cotizacion
   public function getTotCotiza()
   {
      $d = new AlbumTable($this->adapter); 
      $dat = $d->getGeneral("select id from c_cotizaciones ");
      foreach ($dat as $daCot) 
      {
          $idCot = $daCot['id'];
          // TRUC PARA ACTUALIZAR TOTALES DE LAS COTIZACIONES BRUTO Y DESCUENTOS
          $datT = $d->getTotCot($idCot);
          $vlrBruto = 0;
          $vlrDescuento = 0;
          $area = 0;
          $vol = 0; 
          $itemP = 0;
          foreach ($datT as $dat) 
          {  
              $valor = $dat['valor'];
              if ( $dat['valorS'] > 0 ) // Manejo de precio sugerido 
              {
                 $itemP = $dat['idCotIp'];
                 $valor = $dat['valorS'];
              }
              if ( ($itemP > 0 ) and ($dat['itemH'] == 0 ) ) // Solo suma al sugerido del item padre
              {
                 $vlrBruto = $vlrBruto + ($dat['cantCot'] * $valor);
                 $vlrDescuento = $vlrDescuento + ($dat['cantCot'] * ( $valor * ( $dat['descuento'] / 100 ) ) ); 
              }
              if ( $itemP == 0 ) // Suma el calculado
              {
                 $vlrBruto = $vlrBruto + ($dat['cantCot'] * $valor);
                 $vlrDescuento = $vlrDescuento + ($dat['cantCot'] * $dat['valorD']);                               
              }              

//              $area     = $area + ($dat['cantCot'] * $dat['area']);
  //            $vol      = $vol + ($dat['cantCot'] * $dat['volumen']);                            
              $area     = $area + ($dat['area']);
              $vol      = $vol + ($dat['volumen']);                                          
          }

          $vlrSubtotal = $vlrBruto - $vlrDescuento ;        
          // Flete 
          $vlrFlete = 0;
          if ($dat['fleteCot'] == 0)
          {
              $vlrFlete = $vol * $dat['flete'] ;
              if ( $vlrFlete < $dat['fleteMin'] )
                   $vlrFlete = $dat['fleteMin'] ;
          }
          // Viatico 
          $vlrViatico = 0;
          if ($dat['viaticoCot'] == 0)
          {
              $vlrViatico = $area * $dat['viatico'] ;
              if ( $vlrViatico < $dat['viaticoMin'] )
                   $vlrViatico = $dat['viaticoMin'] ;              
          }          
          $d->modGeneral("update c_cotizaciones 
                 set vlrBruto =".$vlrBruto.",vlrDescuento =".$vlrDescuento.",
                 vlrFlete =".$vlrFlete." , vlrViatico =".$vlrViatico." 
                       where id=".$idCot );
      }      
      // Actuaizacion vaticos
      $dat = $d->getGeneral("select * from c_viaticos where vlrTotal>0");
      foreach ($dat as $daCot) 
      {  
          $d->modGeneral("update c_cotizaciones 
                 set consumoGas =".$daCot['vlrTotal']." where idProy=".$daCot['idProy'] );
      }
      // Actuaizacion servicios pagados 
      $dat = $d->getGeneral("select id from c_cotizaciones ");
      foreach ($dat as $daCot) 
      {
          $idCot = $daCot['id'];
          $datSer = $d->getGeneral1("Select case when sum( b.medLiq * b.valor ) is null 
          then 0 else sum( b.medLiq * b.valor ) end as total 
                    from c_liquidacion a 
                      inner join c_liquidacion_i_s b on b.idLiq = a.id
                      inner join c_pre_ordenes c on c.id = b.idPord
                      inner join c_servicios d on d.id = b.idSer  
                      inner join c_tip_servicios e on e.id = d.idTser  
                      left join c_cuadrillas f on f.id = a.idCua 
                      where a.estado=1 and c.idCot = ".$idCot);
          $d->modGeneral("update c_cotizaciones 
                 set consumoSer =".$datSer['total']." where id=".$idCot );
      }// Fin actualizacion servicios pagados 

   }   

   // Totales de las facturas
   public function getTotFact()
   {
      $d = new AlbumTable($this->adapter); 
      //$dat = $d->getGeneral("select id from c_facturas where vlrBruto=0 and id=285");
      $dat = $d->getGeneral("select id from c_facturas where vlrBruto=0");      
      foreach ($dat as $daCot) 
      {
          $idFact = $daCot['id'];
          // TRUC PARA ACTUALIZAR TOTALES DE LAS COTIZACIONES BRUTO Y DESCUENTOS
          $datT = $d->getTotFa($idFact);
          $vlrBruto = 0;
          $vlrDescuento = 0;
          $itemP = 0;
          $sw = 0;
          foreach ($datT as $dat) 
          {  
              $valor = $dat['valor'];
              if ( $dat['valorS'] > 0 ) // Manejo de precio sugerido 
              {
                 $itemP = $dat['idCotIp'];
                 $valor = $dat['valorS'];
              }
              if ( ($itemP > 0 ) and ($dat['itemH'] == 0 ) ) // Solo suma al sugerido del item padre
              {
                 $vlrBruto = $vlrBruto + ($dat['cantFact'] * $valor);
                 $vlrDescuento = $vlrDescuento + ($dat['cantFact'] * ( $valor * ( $dat['descuento'] / 100 ) ) ); 
              }
              if ( $itemP == 0 ) // Suma el calculado
              {
                 $vlrBruto = $vlrBruto + ($dat['cantFact'] * $valor);
                 $vlrDescuento = $vlrDescuento + ($dat['cantFact'] * $dat['valorD']);                               
              }              

              $sw = 1;
          }
          if ($sw > 0)
          {
             $vlrSubtotal = $vlrBruto - $vlrDescuento ;        

             // GASTOS POR FLETE O VITATICOS 
             $datG = $d->getGeneral1("select case when sum(a.vlrTotal) is null 
                               then 0 else sum(a.vlrTotal) end as valor 
                           from c_viaticos a 
                               inner join c_cotizaciones b on b.idProy = a.idProy 
                               inner join c_facturas c on c.idCot = b.id 
                           where a.estado=1 and a.vlrTotal>0 and c.id = ".$idFact);
             $vlrGastos = $datG['valor'];

             $vlrIva = ($vlrBruto-$vlrDescuento) * ($dat['iva']/100);

             // CONSUMOS DE MATERIALES Y VIATICOS 
             $datG = $d->getGeneral1("select a.consumoMat, a.consumoMatA ,
                                        a.consumoRein , a.consumoGas, a.consumoSer  
                                          from c_cotizaciones a 
                                        inner join c_facturas b on b.idCot = a.id
                                       where b.id = ".$idFact);             
             $vlrMateriales = ( $datG['consumoMat'] + $datG['consumoMatA'] ) - $datG['consumoRein'];
             $vlrServicios = $datG['consumoSer'];

             $vlrIva = ($vlrBruto-$vlrDescuento) * ($dat['iva']/100);

             $d->modGeneral("update c_facturas 
              set fecDoc=fecha , nit=".$dat['codigo'].", 
                vlrBruto =".$vlrBruto.",vlrDescuento =".$vlrDescuento.",
                vlrIva = ".$vlrIva." , vlrTotal=".(($vlrBruto-$vlrDescuento)+$vlrIva).",  
                vlrGastos =".$vlrGastos.", costoMat =".$vlrMateriales.", costoServ =".$vlrServicios."   
              where id=".$idFact );
           }// Fin validacion recorrido de items factura  
      }      

   }   

   // DESPIECE DE ITEMS 
   public function getDespieceItemCotiza( $id )
   {   
       // $tipo : 0 = nuevo , 1 = eliminar
       $f = new Funciones($this->adapter);        
       $u = new AlbumTable($this->adapter);      
$hojaBO=0;
       $datos = $u->getGeneral1("select a.idDis, a.idCot, b.idSis, a.idPres  
                                from c_cotizaciones_i a 
                                inner join c_cotizaciones_s b on b.id=a.idCotS   
                                where a.id=".$id);  
//print_r($datos);
       $idSis  = $datos['idSis'];
       $idDis  = $datos['idDis'];
       $idCot  = $datos['idCot'];
       $idPres = $datos['idPres'];

       $datCot    = $u->getDatCCot($idCot); // Datos de la cabecera                     
       $datIcot   = $u->getDatICot($id);// Datos informacion item y cotiza 
       $datosM = $u->getDatCotM($id);// Datos de margenes
       $datCal = $u->getCalculos(" where variable != ''"); // Calculos
       $datLis      = $u->getListas(""); // Listas
       $datos = $u->getProC(" where c.id=".$idSis." and (jj.idPres=".$idPres.
                                         " or jj.idPres=0 or jj.idPres is null) and ( jjj.idOri=0 or jjj.idOri is null ) ", $id, $idSis);// Materiales del en diseño   
        $datosOri    = $u->getProCori($id, $idSis, $idPres);// Materiales del en diseño             
        $datosA      = $u->getProCa(" where c.id=".$idSis, $id );// Componentes del sistema               
        $datosV      = $u->getVarCot( $idDis , $idCot ,$id ); // Variables con valor en cotizaciones          
        $datosVO     = $u->getVarCotO( $idDis , $idCot ,$id ); // Variables con valor en componentes opcionales cotizaciones
        $datosVcom   = $u->getVarComp( $idDis ); // Variables de los componetnes del sistema en el diseño
        $datVari     = $u->getVarTip2Dis( $idDis ); // Variables internas dentro de diseños tipo 2
        $datRecu     = $u->getRecubrimiento($id, $idPres); // Recubrimiento
        $datMatA     = $u->getMatAdiCoti($id); // Materiales adicionales
        $datSerA     = $u->getSerAdiCoti($id); // Servicios adicionales
        $datMatV     = $u->datMatV($id); // Materiales adjuntos al vidrio
        $datVarG     = $u->getVariables(""); // Variables generales
        // *-----------------------------------//
        // ----- CALCULOS ---------------------------
        // *-----------------------------------               
        $p = New CotizaD($this->adapter); // Funcion para materiales adicionales
        $s = New CotizaS($this->adapter); // Funcion para servicios adicionales
        $m = New CotizaM($this->adapter);               
                    
        $i=1;
        //print_r($datVarG);
        foreach ($datVarG as $dat_f)// TODAS LAS VARIABLES 
        { 
            $str='$'.$dat_f['variable'].'=0';
            eval("\$str =$str;");  
        }
        for ($i=1;$i<=10;$i++)// VARIABLES GUARDADAS EN LAS COTIZACIONES
        { 
            if (isset($datosV['var'.$i]) ) 
            {    
                if (!empty($datosV['var'.$i]) ) 
                {
                    if ($datosV['valVar'.$i]>0)
                    {
                       $str='$'.$datosV['var'.$i].'='.$datosV['valVar'.$i];
                       eval("\$str =$str;");   
                    }
                }
            }  
        }
        for ($i=1;$i<=10;$i++)// VARIABLES GUARDADAS EN LOS COMPONENTES OPCIONALES EN LAS COTIZACIONES
        { 
            if (isset($datosVO['var'.$i]) ) 
            {    
                if (!empty($datosVO['var'.$i]) ) 
                {
                    if ($datosVO['valVar'.$i]>0)
                    {
                        $str='$'.$datosVO['var'.$i].'='.$datosVO['valVar'.$i];
                        eval("\$str =$str;");   
                    }
                }
            }  
        }
        $formVol = 0;
        $formAreaS = 0;
        //print_r($datosVcom);
        foreach ($datosVcom as $dat_f)
        { // VARIABLES DE COMPONENTES 
            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
            eval("\$str =$str;"); 
            $formVol = $dat_f['formVol']; // Formula del volumen
            $formAreaS = $dat_f['formArea']; // Formula del area
        }
        foreach ($datVari as $dat_f)
        { // VARIABLES INTERNAS CON VALORES
            // Validacion en variables
            $swVal = 0; // Validacion 
            if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
            {
                $val = trim($dat_f['validacion']);  
                eval(
                   'if (!('.$val.')){'.
                      '$swVal=1;'.
                '}');               
            }    
            if ($swVal==0)
               $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
            else // Toma el si no de la condicion
               $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];          
               eval("\$str =$str;");   
        }
        $pres    = $datIcot['nomPres'];
        $porDescLinea = round($datIcot['porDesc'],2); // Descuento de la linea

        $calculo = ''; // Calculo fin de documento
        $por = 0; // Porcentaje lista items
        $var = ''; // Variable lista
        foreach ($datosM as $datM)
        { // Datos de calculos
            $calculo = $datM['calculo']; // Calculo
            $por = $datM['por']; // Porcentaje lista items
            $var = $datM['variable']; // Variable lista
        }                       
        $total = 0;
        $totalMat = 0; // materiales
        $totalSer = 0; // Servicios
        $totalMatO = 0; // materiales orientacion
        $totalSerO = 0; // Servicios orientacion
        $totalRec = 0; // Recubrimientos        
        $totComponente = 0; // Total por componente
        $totComponenteR = 0; // Total por componente recubrimiento
        $totComponenteO = 0; // Total por componente orientacion o sentido
        $adicionales = 0; // Total adicionales

        $area  = 0; // Maneja/No maneja area para recubrimiento                        
        $formArea = '';
        $preArea  = 0;
        $formPer  = 0;
        $idEle = '';

        $sw=0; 
        $idComs = 0; // Sw para validar si el componente es diferente para totalizar
    
        foreach ($datosA as $dato)
        { ////// DATOS COMPONENTES PESTAÑAS                          
            if ( ($dato['cantComp'] > 0) ) 
            { // Importante ára validar existencia de componentes adicionales
                $idCom  = $dato['idCom'];
                $idPres = $dato['idPres'];
                $cantCom = $dato['cantComp'];
                if ( (ltrim($dato['formComp']) != '') and ( $dato['formComp'] != NULL ) )
                {
                    $formula = $dato['formComp'];  
                    eval("\$valor = $formula;");
                    $cantCom  = $cantCom * $valor;
                }             
                if ($sw==0){ 
                    $sw=1; 
                }else{ 
                    $clase='tab-pane';
                }
                $area  = 0; // Maneja/No maneja area para recubrimiento                        
                $formArea = '';
                $preArea  = 0;
                $formPer  = 0;
                $idEle = '';
                //////-- DATOS ELEMENTOS Y MATERIALES DEL COMPONENTE --////
                foreach ($datos as $dat)
                {           
                   if ( ( $idCom==$dat['idCom'])) // FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE
                   {      
                      $exclu = $dat['excluida'];
                      if ($dat['formArea']!= NULL)
                      {
                          $area = 1;                        
                          $formArea = $dat['formArea'];
                          $preArea  = $dat['preArea']; // Este no va OJO
                          $formPer  = $dat['formPer'];
                      }
                      // Elementos y materiales 
                      $ele     = '';
                      $imagen  = ''; // Imagen 
                      if ( ( $idEle!=$dat['idFor'])) 
                      {
                          $idEle = $dat['idFor']; 
                          $ele     = $dat['nomFor'];    
                          $imagen  = $dat['icoEle']; // Imagen 
                      }                           
                      if ($dat['tipEle']==1)
                      {
                          $nombre  = $dat['CodMat'].' - '.$dat['nomMat'];
                          $numero  = $dat['cantMat']*$cantCom;                           
                          $precio  = $dat['precio'];
                      }else{// Servicios 
                          $nombre  = $dat['idSer'].' - '.$dat['nomSer'];
                          $numero  = $dat['canServ']*$cantCom; 
                          $precio  = $dat['preServ'];                                   
                      }                      
                      $formula = $dat['formula'];          
                      // Ejecutar formula 
                      $forEle = '';
                      $medEle = '';
                      if ($formula != '')
                      {
                          $formula = $formula;  
                          eval("\$valor = $formula;");
                          $forEle = $formula;  
                          $medEle = number_format($valor,3);
                      }
                      // Formula especial              
                      $formMat = ' ';
                      $medMat =  0;
                      $valorE = 0;
                      $swNv=0;// Para verificar que entre en formulas especiales
                      if (ltrim($dat['formEspe']) != '')
                      {
                         $formula = $dat['formEspe'];  
                         eval("\$valor = $formula;");
                         $formMat = $formula;
                         $medMat  = number_format($valor ,3); // Medida materiales
                         $valorE = $valor; 
                         $swNv=1;
                      }  
                      $porDesp = $dat['porDesp'];                            
                      // Total medidas multiplicacion de formulas, pero sera parametrizable             
                      if ($medMat>0)
                      {
                         $valor = ( $medEle * $valorE ) ;
                      }else
                       {
                           $valor = ( $medEle ) ;
                       }
                       $swVal = 0; // Validacion 
                       if ( (ltrim($dat['validacion'])!='') and (ltrim($dat['validacion'])!=NULL) )
                       {
                          $val = trim($dat['validacion']);  
                          eval(
                           'if (!('.$val.')){'.
                              '$swVal=1;'.
                           '}');               
                        }
                        // Validar que la medida del material sea mayor que cero para mostrar valor
                        if ( ($medMat==0) and ($swNv==1) )
                           $numero = 0;
              
                        if ( ($dat['CodMat']!='') or ($dat['idSer']!='') ){                                  
                           if ($swVal==0)
                           {                   
                              // GUARDAR DESPIECE
                              $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );
                              if ( $dat['porScosto'] > 0) // Sobre costo
                              {
                                  $precio = $precio + ( $precio*($dat['porScosto']/100) ) ; 
                              }
                              $total = $precio * ( $valorU * $numero );                    
                              if ($total>0)
                              {
                                  if (($dat['CodMat']!='') )                        
                                  {
                                     $totalMat = $totalMat + ( $total );
                                  }
                                  if (($dat['idSer']!='') )                      
                                  {
                                      $totalSer = $totalSer + $total;                     
                                  }
                              }                      
                              $totComponente = $totComponente + $total; // Total componente unidad
                            }
                        }
                    } // FIN FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE 
                 } ////// FIN DATOS ELEMENTOS Y MATERIALES ------------------------------------------------------------
                 ////// ORIENTACIONES O SENTIDOS ////
                 $idEle = '';
                 $sw = 0;
                 if ($sw==0)
                 { 
                    $sw=1  ;          
                 }  
                 //print_r($datosOri);
                 foreach ($datosOri as $dat)
                 { ////// DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO-------------------------------------------------------                                                         
                    if ( ( $idCom==$dat['idCom'])) // FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE
                    {
                       // Elementos y materiales                                           
                       $ele     = $dat['nomOri'];
                       $imagen  = ''; // Imagen 
                       if ( ( $idEle!=$dat['idFor'])) 
                       {
                          $idEle = $dat['idFor']; 
                          $imagen  = $dat['icoEle']; // Imagen 
                       }                            
                       if ($dat['tipEle']==1)
                       {
                          $nombre  = $dat['CodMat'].' - '.$dat['nomMat'];
                          $numero  = $dat['cantMat']*$cantCom; 
                          $precio  = $dat['precio'];
                       }else{// Servicios 
                          $nombre  = $dat['idSer'].' - '.$dat['nomSer'];
                          $numero  = $dat['canServ']*$cantCom; 
                          $precio  = $dat['preServ'];                                   
                        }                      
                        $formula = $dat['formula'];          
                        // Ejecutar formula 
                        $forEle = '';
                        $medEle = '';
                        if ($formula != '')
                        {
                            $formula = $formula;  
                            eval("\$valor = $formula;");
                            $forEle = $formula;  
                            $medEle = number_format($valor,3);
                        }
                        // Formula especial              
                        $formMat = ' ';
                        $medMat =  '';
                        $valorE = 0; 
                        if (ltrim($dat['formEspe']) != '')
                        {
                            $formula = $dat['formEspe'];  
                            eval("\$valor = $formula;");
                            $formMat = $formula;
                            $medMat  = number_format($valor,3); // Medida materiales
                            $valorE = $valor; 
                        }               
                        $porDesp = 0;
                        // Total medidas multiplicacion de formulas, pero sera parametrizable             
                        if ($medMat!='')
                           $valor = ( $medEle * $valorE ) ;
                        else
                           $valor = ( $medEle ) ;
              
                        $swVal = 0; // Validacion 
                        if ( ($dat['validacion']!='') and ($dat['validacion']!=NULL) )
                        {
                           $val = trim($dat['validacion']);  
                           eval(
                             'if (!('.$val.')){'.
                             '$swVal=1;'.
                           '}');               
                         }
                         if ( ($dat['CodMat']!='') or ($dat['idSer']!='') )
                         {
                            if ($swVal==0)
                            {
                               // GUARDAR DESPIECE
                               $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                               $total = $precio * ( $valorU * $numero );                    
                               if (($dat['CodMat']!='') )
                                  $totalMatO = $totalMatO + $total;
                               if (($dat['idSer']!='') )
                                  $totalSerO = $totalSerO + $total;                      
                               $totComponenteO = $totComponenteO + $total; // Total componente orientaciones o sentidos
                            }                  
                         }
                    }  // FIN FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE 
                 } ////// FIN DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO ------------------------------------------------------------

                 // RECUBRIMIENTOS ----------------------------------------------------------------------
                 $num = 0; 
                 $cantComR = 1;
                 // Materiales
                 if ($area==1)
                 {      
                    $componente = 'Recubrimiento';
                    foreach ($datRecu as $datMv)
                    {    
                       // Cambio de numero de componente
                       if ($idCom == $datMv['idCom'] )
                       {
                          $ele     = "";      
                          $preArea  = $datMv['preVid']; // este si es 
                          // Si tiene formula especial reeemplza la del padre
                          if ( $datMv['formVid'] != '') 
                              $formArea = $datMv['formVid'] ;                                        
                                        
                          if  ($datMv['numero'] != $num )  
                          {
                              $num = $datMv['numero'];
                              $ele = 'Componente '.$num;    
                              // Vidrios
                              $imagen  = ''; // Imagen 
                              $nombre  = $datMv['codVid'].' - '.$datMv['nomVid'];               
                              $numero  = $cantComR; 
                              $precio  = $preArea;                                                                    
                              $formula = $formArea;          
                              $formMat = ' ';
                              $medEle  = ' ';
                              $medMat  = ' ';
                              //$area      = $formArea;
                              $perimetro = $formPer;
                              //$espesor   = $dat['espesor'];
                              // Ejecutar formula 
                              $valor = 0;
                              $forEle = '';
                              $medEle = '';        
                              if ($datMv['variables']!='') // Variables del sistema
                              {
                                  $str = $datMv['variables'];
                                  eval("\$str =$str;");
       //                           echo $str;
                              }                                                                        
                              if ($formula != '')
                              {
                                 $formula = $formula;  
                                 eval("\$valor = $formula;");
                                 $forEle = $formula;
                                 $medEle = $valor;
                              }       
                              $porDesp = $datMv['porDespV'];
                              $valor = ( $medEle ) ;
                              // GUARDAR DESPIECE
                              $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                              $total = $precio * ( $valorU * $numero );                    
                              $totalRec = $totalRec + $total;
                              $totComponenteR = $totComponenteR + $total; // Total componente orientaciones o sentidos
                          }        
                          if ( ( ($idCom == $datMv['idComM']) or ($datMv['idComM']==0) ) and ( ($idPres == $datMv['idPresM']) or ( $datMv['idPresM'] == 0 ) ) )// Verificar si los materialas adjuntos al recubrimiento son del componente actual
                          {                                        
                              $ele = '';    
                              $imagen  = "com3Prod"; // Imagen 
                              $nombre  = $datMv['CodMat'].' - '.$datMv['nomMat'];               
                              $numero  = $datMv['cantidad']*$cantComR; 
                              $precio  = $datMv['preMat'];                                                                    
                              $formula = $datMv['formMat'];          
                    
                              $pos = strpos($datMv['formMat'], "perimetro");
                              if ($pos>0)
                              {
                                  $cadena=$datMv['formMat'];
                                  $cadena_cambiada = str_replace('$perimetro','('.$perimetro.')',$cadena);
                                  $formula = $cadena_cambiada;                                                                   
                              }                                                           
                              // Ejecutar formula 
                              $valor = 0;
                              $forEle = '';
                              $medEle = '';          
                              $porDesp = 0;
                              if ($formula != '')
                              {
                                  $formula = $formula;  
                                  eval("\$valor = $formula;");
                                  $forEle = $formula;
                                  $medEle = $valor;
                              }                                
                              $swVal = 0; // Validacion 
                              $cadVal = str_replace("[" , '"' , $datMv['validacion']);             
                              $cadVal = str_replace("]" , '"' , $cadVal );             
           
                              $swVal = 0; // Validacion 
                              if ( ($cadVal!='') and ($cadVal!=NULL) )
                              {
                                  $val = trim($cadVal);  
                                  eval(
                                    'if (!('.$val.')){'.
                                       '$swVal=1;'.
                                    '}');               
                              }            
                              if ($swVal==0)
                              {            
                                  $porDesp = $datMv['porDesp']; 
                                  $valor = ( $medEle ) ;                                   
                                  // GUARDAR DESPIECE
                                  $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                                  $total = $precio * ( $valorU * $numero );                    
                                  $totalRec = $totalRec + $total;
                                  $totComponenteR = $totComponenteR + $total; // Total componente orientaciones o sentidos
                              }            
                          }// Fin validacion hace parte del componente 
                        } // Fin valdiacion area 
                      }
                  } // Fin validación manejo de recubrimiento
               } // Fin validacion numero de componentes      
            } // Recorrido componentes del sistema          

            foreach ($datMatA as $dat)
            { ////// MATERIALES ADICIONALES -------------------------------------------------------                         
                // GUARDAR DESPIECE
               $adicionales = $adicionales + ($dat['medida']*$dat['precio']); 
            } ////// FIN DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO ------------------------------------------------------------                 
            foreach ($datSerA as $dat)
            { ////// SERVICIOS ADICIONALES -------------------------------------------------------                         
                $adicionales = $adicionales + ($dat['medida']*$dat['precio']); 
            } ////// FIN SERVICIOS POR ORIENTACION O SENTIDO ------------------------------------------------------------                                     
            // *-----------------------------------//
            // ----- FIN CALCULOS ---------------------------
            // *-----------------------------------               
            //echo 'Mat: '.$totalMat.' Op :'.$totalMatO.' Rec '.$totalRec.' Adi '.$adicionales.'<br />';
            $vlrMat = $totalMat + $totalMatO + $totalRec + $adicionales ;
            $vlrSer = $totalSer + $totalSerO;        
            $vlrTotal  = round($vlrMat + $vlrSer,0);    

//echo 'VALOR DLE PRODUCTO '.$vlrMat.'<br />';
//echo 'VALOR DEL SERVICIO '.$vlrSer.'<br />';
//echo 'VALOR DEL SERVICIO '.$vlrSer.'<br />';            
            return(array( "0"=>$vlrMat, 
                          "1"=>$vlrSer, )) ;
                                
    }


   // DESPIECE DE ITEMS VALORIZADO EN COSTO DE FABRICACION ESTIMADO
   public function getDespieceItemCotizaCostos( $id )
   {   
       // $tipo : 0 = nuevo , 1 = eliminar
       $f = new Funciones($this->adapter);        
       $u = new AlbumTable($this->adapter);      
       
       $datos = $u->getGeneral1("select a.idDis, a.idCot, b.idSis, a.idPres  
                                from c_cotizaciones_i a 
                                inner join c_cotizaciones_s b on b.id=a.idCotS   
                                where a.id=".$id);  

       $idSis  = $datos['idSis'];
       $idDis  = $datos['idDis'];
       $idCot  = $datos['idCot'];
       $idPres = $datos['idPres'];

       $datCot    = $u->getDatCCot($idCot); // Datos de la cabecera                     
       $datIcot   = $u->getDatICot($id);// Datos informacion item y cotiza 
       $datosM = $u->getDatCotM($id);// Datos de margenes
       $datCal = $u->getCalculos(" where variable != ''"); // Calculos
       $datLis      = $u->getListas(""); // Listas
       $datos = $u->getProC(" where c.id=".$idSis." and (jj.idPres=".$idPres.
                                         " or jj.idPres=0 or jj.idPres is null) and ( jjj.idOri=0 or jjj.idOri is null ) ", $id, $idSis);// Materiales del en diseño   
        $datosOri    = $u->getProCori($id, $idSis, $idPres);// Materiales del en diseño             
        $datosA      = $u->getProCa(" where c.id=".$idSis, $id );// Componentes del sistema               
        $datosV      = $u->getVarCot( $idDis , $idCot ,$id ); // Variables con valor en cotizaciones          
        $datosVO     = $u->getVarCotO( $idDis , $idCot ,$id ); // Variables con valor en componentes opcionales cotizaciones
        $datosVcom   = $u->getVarComp( $idDis ); // Variables de los componetnes del sistema en el diseño
        $datVari     = $u->getVarTip2Dis( $idDis ); // Variables internas dentro de diseños tipo 2
        $datRecu     = $u->getRecubrimiento($id, $idPres); // Recubrimiento
        $datMatA     = $u->getMatAdiCoti($id); // Materiales adicionales
        $datSerA     = $u->getSerAdiCoti($id); // Servicios adicionales
        $datMatV     = $u->datMatV($id); // Materiales adjuntos al vidrio
        $datVarG     = $u->getVariables(""); // Variables generales
        // *-----------------------------------//
        // ----- CALCULOS ---------------------------
        // *-----------------------------------               
        $p = New CotizaD($this->adapter); // Funcion para materiales adicionales
        $s = New CotizaS($this->adapter); // Funcion para servicios adicionales
        $m = New CotizaM($this->adapter);               
                    
        $i=1;
        //print_r($datVarG);
        foreach ($datVarG as $dat_f)// TODAS LAS VARIABLES 
        { 
            $str='$'.$dat_f['variable'].'=0';
            eval("\$str =$str;");  
        }
        for ($i=1;$i<=10;$i++)// VARIABLES GUARDADAS EN LAS COTIZACIONES
        { 
            if (isset($datosV['var'.$i]) ) 
            {    
                if (!empty($datosV['var'.$i]) ) 
                {
                    if ($datosV['valVar'.$i]>0)
                    {
                       $str='$'.$datosV['var'.$i].'='.$datosV['valVar'.$i];
                       eval("\$str =$str;");   
                    }
                }
            }  
        }
        for ($i=1;$i<=10;$i++)// VARIABLES GUARDADAS EN LOS COMPONENTES OPCIONALES EN LAS COTIZACIONES
        { 
            if (isset($datosVO['var'.$i]) ) 
            {    
                if (!empty($datosVO['var'.$i]) ) 
                {
                    if ($datosVO['valVar'.$i]>0)
                    {
                        $str='$'.$datosVO['var'.$i].'='.$datosVO['valVar'.$i];
                        eval("\$str =$str;");   
                    }
                }
            }  
        }
        $formVol = 0;
        $formAreaS = 0;
        //print_r($datosVcom);
        foreach ($datosVcom as $dat_f)
        { // VARIABLES DE COMPONENTES 
            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
            eval("\$str =$str;"); 
            $formVol = $dat_f['formVol']; // Formula del volumen
            $formAreaS = $dat_f['formArea']; // Formula del area
        }
        foreach ($datVari as $dat_f)
        { // VARIABLES INTERNAS CON VALORES
            // Validacion en variables
            $swVal = 0; // Validacion 
            if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
            {
                $val = trim($dat_f['validacion']);  
                eval(
                   'if (!('.$val.')){'.
                      '$swVal=1;'.
                '}');               
            }    
            if ($swVal==0)
               $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
            else // Toma el si no de la condicion
               $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];          
               eval("\$str =$str;");   
        }
        $pres    = $datIcot['nomPres'];
        $porDescLinea = round($datIcot['porDesc'],2); // Descuento de la linea

        $calculo = ''; // Calculo fin de documento
        $por = 0; // Porcentaje lista items
        $var = ''; // Variable lista
        foreach ($datosM as $datM)
        { // Datos de calculos
            $calculo = $datM['calculo']; // Calculo
            $por = $datM['por']; // Porcentaje lista items
            $var = $datM['variable']; // Variable lista
        }                       
        $total = 0;
        $totalMat = 0; // materiales
        $totalSer = 0; // Servicios
        $totalMatO = 0; // materiales orientacion
        $totalSerO = 0; // Servicios orientacion
        $totalRec = 0; // Recubrimientos        
        $totComponente = 0; // Total por componente
        $totComponenteR = 0; // Total por componente recubrimiento
        $totComponenteO = 0; // Total por componente orientacion o sentido
        $adicionales = 0; // Total adicionales

        $area  = 0; // Maneja/No maneja area para recubrimiento                        
        $formArea = '';
        $preArea  = 0;
        $formPer  = 0;
        $idEle = '';

        $sw=0; 
        $idComs = 0; // Sw para validar si el componente es diferente para totalizar
    
        foreach ($datosA as $dato)
        { ////// DATOS COMPONENTES PESTAÑAS                          
            if ( ($dato['cantComp'] > 0) ) 
            { // Importante ára validar existencia de componentes adicionales
                $idCom  = $dato['idCom'];
                $idPres = $dato['idPres'];
                $cantCom = $dato['cantComp'];
                if ( (ltrim($dato['formComp']) != '') and ( $dato['formComp'] != NULL ) )
                {
                    $formula = $dato['formComp'];  
                    eval("\$valor = $formula;");
                    $cantCom  = $cantCom * $valor;
                }             
                if ($sw==0){ 
                    $sw=1; 
                }else{ 
                    $clase='tab-pane';
                }
                $area  = 0; // Maneja/No maneja area para recubrimiento                        
                $formArea = '';
                $preArea  = 0;
                $formPer  = 0;
                $idEle = '';
                //////-- DATOS ELEMENTOS Y MATERIALES DEL COMPONENTE --////
                foreach ($datos as $dat)
                {           
                   if ( ( $idCom==$dat['idCom'])) // FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE
                   {      
                      $exclu = $dat['excluida'];
                      if ($dat['formArea']!= NULL)
                      {
                          $area = 1;                        
                          $formArea = $dat['formArea'];
                          $preArea  = $dat['preArea']; // Este no va OJO
                          $formPer  = $dat['formPer'];
                      }
                      // Elementos y materiales 
                      $ele     = '';
                      $imagen  = ''; // Imagen 
                      if ( ( $idEle!=$dat['idFor'])) 
                      {
                          $idEle = $dat['idFor']; 
                          $ele     = $dat['nomFor'];    
                          $imagen  = $dat['icoEle']; // Imagen 
                      }                           
                      if ($dat['tipEle']==1)
                      {
                          $nombre  = $dat['CodMat'].' - '.$dat['nomMat'];
                          $numero  = $dat['cantMat']*$cantCom;                           
                          $datCosto = $u->getMatCosto($dat['CodMat'],0); // Obtener costo del material                           
                          $precio  = $datCosto['costo'];
//         echo $dat['CodMat'].' valor :'.number_format( $precio ,2 ).'<br />';
                      }else{// Servicios 
                          $nombre  = $dat['idSer'].' - '.$dat['nomSer'];
                          $numero  = $dat['canServ']*$cantCom; 
                          $precio  = 0;
                          if ($dat['idSer']>0)
                          {
                             $datCosto = $u->getServCosto($dat['idSer']); // Obtener costo del servicio
                             $precio  = $datCosto['costo'];                                   
                          }
                      }                      
                      $formula = $dat['formula'];          
                      // Ejecutar formula 
                      $forEle = '';
                      $medEle = '';
                      if ($formula != '')
                      {
                          $formula = $formula;  
                          eval("\$valor = $formula;");
                          $forEle = $formula;  
                          $medEle = number_format($valor,3);
                      }
                      // Formula especial              
                      $formMat = ' ';
                      $medMat =  0;
                      $valorE = 0;
                      $swNv=0;// Para verificar que entre en formulas especiales
                      if (ltrim($dat['formEspe']) != '')
                      {
                         $formula = $dat['formEspe'];  
                         eval("\$valor = $formula;");
                         $formMat = $formula;
                         $medMat  = number_format($valor ,3); // Medida materiales
                         $valorE = $valor; 
                         $swNv=1;
                      }  
                      $porDesp = $dat['porDesp'];                            
                      // Total medidas multiplicacion de formulas, pero sera parametrizable             
                      if ($medMat>0)
                      {
                         $valor = ( $medEle * $valorE ) ;
                      }else
                       {
                           $valor = ( $medEle ) ;
                       }
                       $swVal = 0; // Validacion 
                       if ( ($dat['validacion']!='') and ($dat['validacion']!=NULL) )
                       {
                          $val = trim($dat['validacion']);  
                          eval(
                           'if (!('.$val.')){'.
                              '$swVal=1;'.
                           '}');               
                        }
                        // Validar que la medida del material sea mayor que cero para mostrar valor
                        if ( ($medMat==0) and ($swNv==1) )
                           $numero = 0;
              
                        if ( ($dat['CodMat']!='') or ($dat['idSer']!='') ){                                  
                           if ($swVal==0)
                           {                   
                              // GUARDAR DESPIECE
                              $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );

                              $total = $precio * ( $valorU * $numero );                    
                              if ($total>0)
                              {
                                  if (($dat['CodMat']!='') )                        
                                  {
                                     $totalMat = $totalMat + ( $total );
                                  }
                                  if (($dat['idSer']!='') )                      
                                  {
                                      $totalSer = $totalSer + $total;                     
                                  }
                              }                      
                              $totComponente = $totComponente + $total; // Total componente unidad
                            }
                        }
                    } // FIN FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE 
                 } ////// FIN DATOS ELEMENTOS Y MATERIALES ------------------------------------------------------------
                 ////// ORIENTACIONES O SENTIDOS ////
                 $idEle = '';
                 $sw = 0;
                 if ($sw==0)
                 { 
                    $sw=1  ;          
                 }  
                 //print_r($datosOri);
                 foreach ($datosOri as $dat)
                 { ////// DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO-------------------------------------------------------                                                         
                    if ( ( $idCom==$dat['idCom'])) // FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE
                    {
                       // Elementos y materiales                                           
                       $ele     = $dat['nomOri'];
                       $imagen  = ''; // Imagen 
                       if ( ( $idEle!=$dat['idFor'])) 
                       {
                          $idEle = $dat['idFor']; 
                          $imagen  = $dat['icoEle']; // Imagen 
                       }                            
                       if ($dat['tipEle']==1)
                       {
                          $nombre  = $dat['CodMat'].' - '.$dat['nomMat'];
                          $numero  = $dat['cantMat']*$cantCom; 
                          $datCosto = $u->getMatCosto($dat['CodMat'],0); // Obtener costo del material                           
                          $precio  = $datCosto['costo'];
                       }else{// Servicios 
                          $nombre  = $dat['idSer'].' - '.$dat['nomSer'];
                          $numero  = $dat['canServ']*$cantCom; 
                          $datCosto = $u->getServCosto($dat['idSer']); // Obtener costo del servicio
                          $precio  = $datCosto['costo'];                                                            
                        }                      
                        $formula = $dat['formula'];          
                        // Ejecutar formula 
                        $forEle = '';
                        $medEle = '';
                        if ($formula != '')
                        {
                            $formula = $formula;  
                            eval("\$valor = $formula;");
                            $forEle = $formula;  
                            $medEle = number_format($valor,3);
                        }
                        // Formula especial              
                        $formMat = ' ';
                        $medMat =  '';
                        $valorE = 0; 
                        if (ltrim($dat['formEspe']) != '')
                        {
                            $formula = $dat['formEspe'];  
                            eval("\$valor = $formula;");
                            $formMat = $formula;
                            $medMat  = number_format($valor,3); // Medida materiales
                            $valorE = $valor; 
                        }               
                        $porDesp = 0;
                        // Total medidas multiplicacion de formulas, pero sera parametrizable             
                        if ($medMat!='')
                           $valor = ( $medEle * $valorE ) ;
                        else
                           $valor = ( $medEle ) ;
              
                        $swVal = 0; // Validacion 
                        if ( ($dat['validacion']!='') and ($dat['validacion']!=NULL) )
                        {
                           $val = trim($dat['validacion']);  
                           eval(
                             'if (!('.$val.')){'.
                             '$swVal=1;'.
                           '}');               
                         }
                         if ( ($dat['CodMat']!='') or ($dat['idSer']!='') )
                         {
                            if ($swVal==0)
                            {
                               // GUARDAR DESPIECE
                               $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                               $total = $precio * ( $valorU * $numero );                    
                               if (($dat['CodMat']!='') )
                                  $totalMatO = $totalMatO + $total;
                               if (($dat['idSer']!='') )
                                  $totalSerO = $totalSerO + $total;                      
                               $totComponenteO = $totComponenteO + $total; // Total componente orientaciones o sentidos
                            }                  
                         }
                    }  // FIN FILTRAR ELEMENTOS SOLO DE ESTE COMPONENTE 
                 } ////// FIN DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO ------------------------------------------------------------

                 // RECUBRIMIENTOS ----------------------------------------------------------------------
                 $num = 0; 
                 $cantComR = 1;
                 // Materiales
                 if ($area==1)
                 {      
                    $componente = 'Recubrimiento';
                    foreach ($datRecu as $datMv)
                    {    
                       // Cambio de numero de componente
                       if ($idCom == $datMv['idCom'] )
                       {
                          $ele     = "";      
                          $preArea  = $datMv['preVid']; // este si es 
                          // Si tiene formula especial reeemplza la del padre
                          if ( $datMv['formVid'] != '') 
                              $formArea = $datMv['formVid'] ;                                        
                                        
                          if  ($datMv['numero'] != $num )  
                          {
                              $num = $datMv['numero'];
                              $ele = 'Componente '.$num;    
                              // Vidrios
                              $imagen  = ''; // Imagen 
                              $nombre  = $datMv['codVid'].' - '.$datMv['nomVid'];               
                              $numero  = $cantComR; 
                              $datCosto = $u->getMatCosto($datMv['CodVid'],0); // Obtener costo del material                           
                              $precio  = $datCosto['costo'];

                              $formula = $formArea;          
                              $formMat = ' ';
                              $medEle  = ' ';
                              $medMat  = ' ';
                              //$area      = $formArea;
                              $perimetro = $formPer;
                              //$espesor   = $dat['espesor'];
                              // Ejecutar formula 
                              $valor = 0;
                              $forEle = '';
                              $medEle = '';        
                              if ($datMv['variables']!='') // Variables del sistema
                              {
                                  $str = $datMv['variables'];
                                  eval("\$str =$str;");
                                  //echo $str;
                              }                                                                        
                              if ($formula != '')
                              {
                                 $formula = $formula;  
                                 eval("\$valor = $formula;");
                                 $forEle = $formula;
                                 $medEle = $valor;
                              }       
                              $porDesp = $datMv['porDespV'];
                              $valor = ( $medEle ) ;
                              // GUARDAR DESPIECE
                              $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                              $total = $precio * ( $valorU * $numero );                    
                              $totalRec = $totalRec + $total;
                              $totComponenteR = $totComponenteR + $total; // Total componente orientaciones o sentidos
                          }        
                          if ( ( ($idCom == $datMv['idComM']) or ($datMv['idComM']==0) ) and ( ($idPres == $datMv['idPresM']) or ( $datMv['idPresM'] == 0 ) ) )// Verificar si los materialas adjuntos al recubrimiento son del componente actual
                          {                                        
                              $ele = '';    
                              $imagen  = "com3Prod"; // Imagen 
                              $nombre  = $datMv['CodMat'].' - '.$datMv['nomMat'];               
                              $numero  = $datMv['cantidad']*$cantComR; 
                              
                              $datCosto = $u->getMatCosto($datMv['CodMat'],0); // Obtener costo del material                           
                              $precio  = $datCosto['costo'];                              

                              $formula = $datMv['formMat'];          
                    
                              $pos = strpos($datMv['formMat'], "perimetro");
                              if ($pos>0)
                              {
                                  $cadena=$datMv['formMat'];
                                  $cadena_cambiada = str_replace('$perimetro','('.$perimetro.')',$cadena);
                                  $formula = $cadena_cambiada;                                                                   
                              }                                                           
                              // Ejecutar formula 
                              $valor = 0;
                              $forEle = '';
                              $medEle = '';          
                              $porDesp = 0;
                              if ($formula != '')
                              {
                                  $formula = $formula;  
                                  eval("\$valor = $formula;");
                                  $forEle = $formula;
                                  $medEle = $valor;
                              }                                
                              $swVal = 0; // Validacion 
                              $cadVal = str_replace("[" , '"' , $datMv['validacion']);             
                              $cadVal = str_replace("]" , '"' , $cadVal );             
           
                              $swVal = 0; // Validacion 
                              if ( ($cadVal!='') and ($cadVal!=NULL) )
                              {
                                  $val = trim($cadVal);  
                                  eval(
                                    'if (!('.$val.')){'.
                                       '$swVal=1;'.
                                    '}');               
                              }            
                              if ($swVal==0)
                              {            
                                  $porDesp = $datMv['porDesp']; 
                                  $valor = ( $medEle ) ;                                   
                                  // GUARDAR DESPIECE
                                  $valorU = ( $valor + ( $valor *( $porDesp / 100 ) ) );                    
                                  $total = $precio * ( $valorU * $numero );                    
                                  $totalRec = $totalRec + $total;
                                  $totComponenteR = $totComponenteR + $total; // Total componente orientaciones o sentidos
                              }            
                          }// Fin validacion hace parte del componente 
                        } // Fin valdiacion area 
                      }
                  } // Fin validación manejo de recubrimiento
               } // Fin validacion numero de componentes      
            } // Recorrido componentes del sistema          

            foreach ($datMatA as $dat)
            { ////// MATERIALES ADICIONALES -------------------------------------------------------                         
                // GUARDAR DESPIECE
               $adicionales = $adicionales + ($dat['medida']*$dat['precio']); 
            } ////// FIN DATOS ELEMENTOS Y MATERIALES POR ORIENTACION O SENTIDO ------------------------------------------------------------                 
            foreach ($datSerA as $dat)
            { ////// SERVICIOS ADICIONALES -------------------------------------------------------                         
                $adicionales = $adicionales + ($dat['medida']*$dat['precio']); 
            } ////// FIN SERVICIOS POR ORIENTACION O SENTIDO ------------------------------------------------------------                                     
            // *-----------------------------------//
            // ----- FIN CALCULOS ---------------------------
            // *-----------------------------------               
            //echo 'Mat: '.$totalMat.' Op :'.$totalMatO.' Rec '.$totalRec.' Adi '.$adicionales.'<br />';
            $vlrMat = $totalMat + $totalMatO + $totalRec + $adicionales ;
            $vlrSer = $totalSer + $totalSerO;        
            $vlrTotal  = round($vlrMat + $vlrSer,0);    

//echo 'VALOR DLE PRODUCTO '.$vlrMat.'<br />';
//echo 'VALOR DEL SERVICIO '.$vlrSer.'<br />';
//echo 'VALOR DEL SERVICIO '.$vlrSer.'<br />';            
            return(array( "0"=>$vlrMat, 
                          "1"=>$vlrSer, )) ;
                                
    }


   // Despiece de materiales por proyectos 3
   public function getDespieceMat($id, $tipo)
   {
        $d = new AlbumTable($this->adapter); 
        $datVarG = $d->getVariables(""); // Variables generales
        
        /// DESPIECE POR PIEZAS LINEALES Y UNIDADES
        // TODAS LAS VARIABLES         
        foreach ($datVarG as $dat_f)
        { 
            $str='$'.$dat_f['variable'].'=0';
            eval("\$str =$str;");  
        }        
//$hojaO =0;
        /// -******----------------------------///
        //******** DESPIECE POR CONSUMO *******////        
        /// ----------------------------///-----****        
        ini_set('max_execution_time', 8000); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        switch ($tipo) {
            case 1: // Materiales de la base
                $datos = $d->getDespieceMmat($id, " and g.tipo=1 and ii.despTotal = 0 and ( m.ancho > 0 and m.alto = 0 )"
                        . "and ( kkk.idPres=0 or kkk.idPres is null or kkk.idPres = c.idPres ) 
                         and ( ( q.id is null and k.idOri = 0 ) or ( q.idOri = k.idOri )  )  and  m.optimizar=0 # Mostrar solo los materiales que tenga orientacion 
                         group by m.codMat ");
                break;
            case 2: // Materiales adicionales del componente
                $datos = $d->getDespieceMcaMat($id);
                break;
            case 3: // Materiales por recubrimientos
                $datos = $d->getDespieceMre2Mat($id); # NUeva formula para verificar fallos
                break;            
            case 4: // Materiales adjuntos a los recubrimientos
                $datos = $d->getDespieceMreMmat($id, " and ( i.ancho > 0.001 and i.alto = 0 ) ");
                break;                        
            case 5: // Materiales adicionales en items de cotizaciones
                $datos = $d->getMateAdicMat($id, " and ( d.ancho>0 and d.alto=0 ) ");
                break;                                        
            default:
                break;
        }

           //$d->modGeneral("update  c_pre_ordenes_despiece set cantMat = 0 where idPord=".$id); // Poner todo en 0     
          foreach ($datos as $datM)
          {         
            $codMat  = $datM['CodMat'];               
            if ( $datM['descar'] != -8989899999 ) // Que no tenga material descargado es la forma para recalcular 
            {
      // Validar 
            $ancho   = $datM['ancho']; // Ancho de la pieza
                        
            if (  $tipo == 3  ) // Recubrimientos 
            {
               $cantOrd  = $datM['cantOrd']; 
               $cantEle  = 1;
               $ancho    = 0;
               $desCorte = 0;
               //$med      = round( $datM['medida'], 3 );
               $med      = $datM['medida'];
               // Verificar que el mismo material con la misma medida no se guarde 2 veces
               $datDes = $d->getGeneral1("select id  
                                           from c_proyectos_despiece 
                                           where idCot=".$id." and tipo = ".$tipo." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd." and round(medida,2)=".$med);
               //print_r($datDes);
               if ($datDes['id']==0) // Se modifica el registro
               { 
                  // Guardar datos en la tabla de despieces de la orden de produccion
                  $d->modGeneral("insert into c_proyectos_despiece "
                          . "(idCot, codMat, medida, cantProd, cantEle, cantReq, limite, desCorte, componente, tipo )"
                        . " values(".$id.",'".$codMat."',".$med.",".$cantOrd.",".$cantEle.", ".$med." ,".$ancho.", ".$desCorte.",'Recubrimiento', ".$tipo." )");
               }else{
                  $d->modGeneral("update c_proyectos_despiece set cantReq = ".$med." where idCot=".$id." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd." and round(medida,2)=".$med);                
               }            

            }else{// Manejo de materiales con optimizacion 
               
              $nomLin  = $datM['nomLin']; // Linea de materiales
              $existen = $datM['existen']; // Existencia actual            
              $desCorte = $datM['desCorte'];// Desperdicio en corte
              $cantOrd  = 0;
              //echo $codMat.'<br />'; 
              // Buscar materiales asociados  
              $matVal = array(); // Medidas del material
              // Valor de variables de materiales en diferentes items de la orden
              $idIpd = 0;

              $datos = $d->getDespieceMat($id, $codMat, $tipo);  // Buscar tiems que contienen material activo ------------------------------           
              //if ($tipo==4)
                //  print_r($datos);

              //if (empty($datos))  
              // RECORDAR QUE idIprod ES ITEM DE COTIZACION           
              foreach ($datos as $dato)
              {
                 $cantOrd = $dato['cantProd'] * $dato['cantMat'] ;// Cantidad a descargar
                 $nomComp = $dato['nomComp'] ;// Componente del material
                 if ($dato['desp'] > 0) // Se toma el desperdicio en el sistema por encima del del material
                    $desCorte = $dato['desp'];// Desperdicio en corte por sistema
                 $cantidad = $dato['cantidad']; // Cantidad del elemento

                 if ( $idIpd != $dato['idIprod'] ) // SE IDENTIDICA EL ITEMS DE LA ORDEN PARA CARGAR VALORES DE VARIABLES
                 {
                    $idIpd = $dato['idIprod'];              
                    $cantidad = $dato['cantidad']; // Cantidad del elemento
                    $datosP = $d->getVarCotI($idIpd); // VARIABLES Y VALOR EN LAS ORDENES
                    for ($i=1;$i<=10;$i++)
                    {               
                        if ( ($datosP[ 'var'.$i] != null) and ($datosP[ 'var'.$i] != ' ') )
                        {
                            $str = '$'.$datosP[ 'var'.$i].'='.$datosP[ 'valVar'.$i];
                         //   echo $str.' : ';                            
                            eval("\$str =$str;");     
                          //  echo $str.'<br />';
                        }
                    }
                    $datos = $d->getGeneral1("select b.idDis 
                                    from c_cotizaciones_i b
                                      where b.id = ".$idIpd);            
                    $idDis    = $datos['idDis'];      

                    // VARIABLE DE LOS COMPONENTES
                    $datosVcom = $d->getVarComp($datos['idDis']); // Variables de los componetnes del sistema en el diseño
////                    if ($tipo==4)
    //                    print_r($datosVcom);                                                                                
                    foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
//                          echo $str.' : ';                        
                          eval("\$str =$str;");   
    //                       echo $str.'<br />';  
                           if ($dat_f['variables']!='') // Variables del sistema
                           {
                               $str = $dat_f['variables'];
                               eval("\$str =$str;");
                           }                                                 
                    }  
                    // VARIABLES INTERNAS DEL DISEÑO
                    $datVari = $d->getVarTip2Dis($datos['idDis']);
                    foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
                       // Validacion en variables
                       $swVal = 0; // Validacion
                       //echo $dat_f['validacion'].'<br />';
                       if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
                       {
                           $val = trim($dat_f['validacion']);  
                           eval(
                            'if (!('.$val.')){'.
                               '$swVal=1;'.
                            '}');               
                        }    
                        if ($swVal==0)
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        else // Toma el si no de la condicion
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];   

  //                      echo 'Hoja O: '.$hojaO.' Hoja X: '.$hojaX;    
//$hojaX=1;
//           echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                   

                        $var = '$hojaX'; 
                        $pos = strpos($str, $var);
                        //echo 'd'.$pos; 
                        if ( $pos > 0 )
                           if ( ($hojaX==0) or ($hojaX=='') )
                              $hojaX = 1; 
                           
                        eval("\$str =$str;");   
                    }                                     
                  } // FIN CAMBIO DE DISEÑO ITEMS DE ORDEN DE PRODUCCION -------------------------------***********************
                  //***********************************************************************------------------******************
                
                  // --- VALIDAR SI EL MATERIAL VA O NO VA
                  $swVal = 0;
                  if ( ($dato['validacion']!='') and ($dato['validacion']!=NULL) )
                  {
                     $val = trim($dato['validacion']);  
                     eval(
                       'if (!('.$val.')){'.
                        '$swVal=1;'.
                     '}');       
//                    if ( $codMat == 'ROY61436' )
  //                     echo ' val ---'.$codMat.' : '.$str.' : '.$med.' <br />';                                                
                  }                                       
                  if ($swVal == 0)
                  { 
                    // REGISTRO EN TABLA DE DESPIECE
                    $str = $dato['formEle'];// Medida de la pieza     
                    $pos = strpos($dato['formEle'], "perimetro");
                    if ($pos>0)
                    {                
                      $str = str_replace('$perimetro','('.$dato['formPer'].')',$str);                      
                      //echo ' FORMULA PERIMETRO: '.$str.' <br />';                    
                    }
                    $pos = strpos($dato['formEle'], "area");
                    if ($pos>0)
                    {                
                      $str = str_replace('$area','('.$dato['formArea'].')',$str);                      
                      //echo ' FORMULA AREA: '.$str.' <br />';                    
                    }
                    // La formula especial reemplaza a todas las anteriores 
                    $valEsp = 0;
                    if (ltrim($dato['formEspe']) != '')
                    {
                       $str = '('.$dato['formEle'].') * ('.$dato['formEspe'].')';  
                       //$str = ' ('.$dato['formEspe'].')';  
                       $strEsp = $dato['formEspe'];  
                       eval("\$valEsp =$strEsp;");

                       //echo $codMat.': FORMULA ESPECIAL: '.$str.' : '.$valEsp.'<br />';                                               
                    }                   
                    $med = 0;
                    // Empanada momentena para eviar division entre 0  -----
                    $sw=0;  
                    $var = '$cv'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                           $sw=1;
                    if ( $sw==0 ) 
                    {
                       $var = '$ch'; 
                       $pos = strpos($str, $var);
                      // echo 'd'.$pos; 
                       if ( $pos > 0 )
                          if ( $ch == 0)
                              $sw=1;
                    }
                    $var = '$v'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                           $sw=1;
                    $var = '$hojaO'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $hojaO == 0)
                           $sw=1;                    

                    if ( $str == '|' )
                        $sw = 1;
                    // Fin empanada momentena para eviar division entre 0 
                       // echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                                         
                    if ( $sw==0 ) 
                    {
                       eval("\$med =$str;");
                    }

                    $cantEle = $cantidad;
                    if ( $med > 0 )
                    {
                       // REGISTRO EN TABLA DE DESPIECE        
                       // Verificar que el mismo material con la misma medida no se guarde 2 veces
                      $datDes = $d->getGeneral1("select id  
                                           from c_proyectos_despiece 
                                           where idCot=".$id." and tipo = ".$tipo." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd." and replace(medida,',','.')=".$med);
//                      echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                   
                       if ($datDes['id']>0) // Se modifica el registro
                       {
                          // Se modifican las cantidades del material con la misma medida en el despiece
                          $d->modGeneral("update c_proyectos_despiece set cantMat=cantMat+1 where id=".$datDes['id']);                                                           
                       }else{
                         // Guardar datos en la tabla de despieces de la orden de produccion
//                        echo $id.' - '.$codMat.' - '.$med.'- '.$valEsp.'<br />';
                         $d->modGeneral("insert into c_proyectos_despiece "
                            . "(idCot, codMat, medida, medEsp, cantProd, cantEle, limite, desCorte, formula, componente, tipo )"
                           . " values(".$id.",'".$codMat."', ".$med.",".$valEsp.",".$cantOrd.",".$cantEle.",".$ancho.", ".$desCorte.",'".$str."', '".$nomComp."', ".$tipo." )");                                       
                       }
                     }// Valdiacion medida mayor que cero 
                  }// Fin validacion inclucion del material 
                /////---- FIN GUARDAR REGISTRO DEL DESPIECE --------------------------           
                }// Fin validacion especial para recubrimientos 
            }// FIN RECORRIDO MATERIAL PARA DESPIECE  
            
          }// FIN RECORRIDO MATERIALES DE LA OBRA                
        
          ///------------------------------------------------- ////
          /////---- RECORRIDO DE MATERIALES PARA DESPIEZAR --------------------------
          ///--------------------------------------------------////        
          $datDes = $d->getGeneral("select * 
                                  from c_proyectos_despiece 
                                       where idCot=".$id." and tipo = ".$tipo." 
                                       and limite > 0 order by codMat, medida desc"); // Completo
          $matMatT = '';
          $medMatT = '';
          $marMatT = ''; // Marcar material para ubicar su pieza
          $i = 1;
          foreach($datDes as $dat) // Se sacan todas las cantidades de piezas de la orden de produccion y se guarda en una matriz
          {                
            
            $cant = $dat['cantMat']*$dat['cantEle']*$dat['cantProd'];
            //if ( $dat['codMat'] == 'ROY10136' )
            //   echo $dat['codMat'].' '.$dat['medEsp'].' '.$cant.' <br />' ;

            for ($z = 1; $z <= $cant; $z++)
            {
                $matMatT[$i] = $dat['codMat'];
                if ( $dat['medEsp'] > 0) // Formula especial                 
                   $medMatT[$i] = $dat['medida']*$dat['medEsp']; 
                else   
                   $medMatT[$i] = $dat['medida']; 

                $marMatT[$i] = 0;
                $i++;
            }
         }   
         //print_r($matMatT);
         // Materiales para optimizacion de materiales 
         $datDes = $d->getGeneral("select * 
                                  from c_proyectos_despiece 
                                       where idCot=".$id." and limite > 0  and tipo = ".$tipo."  
                                       group by codMat 
                                       order by codMat, medida desc"); // Codigos aagrupado        
         foreach($datDes as $dat) // Se sacan todas las cantidades de piezas de la orden de produccion y se guarda en una matriz
         {                
            $idD    = $dat['id'];
            $codMat = $dat['codMat'];
            $lim    = $dat['limite'];            
            $cant   = $dat['cantProd']; 
            $desCorte = $dat['desCorte']; ;// Desperdicio en corte
            $i=1;
            $unidades = 0; // Unidades requeridas de un material                       
            $distri   = ''; 
            $distriT   = ''; 
            // REALIZAR CALCULOS PARA OPTIMIZACION DE MATERIAL
             //if ( $codMat == 'ROY10136') // Pruebas 
             //     echo 'COD '.$codMat.'<br />';  

              for ($i=1; $i <= (count($matMatT)) ; $i++)           
              {   

                //echo '--'.$matMatT[$i].'-'.$medMatT[$i].' marca '.$marMatT[$i].'<br />';  
                if ( ( $codMat == $matMatT[$i] ) and ($marMatT[$i]==0) )    
                {                    
                    //if ( $matMatT[$i] == 'ROY10136') // PRuebas 
                    //   echo '--'.$medMatT[$i].'-'.$medMatT[$i].'<br />';
                    $sumMed = 0; // Suma de dedidas
                    $swUni = 0;            
                    // Recorrer para armar 1 pieza ----------------------------------------------        
                    for ($y=1; $y <= (count($matMatT)) ; $y++) 
                    {      
                        if ( ( $codMat == $matMatT[$y]  ) and ($marMatT[$y]==0) )
                        {                        
                           //if ( $matMatT[$i] == 'ROY50136A')  // Prueba 
                              //echo '---Mrca :'.$medMatT[$y].' corte:'..'<br />'; 

                           $medMat = $medMatT[$y] + $desCorte;
                           
                           if ( ($sumMed + $medMat ) <= $lim ) // Si es menor que el limite de la pieza
                           {
                               $sumMed = $sumMed + $medMat ;
                               $marMatT[$y]=1; // Marcar pieza 
                               $swUni = 1; // Activar unidad
                               $distri = $distri.$medMat.'+';
                           }else{ 
                               if ( ( $medMat ) > $lim ) // Cuando la cantidad sea mayor que la pieza tiene se divide pára obtener lo requerido
                               {
                                  $marMatT[$y]=1; // Marcar pieza  
                                  $unidades = $unidades + ( $medMat / $lim );                              
                                  //$distriP = $distriP.$medMat.'+';
                               }  
                           }                          
                        }          
                    } // Fin Recorrer para armar 1 pieza ----------------------------------------------
                    if ( $swUni == 1)
                    {                           
                        $unidades++;                    
                        $distriT = $distriT.' ('.$distri.')'  ;
                        $distri   = ''; 
                    }

                }// FIn validacion no este marcada la pieza
              }// Fin validacion manejo de limite en medida   
              // GUARDAR REQUERIMIENTO DE UNIDADES            
              $d->modGeneral("update c_proyectos_despiece 
                   Set cantReq = ".$unidades.", distribucion='".$distriT."' where id = ".$idD);                                                                   
            } // Fin validacion si tuvo no descargue de materiales 
        } // Finr ecorrido materiales
       
       /// -******--------------------------------///
       //******** FIN DESPIECE POR CONSUMO *******////        
       /// -------------------------------///-----****             
       
              
       /// -******--------------------------------///
       //******** DESPIECE POR UNIDAD *******////        
       /// -------------------------------///-----****       
ini_set('max_execution_time', 10000); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        switch ($tipo) {
            case 1: // Materiales de la base
                $datos = $d->getDespieceMmat($id, " and g.tipo=1
                 and ( ( m.ancho=0 and m.alto=0 ) or ( ii.despTotal = 1) ) # Despiece incluyendo cantidad*cantDesp*formula  
                 and ( k.idPres=0 or k.idPres is null or k.idPres = c.idPres )
                         and ( ( q.id is null and k.idOri = 0 ) or ( q.idOri = k.idOri )  ) or m.optimizar=1 # Mostrar solo los materiales que tenga orientacion 
                         group by m.codMat ");
                break;
            case 2: // Materiales adicionales del componente
                $datos = $d->getDespieceMcaUmat($id);
                break;
            case 4: // Materiales adjuntos a los recubrimientos
                $datos = $d->getDespieceMreMmat($id, "  and ( i.ancho = 0 and i.alto = 0 ) ");
                break;                        
            case 5: // Materiales adicionales por items
                $datos = $d->getMateAdicMat($id, " and ( d.ancho=0 and d.alto=0 ) ");
                break;                                        
            default:
                break;
        }       


        if ( ($tipo != 3) )// Diferente de vidrios
        {
          //print_r($datos);
          foreach ($datos as $datM)
          {         
            $codMat  = $datM['CodMat'];
            if ( $datM['descar'] != 797979790 ) // Que no tenga material descargado es la forma para recalcular 
            {

            //echo $codMat.'<br />'; 
            // Buscar materiales asociados  
            // Valor de variables de materiales en diferentes items de la orden
            $idIpd = 0;
            $datos = $d->getDespieceUmat($id, $codMat, $tipo);  // Buscar tiems que contienen material activo            
            //if (empty($datos))            
            foreach ($datos as $dato)
            {
                $cantOrd = $dato['cantProd'] * $dato['cantMat'] ;// Cantidad a descargar
                $nomComp = $dato['nomComp'] ;// Componente del material              
                $limite  = $dato['ancho'] ;// Componente del material              
                $med = '';
                $unidades = $dato['cantMat'] * $dato['cantProd'];                     
                $cantMat  = $dato['cantMat']; // Cantidad del material                
                $cantEle = 1;
                if ( $idIpd != $dato['idIprod'] ) // SE IDENTIDICA EL ITEMS DE LA ORDEN PARA CARGAR VALORES DE VARIABLES
                {
                    $idIpd    = $dato['idIprod'];              
                    $cantEle = 1;
                    
                    $datosP = $d->getVarCotI($idIpd); // VARIABLES Y VALOR EN LAS ORDENES
                    for ($i=1;$i<=10;$i++)
                    {               
                        if ( ($datosP[ 'var'.$i] != null) and ($datosP[ 'var'.$i] != ' ') )
                        {
                            $str = '$'.$datosP[ 'var'.$i].'='.$datosP[ 'valVar'.$i];
                            eval("\$str =$str;");     
                        }
                    }
                    $datos = $d->getGeneral1("select idDis 
                              from c_cotizaciones_i  
                                where id = ".$idIpd);            
                    $idDis    = $datos['idDis'];      

                    // VARIABLE DE LOS COMPONENTES
                    $datosVcom = $d->getVarComp($datos['idDis']); // Variables de los componetnes del sistema en el diseño
                    //if ($tipo==4)
//                        print_r($datosVcom);                                                                                
                    foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        //  echo $str.'<br />';                        
                        eval("\$str =$str;");   
                        if ($dat_f['variables']!='') 
                        {
                           $variables = $dat_f['variables'];
                           eval("\$str =$variables;");                          
                        }    
                            //echo $str.'<br />';                        
                    }  
                    // VARIABLES INTERNAS DEL DISEÑO
                    $datVari = $d->getVarTip2Dis($datos['idDis']);
                    foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
                       // Validacion en variables
                       $swVal = 0; // Validacion
                       //echo $dat_f['validacion'].'<br />';
                       if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
                       {
                           $val = trim($dat_f['validacion']);  
                           eval(
                            'if (!('.$val.')){'.
                               '$swVal=1;'.
                            '}');               
                        }    
                        if ($swVal==0)
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        else // Toma el si no de la condicion
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];   
                        // Empanada     
                        $sw=0;  
                        $var = '$hojaX'; 
                        $pos = strpos($str, $var);
                        if ( $pos > 0 )
                           if ( $hojaX == 0)
                                $sw=1;
                        // fin empanada     
                        if ($sw==0)      
                           eval("\$str =$str;");   
                    }                                     
                } // FIN CAMBIO DE DISEÑO ITEMS DE ORDEN DE PRODUCCION -------------------------------
                
                // --- VALIDAR SI EL MATERIAL VA O NO VA
                $swVal = 0;
                if ( ($dato['validacion']!='') and ($dato['validacion']!=NULL) )
                {
                   $val = trim($dato['validacion']);  
                   eval(
                      'if (!('.$val.')){'.
                        '$swVal=1;'.
                   '}');               
                }                     

                if ($swVal == 0)
                { 
                   // REGISTRO EN TABLA DE DESPIECE
                   $str = $dato['formEle'];// Medida de la pieza     
                   $pos = strpos($dato['formEle'], "perimetro");
                   if ($pos>0)
                   {                
                      $str = str_replace('$perimetro','('.$dato['formPer'].')',$str);                      
                   }
                   $pos = strpos($dato['formEle'], "area");
                   if ($pos>0)
                   {                
                      $str = str_replace('$area','('.$dato['formArea'].')',$str);                      
                   }
                    // La formula especial reemplaza a todas las anteriores 
                    $valEsp = 0;
                    if (ltrim($dato['formEspe']) != '')
                    {
                       //$str = '('.$dato['formEle'].') * ('.$dato['formEspe'].')';  
                       //$str = ' ('.$dato['formEspe'].')';  
                       $strEsp = $dato['formEspe'];  
                       eval("\$valEsp =$strEsp;");

                       //echo $codMat.': FORMULA ESPECIAL: '.$str.' : '.$valEsp.'<br />';                                               
                    }                   

                   $cantMat  = $dato['cantMat']; // Cantidad del material
                   $cantProd = $dato['cantProd']; // Cantidad a producir
                    
                    $med = 0;

                    // Empanada momentena para eviar division entre 0  -----
                    $sw=0;  
                    $var = '$cv'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                       {
                           $sw=1;
                       //   $str = str_replace('$cv','1',$str);                      
                       }

                    if ( $sw==0 ) 
                    {
                       $var = '$ch'; 
                       $pos = strpos($str, $var);
                       //echo 'd'.$pos; 
                       if ( $pos > 0 )
                          if ( $ch == 0)
                              $sw=1;

                    }
                       //if ($codMat=='STAC')
                          //echo $codMat.' : '.$str.' : '.$med.' <br />';                   
                    // Fin empanada momentena para eviar division entre 0 
                    if ( $sw==0 ) 
                    {
                       eval("\$med =$str;");
                    }

                   if ( $valEsp > 0)
                      $unidades = $dato['cantMat'] * $dato['cantProd'] * $med * $valEsp ;                    
                   else
                      $unidades = $dato['cantMat'] * $dato['cantProd'] * $med;                     

                   $despTotal = $dato['despTotal'];  
                   if ( ( $dato['despTotal'] == 1) and ( $dato['ancho'] > 0 ) ) // Despiece total se divide entre el tamaño del perfil si lo tuviera 
                      $unidades = $unidades / $dato['ancho'];
                   //if ( $codMat == 'T167N')
                       //echo $dato['cantMat'].' '.$med.'<br />';

//                   echo $med.' <br />';                   
                   // DESPIECE DETALLADO------------------------------------------------------
                   // Verificar que el mismo material con la misma medida no se guarde 2 veces
                   $datDes = $d->getGeneral1("select id  
                                           from c_proyectos_despiece_d 
                                           where idCot=".$id."  and tipo = ".$tipo." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd
                                            ." and round(medida,2)=".$med." and idIpord=".$idIpd  );
                   if ($datDes['id']>0) // Se modifica el registro
                   {
                     //                  if ( $codMat == 'T10X11/2IX')
                       //echo $unidades.'<br />';                    
                       // Se modifican las cantidades del material con la misma medida en el despiece
                       $d->modGeneral("update c_proyectos_despiece_d  
                                set cantReq  = cantReq  + ".$unidades.", 
                                    cantMat  = cantMat  + ".$cantMat." 
                                    where id = ".$datDes['id']);
                   }else{
                       // Guardar datos en la tabla de despieces de la orden de produccion

//echo 'valor a insertar'.$id.", ".$idIpd." ,'".$codMat."',".$med.",".$cantOrd.",".$cantEle.",".$cantMat.'<br />';

                       $d->modGeneral("insert into c_proyectos_despiece_d  
                       (idCot, idIpord , codMat, medida,  cantProd, cantEle, cantMat, limite, desCorte, cantReq, tipo )
                       values(".$id.", ".$idIpd." ,'".$codMat."',".$med.",".$cantOrd.",".$cantEle.",".$cantMat.",0,0, ".$unidades.", ".$tipo." )");                                       
                    }                    
                   //************-----------------------------------------------------------
                    
                   // DEPIECE TOTAL ----------------------------------------------------------
                   // Verificar que el mismo material con la misma medida no se guarde 2 veces
                   $datDes = $d->getGeneral1("select id  
                                           from c_proyectos_despiece  
                                           where idCot=".$id."  and tipo = ".$tipo." 
                                           and codMat='".$codMat."'");
                   if ($datDes['id']>0) // Se modifica el registro
                   {
                        //               if ( $codMat == 'T10X11/2IX')
                      // echo $datDes['id'].' = '.$unidades.'<br />';
                       // Se modifican las cantidades del material con la misma medida en el despiece
                       $d->modGeneral("update c_proyectos_despiece  
                                set cantReq  = cantReq  + ".$unidades.", 
                                    cantMat  = cantMat  + ".$cantMat."  
                                    where id = ".$datDes['id']);
                   }else{
                       // Guardar datos en la tabla de despieces de la orden de produccion
                       $d->modGeneral("insert into c_proyectos_despiece  
                       (idCot, codMat, medida, medEsp ,cantEle, cantMat, limite, desCorte, cantReq, formula, componente, tipo  )
                       values(".$id.",'".$codMat."',".$med.",".$valEsp." ,".$cantEle.",".$cantMat.",".$limite.",0, ".$unidades." ,'".$str."', '".$nomComp."' , ".$tipo.")"); 
                    }                    
                    //************-----------------------------------------------------------
                    
                }// Fin validacion inclucion del material 
                /////---- FIN GUARDAR REGISTRO DEL DESPIECE --------------------------           
              }// FIN VALIDACION QUE NO TENGA DESCRGUES
            }// FIN RECORRIDO MATERIAL PARA DESPIECE  
          }// FIN RECORRIDO MATERIALES DE LA OBRA                       
        }
       /// -******--------------------------------///
       //******** FIN DESPIECE POR UNIDAD *******////        
       /// -------------------------------///-----****          
       
   }// Despiece por proyectos 3    


   // Despiece de materiales por consumo 
   public function getDespieceMatConsumo($id, $tipo)
   {
        $d = new AlbumTable($this->adapter); 
        $datVarG = $d->getVariables(""); // Variables generales
        
        /// DESPIECE POR PIEZAS LINEALES Y UNIDADES
        // TODAS LAS VARIABLES         
        foreach ($datVarG as $dat_f)
        { 
            $str='$'.$dat_f['variable'].'=0';
            eval("\$str =$str;");  
        }        
//$hojaO =0;
        /// -******----------------------------///
        //******** DESPIECE POR CONSUMO *******////        
        /// ----------------------------///-----****        
        ini_set('max_execution_time', 10000); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        switch ($tipo) {
            case 1: // Materiales de la base
                $datos = $d->getDespieceMmat($id, " and g.tipo=1 and ii.despTotal = 0 and ( m.ancho > 0 and m.alto = 0 )"
                        . "and ( kkk.idPres=0 or kkk.idPres is null or kkk.idPres = c.idPres ) 
                         and ( ( q.id is null and k.idOri = 0 ) or ( q.idOri = k.idOri )  )  and  m.optimizar=0 # Mostrar solo los materiales que tenga orientacion 
                         group by m.codMat ");
                break;
            case 2: // Materiales adicionales del componente
                $datos = $d->getDespieceMcaMat($id);
                break;
            case 3: // Materiales por recubrimientos
                $datos = $d->getDespieceMre2Mat($id); # NUeva formula para verificar fallos
                break;            
            case 4: // Materiales adjuntos a los recubrimientos
                $datos = $d->getDespieceMreMmat($id, " and ( i.ancho > 0.001 and i.alto = 0 ) ");
                break;                        
            case 5: // Materiales adicionales en items de cotizaciones
                $datos = $d->getMateAdicMat($id, " and ( d.ancho>0 and d.alto=0 ) ");
                break;                                        
            default:
                break;
        }
            if (  $tipo == 4  ) // Recubrimientos 
                  echo $datos; 
           //$d->modGeneral("update  c_pre_ordenes_despiece set cantMat = 0 where idPord=".$id); // Poner todo en 0     
          foreach ($datos as $datM)
          {         
            $codMat  = $datM['CodMat'];               
            if ( $datM['descar'] != -8989899999 ) // Que no tenga material descargado es la forma para recalcular 
            {
      // Validar 
            $ancho   = $datM['ancho']; // Ancho de la pieza
                        
            if (  $tipo == 3  ) // Recubrimientos 
            {
               $cantOrd  = $datM['cantOrd']; 
               $cantEle  = 1;
               $ancho    = 0;
               $desCorte = 0;
               //$med      = round( $datM['medida'], 3 );
               $med      = $datM['medida'];
               // Verificar que el mismo material con la misma medida no se guarde 2 veces
               $datDes = $d->getGeneral1("select id  
                                           from c_proyectos_despiece 
                                           where idCot=".$id." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd." and round(medida,2)=".$med);
               //print_r($datDes);
               if ($datDes['id']==0) // Se modifica el registro
               { 
                  // Guardar datos en la tabla de despieces de la orden de produccion
                  $d->modGeneral("insert into c_proyectos_despiece "
                          . "(idCot, codMat, medida, cantProd, cantEle, cantReq, limite, desCorte, componente )"
                        . " values(".$id.",'".$codMat."',".$med.",".$cantOrd.",".$cantEle.", ".$med." ,".$ancho.", ".$desCorte.",'Recubrimiento' )");
               }else{
                  $d->modGeneral("update c_proyectos_despiece set cantReq = ".$med." where idCot=".$id." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd." and round(medida,2)=".$med);                
               }            

            }else{// Manejo de materiales con optimizacion 
               
              $nomLin  = $datM['nomLin']; // Linea de materiales
              $existen = $datM['existen']; // Existencia actual            
              $desCorte = $datM['desCorte'];// Desperdicio en corte
              $cantOrd  = 0;
              //echo $codMat.'<br />'; 
              // Buscar materiales asociados  
              $matVal = array(); // Medidas del material
              // Valor de variables de materiales en diferentes items de la orden
              $idIpd = 0;

              $datos = $d->getDespieceMat($id, $codMat, $tipo);  // Buscar tiems que contienen material activo ------------------------------           
              //if ($tipo==4)
                //  print_r($datos);

              //if (empty($datos))  
              // RECORDAR QUE idIprod ES ITEM DE COTIZACION           
              foreach ($datos as $dato)
              {
                 $cantOrd = $dato['cantProd'] * $dato['cantMat'] ;// Cantidad a descargar
                 $nomComp = $dato['nomComp'] ;// Componente del material
                 if ($dato['desp'] > 0) // Se toma el desperdicio en el sistema por encima del del material
                    $desCorte = $dato['desp'];// Desperdicio en corte por sistema
                 $cantidad = $dato['cantidad']; // Cantidad del elemento

                 if ( $idIpd != $dato['idIprod'] ) // SE IDENTIDICA EL ITEMS DE LA ORDEN PARA CARGAR VALORES DE VARIABLES
                 {
                    $idIpd = $dato['idIprod'];              
                    $cantidad = $dato['cantidad']; // Cantidad del elemento
                    $datosP = $d->getVarCotI($idIpd); // VARIABLES Y VALOR EN LAS ORDENES
                    for ($i=1;$i<=10;$i++)
                    {               
                        if ( ($datosP[ 'var'.$i] != null) and ($datosP[ 'var'.$i] != ' ') )
                        {
                            $str = '$'.$datosP[ 'var'.$i].'='.$datosP[ 'valVar'.$i];
                         //   echo $str.' : ';                            
                            eval("\$str =$str;");     
                          //  echo $str.'<br />';
                        }
                    }
                    $datos = $d->getGeneral1("select b.idDis 
                                    from c_cotizaciones_i b
                                      where b.id = ".$idIpd);            
                    $idDis    = $datos['idDis'];      

                    // VARIABLE DE LOS COMPONENTES
                    $datosVcom = $d->getVarComp($datos['idDis']); // Variables de los componetnes del sistema en el diseño
////                    if ($tipo==4)
    //                    print_r($datosVcom);                                                                                
                    foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
//                          echo $str.' : ';                        
                          eval("\$str =$str;");   
    //                       echo $str.'<br />';  
                           if ($dat_f['variables']!='') // Variables del sistema
                           {
                               $str = $dat_f['variables'];
                               eval("\$str =$str;");
                           }                                                 
                    }  
                    // VARIABLES INTERNAS DEL DISEÑO
                    $datVari = $d->getVarTip2Dis($datos['idDis']);
                    foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
                       // Validacion en variables
                       $swVal = 0; // Validacion
                       //echo $dat_f['validacion'].'<br />';
                       if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
                       {
                           $val = trim($dat_f['validacion']);  
                           eval(
                            'if (!('.$val.')){'.
                               '$swVal=1;'.
                            '}');               
                        }    
                        if ($swVal==0)
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        else // Toma el si no de la condicion
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];   

  //                      echo 'Hoja O: '.$hojaO.' Hoja X: '.$hojaX;    
//$hojaX=1;
//           echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                   

                        $var = '$hojaX'; 
                        $pos = strpos($str, $var);
                        //echo 'd'.$pos; 
                        if ( $pos > 0 )
                           if ( ($hojaX==0) or ($hojaX=='') )
                              $hojaX = 1; 
                           
                        eval("\$str =$str;");   
                    }                                     
                  } // FIN CAMBIO DE DISEÑO ITEMS DE ORDEN DE PRODUCCION -------------------------------***********************
                  //***********************************************************************------------------******************
                
                  // --- VALIDAR SI EL MATERIAL VA O NO VA
                  $swVal = 0;
                  if ( ($dato['validacion']!='') and ($dato['validacion']!=NULL) )
                  {
                     $val = trim($dato['validacion']);  
                     eval(
                       'if (!('.$val.')){'.
                        '$swVal=1;'.
                     '}');       
//                    if ( $codMat == 'ROY61436' )
  //                     echo ' val ---'.$codMat.' : '.$str.' : '.$med.' <br />';                                                
                  }                                       
                  if ($swVal == 0)
                  { 
                    // REGISTRO EN TABLA DE DESPIECE
                    $str = $dato['formEle'];// Medida de la pieza     
                    $pos = strpos($dato['formEle'], "perimetro");
                    if ($pos>0)
                    {                
                      $str = str_replace('$perimetro','('.$dato['formPer'].')',$str);                      
                      //echo ' FORMULA PERIMETRO: '.$str.' <br />';                    
                    }
                    $pos = strpos($dato['formEle'], "area");
                    if ($pos>0)
                    {                
                      $str = str_replace('$area','('.$dato['formArea'].')',$str);                      
                      //echo ' FORMULA AREA: '.$str.' <br />';                    
                    }
                    // La formula especial reemplaza a todas las anteriores 
                    $valEsp = 0;
                    if (ltrim($dato['formEspe']) != '')
                    {
                       $str = '('.$dato['formEle'].') * ('.$dato['formEspe'].')';  
                       //$str = ' ('.$dato['formEspe'].')';  
                       $strEsp = $dato['formEspe'];  
                       eval("\$valEsp =$strEsp;");

                       //echo $codMat.': FORMULA ESPECIAL: '.$str.' : '.$valEsp.'<br />';                                               
                    }                   
                    $med = 0;
                    // Empanada momentena para eviar division entre 0  -----
                    $sw=0;  
                    $var = '$cv'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                           $sw=1;
                    if ( $sw==0 ) 
                    {
                       $var = '$ch'; 
                       $pos = strpos($str, $var);
                      // echo 'd'.$pos; 
                       if ( $pos > 0 )
                          if ( $ch == 0)
                              $sw=1;
                    }
                    $var = '$v'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                           $sw=1;
                    $var = '$hojaO'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $hojaO == 0)
                           $sw=1;                    

                    if ( $str == '|' )
                        $sw = 1;
                    // Fin empanada momentena para eviar division entre 0 
                       // echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                                         
                    if ( $sw==0 ) 
                    {
                       eval("\$med =$str;");
                    }

                    $cantEle = $cantidad;
                    if ( $med > 0 )
                    {
                       // REGISTRO EN TABLA DE DESPIECE        
                       // Verificar que el mismo material con la misma medida no se guarde 2 veces
                      $datDes = $d->getGeneral1("select id  
                                           from c_proyectos_despiece 
                                           where idCot=".$id." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd." and replace(medida,',','.')=".$med);
//                      echo $idIpd.' '.$nomComp.' ---'.$codMat.' : '.$str.' : '.$med.' <br />';                   
                       if ($datDes['id']>0) // Se modifica el registro
                       {
                          // Se modifican las cantidades del material con la misma medida en el despiece
                          $d->modGeneral("update c_proyectos_despiece set cantMat=cantMat+1 where id=".$datDes['id']);                                                           
                       }else{
                         // Guardar datos en la tabla de despieces de la orden de produccion
//                        echo $id.' - '.$codMat.' - '.$med.'- '.$valEsp.'<br />';
                         $d->modGeneral("insert into c_proyectos_despiece "
                            . "(idCot, codMat, medida, medEsp, cantProd, cantEle, limite, desCorte, formula, componente )"
                           . " values(".$id.",'".$codMat."', ".$med.",".$valEsp.",".$cantOrd.",".$cantEle.",".$ancho.", ".$desCorte.",'".$str."', '".$nomComp."' )");                                       
                       }
                     }// Valdiacion medida mayor que cero 
                  }// Fin validacion inclucion del material 
                /////---- FIN GUARDAR REGISTRO DEL DESPIECE --------------------------           
                }// Fin validacion especial para recubrimientos 
            }// FIN RECORRIDO MATERIAL PARA DESPIECE  
            
          }// FIN RECORRIDO MATERIALES DE LA OBRA                
        
          ///------------------------------------------------- ////
          /////---- RECORRIDO DE MATERIALES PARA DESPIEZAR --------------------------
          ///--------------------------------------------------////        
          $datDes = $d->getGeneral("select * 
                                  from c_proyectos_despiece 
                                       where idCot=".$id."  
                                       and limite > 0 order by codMat, medida desc"); // Completo
          $matMatT = '';
          $medMatT = '';
          $marMatT = ''; // Marcar material para ubicar su pieza
          $i = 1;
          foreach($datDes as $dat) // Se sacan todas las cantidades de piezas de la orden de produccion y se guarda en una matriz
          {                
            
            $cant = $dat['cantMat']*$dat['cantEle']*$dat['cantProd'];
            //if ( $dat['codMat'] == 'ROY10136' )
            //   echo $dat['codMat'].' '.$dat['medEsp'].' '.$cant.' <br />' ;

            for ($z = 1; $z <= $cant; $z++)
            {
                $matMatT[$i] = $dat['codMat'];
                if ( $dat['medEsp'] > 0) // Formula especial                 
                   $medMatT[$i] = $dat['medida']*$dat['medEsp']; 
                else   
                   $medMatT[$i] = $dat['medida']; 

                $marMatT[$i] = 0;
                $i++;
            }
         }   
         //print_r($matMatT);
         // Materiales para optimizacion de materiales 
         $datDes = $d->getGeneral("select * 
                                  from c_proyectos_despiece 
                                       where idCot=".$id." and limite > 0  
                                       group by codMat 
                                       order by codMat, medida desc"); // Codigos aagrupado        
         foreach($datDes as $dat) // Se sacan todas las cantidades de piezas de la orden de produccion y se guarda en una matriz
         {                
            $idD    = $dat['id'];
            $codMat = $dat['codMat'];
            $lim    = $dat['limite'];            
            $cant   = $dat['cantProd']; 
            $desCorte = $dat['desCorte']; ;// Desperdicio en corte
            $i=1;
            $unidades = 0; // Unidades requeridas de un material                       
            $distri   = ''; 
            $distriT   = ''; 
            // REALIZAR CALCULOS PARA OPTIMIZACION DE MATERIAL
             //if ( $codMat == 'ROY10136') // Pruebas 
             //     echo 'COD '.$codMat.'<br />';  

              for ($i=1; $i <= (count($matMatT)) ; $i++)           
              {   

                //echo '--'.$matMatT[$i].'-'.$medMatT[$i].' marca '.$marMatT[$i].'<br />';  
                if ( ( $codMat == $matMatT[$i] ) and ($marMatT[$i]==0) )    
                {                    
                    //if ( $matMatT[$i] == 'ROY10136') // PRuebas 
                    //   echo '--'.$medMatT[$i].'-'.$medMatT[$i].'<br />';
                    $sumMed = 0; // Suma de dedidas
                    $swUni = 0;            
                    // Recorrer para armar 1 pieza ----------------------------------------------        
                    for ($y=1; $y <= (count($matMatT)) ; $y++) 
                    {      
                        if ( ( $codMat == $matMatT[$y]  ) and ($marMatT[$y]==0) )
                        {                        
                           //if ( $matMatT[$i] == 'ROY50136A')  // Prueba 
                              //echo '---Mrca :'.$medMatT[$y].' corte:'..'<br />'; 

                           $medMat = $medMatT[$y] + $desCorte;
                           
                           if ( ($sumMed + $medMat ) <= $lim ) // Si es menor que el limite de la pieza
                           {
                               $sumMed = $sumMed + $medMat ;
                               $marMatT[$y]=1; // Marcar pieza 
                               $swUni = 1; // Activar unidad
                               $distri = $distri.$medMat.'+';
                           }else{ 
                               if ( ( $medMat ) > $lim ) // Cuando la cantidad sea mayor que la pieza tiene se divide pára obtener lo requerido
                               {
                                  $marMatT[$y]=1; // Marcar pieza  
                                  $unidades = $unidades + ( $medMat / $lim );                              
                                  //$distriP = $distriP.$medMat.'+';
                               }  
                           }                          
                        }          
                    } // Fin Recorrer para armar 1 pieza ----------------------------------------------
                    if ( $swUni == 1)
                    {                           
                        $unidades++;                    
                        $distriT = $distriT.' ('.$distri.')'  ;
                        $distri   = ''; 
                    }

                }// FIn validacion no este marcada la pieza
              }// Fin validacion manejo de limite en medida   
              // GUARDAR REQUERIMIENTO DE UNIDADES            
              $d->modGeneral("update c_proyectos_despiece 
                   Set cantReq = ".$unidades.", distribucion='".$distriT."' where id = ".$idD);                                                                   
            } // Fin validacion si tuvo no descargue de materiales 
        } // Finr ecorrido materiales
       
       /// -******--------------------------------///
       //******** FIN DESPIECE POR CONSUMO *******////        
       /// -------------------------------///-----****                   
              

       
   }// Despiece por onsumo de materiales

   // Despiece de materiales por consumo 
   public function getDespieceMatUnidades($id, $tipo)
   {
        $d = new AlbumTable($this->adapter); 
        $datVarG = $d->getVariables(""); // Variables generales
        
        /// DESPIECE POR PIEZAS LINEALES Y UNIDADES
        // TODAS LAS VARIABLES         
        foreach ($datVarG as $dat_f)
        { 
            $str='$'.$dat_f['variable'].'=0';
            eval("\$str =$str;");  
        }               
              
       /// -******--------------------------------///
       //******** DESPIECE POR UNIDAD *******////        
       /// -------------------------------///-----****       
ini_set('max_execution_time', 10000); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        switch ($tipo) {
            case 1: // Materiales de la base
                $datos = $d->getDespieceMmat($id, " and g.tipo=1
                 and ( ( m.ancho=0 and m.alto=0 ) or ( ii.despTotal = 1) ) # Despiece incluyendo cantidad*cantDesp*formula  
                 and ( k.idPres=0 or k.idPres is null or k.idPres = c.idPres )
                         and ( ( q.id is null and k.idOri = 0 ) or ( q.idOri = k.idOri )  ) or m.optimizar=1 # Mostrar solo los materiales que tenga orientacion 
                         group by m.codMat ");
                break;
            case 2: // Materiales adicionales del componente
                $datos = $d->getDespieceMcaUmat($id);
                break;
            case 4: // Materiales adjuntos a los recubrimientos
                $datos = $d->getDespieceMreMmat($id, "  and ( i.ancho = 0 and i.alto = 0 ) ");
                break;                        
            case 5: // Materiales adicionales por items
                $datos = $d->getMateAdicMat($id, " and ( d.ancho=0 and d.alto=0 ) ");
                break;                                        
            default:
                break;
        }       


        if ( ($tipo != 3) and ($tipo != 4) )// Diferente de vidrios
        {
          //print_r($datos);
          foreach ($datos as $datM)
          {         
            $codMat  = $datM['CodMat'];
            if ( $datM['descar'] != 797979790 ) // Que no tenga material descargado es la forma para recalcular 
            {

            //echo $codMat.'<br />'; 
            // Buscar materiales asociados  
            // Valor de variables de materiales en diferentes items de la orden
            $idIpd = 0;
            $datos = $d->getDespieceUmat($id, $codMat, $tipo);  // Buscar tiems que contienen material activo            
            //if (empty($datos))            
            foreach ($datos as $dato)
            {
                $cantOrd = $dato['cantProd'] * $dato['cantMat'] ;// Cantidad a descargar
                $nomComp = $dato['nomComp'] ;// Componente del material              
                $limite  = $dato['ancho'] ;// Componente del material              
                $med = '';
                $unidades = $dato['cantMat'] * $dato['cantProd'];                     
                $cantMat  = $dato['cantMat']; // Cantidad del material                
                $cantEle = 1;
                if ( $idIpd != $dato['idIprod'] ) // SE IDENTIDICA EL ITEMS DE LA ORDEN PARA CARGAR VALORES DE VARIABLES
                {
                    $idIpd    = $dato['idIprod'];              
                    $cantEle = 1;
                    
                    $datosP = $d->getVarCotI($idIpd); // VARIABLES Y VALOR EN LAS ORDENES
                    for ($i=1;$i<=10;$i++)
                    {               
                        if ( ($datosP[ 'var'.$i] != null) and ($datosP[ 'var'.$i] != ' ') )
                        {
                            $str = '$'.$datosP[ 'var'.$i].'='.$datosP[ 'valVar'.$i];
                            eval("\$str =$str;");     
                        }
                    }
                    $datos = $d->getGeneral1("select idDis 
                              from c_cotizaciones_i  
                                where id = ".$idIpd);            
                    $idDis    = $datos['idDis'];      

                    // VARIABLE DE LOS COMPONENTES
                    $datosVcom = $d->getVarComp($datos['idDis']); // Variables de los componetnes del sistema en el diseño
                    //if ($tipo==4)
//                        print_r($datosVcom);                                                                                
                    foreach ($datosVcom as $dat_f){ // VARIABLES DE COMPONENTES 
                        $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        //  echo $str.'<br />';                        
                        eval("\$str =$str;");   
                        if ($dat_f['variables']!='') 
                        {
                           $variables = $dat_f['variables'];
                           eval("\$str =$variables;");                          
                        }    
                            //echo $str.'<br />';                        
                    }  
                    // VARIABLES INTERNAS DEL DISEÑO
                    $datVari = $d->getVarTip2Dis($datos['idDis']);
                    foreach ($datVari as $dat_f){ // VARIABLES INTERNAS CON VALORES
                       // Validacion en variables
                       $swVal = 0; // Validacion
                       //echo $dat_f['validacion'].'<br />';
                       if ( ($dat_f['validacion']!='') and ($dat_f['validacion']!=NULL) )
                       {
                           $val = trim($dat_f['validacion']);  
                           eval(
                            'if (!('.$val.')){'.
                               '$swVal=1;'.
                            '}');               
                        }    
                        if ($swVal==0)
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor'];
                        else // Toma el si no de la condicion
                            $str='$'.$dat_f['variable'].'='.$dat_f['valor2'];   
                        // Empanada     
                        $sw=0;  
                        $var = '$hojaX'; 
                        $pos = strpos($str, $var);
                        if ( $pos > 0 )
                           if ( $hojaX == 0)
                                $sw=1;
                        // fin empanada     
                        if ($sw==0)      
                           eval("\$str =$str;");   
                    }                                     
                } // FIN CAMBIO DE DISEÑO ITEMS DE ORDEN DE PRODUCCION -------------------------------
                
                // --- VALIDAR SI EL MATERIAL VA O NO VA
                $swVal = 0;
                if ( ($dato['validacion']!='') and ($dato['validacion']!=NULL) )
                {
                   $val = trim($dato['validacion']);  
                   eval(
                      'if (!('.$val.')){'.
                        '$swVal=1;'.
                   '}');               
                }                     

                if ($swVal == 0)
                { 
                   // REGISTRO EN TABLA DE DESPIECE
                   $str = $dato['formEle'];// Medida de la pieza     
                   $pos = strpos($dato['formEle'], "perimetro");
                   if ($pos>0)
                   {                
                      $str = str_replace('$perimetro','('.$dato['formPer'].')',$str);                      
                   }
                   $pos = strpos($dato['formEle'], "area");
                   if ($pos>0)
                   {                
                      $str = str_replace('$area','('.$dato['formArea'].')',$str);                      
                   }
                    // La formula especial reemplaza a todas las anteriores 
                    $valEsp = 0;
                    if (ltrim($dato['formEspe']) != '')
                    {
                       //$str = '('.$dato['formEle'].') * ('.$dato['formEspe'].')';  
                       //$str = ' ('.$dato['formEspe'].')';  
                       $strEsp = $dato['formEspe'];  
                       eval("\$valEsp =$strEsp;");

                       //echo $codMat.': FORMULA ESPECIAL: '.$str.' : '.$valEsp.'<br />';                                               
                    }                   

                   $cantMat  = $dato['cantMat']; // Cantidad del material
                   $cantProd = $dato['cantProd']; // Cantidad a producir
                    
                    $med = 0;

                    // Empanada momentena para eviar division entre 0  -----
                    $sw=0;  
                    $var = '$cv'; 
                    $pos = strpos($str, $var);
                    //echo 'd'.$pos; 
                    if ( $pos > 0 )
                       if ( $cv == 0)
                       {
                           $sw=1;
                       //   $str = str_replace('$cv','1',$str);                      
                       }

                    if ( $sw==0 ) 
                    {
                       $var = '$ch'; 
                       $pos = strpos($str, $var);
                       //echo 'd'.$pos; 
                       if ( $pos > 0 )
                          if ( $ch == 0)
                              $sw=1;

                    }
                       //if ($codMat=='STAC')
                          //echo $codMat.' : '.$str.' : '.$med.' <br />';                   
                    // Fin empanada momentena para eviar division entre 0 
                    if ( $sw==0 ) 
                    {
                       eval("\$med =$str;");
                    }

                   if ( $valEsp > 0)
                      $unidades = $dato['cantMat'] * $dato['cantProd'] * $med * $valEsp ;                    
                   else
                      $unidades = $dato['cantMat'] * $dato['cantProd'] * $med;                     

                   $despTotal = $dato['despTotal'];  
                   if ( ( $dato['despTotal'] == 1) and ( $dato['ancho'] > 0 ) ) // Despiece total se divide entre el tamaño del perfil si lo tuviera 
                      $unidades = $unidades / $dato['ancho'];
                   //if ( $codMat == 'T167N')
                       //echo $dato['cantMat'].' '.$med.'<br />';

//                   echo $med.' <br />';                   
                   // DESPIECE DETALLADO------------------------------------------------------
                   // Verificar que el mismo material con la misma medida no se guarde 2 veces
                   $datDes = $d->getGeneral1("select id  
                                           from c_proyectos_despiece_d 
                                           where idCot=".$id." 
                                           and codMat='".$codMat."' and cantProd=".$cantOrd
                                            ." and round(medida,2)=".$med." and idIpord=".$idIpd  );
                   if ($datDes['id']>0) // Se modifica el registro
                   {
                     //                  if ( $codMat == 'T10X11/2IX')
                       //echo $unidades.'<br />';                    
                       // Se modifican las cantidades del material con la misma medida en el despiece
                       $d->modGeneral("update c_proyectos_despiece_d  
                                set cantReq  = cantReq  + ".$unidades.", 
                                    cantMat  = cantMat  + ".$cantMat." 
                                    where id = ".$datDes['id']);
                   }else{
                       // Guardar datos en la tabla de despieces de la orden de produccion

//echo 'valor a insertar'.$id.", ".$idIpd." ,'".$codMat."',".$med.",".$cantOrd.",".$cantEle.",".$cantMat.'<br />';

                       $d->modGeneral("insert into c_proyectos_despiece_d  
                       (idCot, idIpord , codMat, medida,  cantProd, cantEle, cantMat, limite, desCorte, cantReq )
                       values(".$id.", ".$idIpd." ,'".$codMat."',".$med.",".$cantOrd.",".$cantEle.",".$cantMat.",0,0, ".$unidades." )");                                       
                    }                    
                   //************-----------------------------------------------------------
                    
                   // DEPIECE TOTAL ----------------------------------------------------------
                   // Verificar que el mismo material con la misma medida no se guarde 2 veces
                   $datDes = $d->getGeneral1("select id  
                                           from c_proyectos_despiece  
                                           where idCot=".$id." 
                                           and codMat='".$codMat."'");
                   if ($datDes['id']>0) // Se modifica el registro
                   {
                        //               if ( $codMat == 'T10X11/2IX')
                      // echo $datDes['id'].' = '.$unidades.'<br />';
                       // Se modifican las cantidades del material con la misma medida en el despiece
                       $d->modGeneral("update c_proyectos_despiece  
                                set cantReq  = cantReq  + ".$unidades.", 
                                    cantMat  = cantMat  + ".$cantMat."  
                                    where id = ".$datDes['id']);
                   }else{
                       // Guardar datos en la tabla de despieces de la orden de produccion
                       $d->modGeneral("insert into c_proyectos_despiece  
                       (idCot, codMat, medida, medEsp ,cantEle, cantMat, limite, desCorte, cantReq, formula, componente  )
                       values(".$id.",'".$codMat."',".$med.",".$valEsp." ,".$cantEle.",".$cantMat.",".$limite.",0, ".$unidades." ,'".$str."', '".$nomComp."' )"); 
                    }                    
                    //************-----------------------------------------------------------
                    
                }// Fin validacion inclucion del material 
                /////---- FIN GUARDAR REGISTRO DEL DESPIECE --------------------------           
              }// FIN VALIDACION QUE NO TENGA DESCRGUES
            }// FIN RECORRIDO MATERIAL PARA DESPIECE  
          }// FIN RECORRIDO MATERIALES DE LA OBRA                       
        }
       /// -******--------------------------------///
       //******** FIN DESPIECE POR UNIDAD *******////        
       /// -------------------------------///-----****          
       
   }// Despiece por unidades

}








