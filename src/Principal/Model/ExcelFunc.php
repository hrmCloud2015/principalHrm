<?php
/** STANDAR MAESTROS NISSI  */
// (C): Cambiar en el controlador 
namespace Principal\Model;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

use Principal\Model\AlbumTable;        // Libreria de datos
use Constructor\Model\Entity\Constructor; // (C)
use Constructor\Model\Entity\ConstructorE; // (C)

require './vendor/Classes/PHPExcel.php'; 
use PHPExcel; 
use PHPExcel_IOFactory;
use PHPExcel_Style_Fill;

class ExcelFunc extends AbstractTableGateway
{
   // Armar canalizador de reportes por Id Nomina 
   public function listexcel($datos, $titulo) 
   {                           
      // TITULOS DE LAS COLUMNAS EN EXCEL
      $columnas = '';
      $datosC   = '';
      if ($datos!='')
      {
         foreach($datos as $arr2)
         {
           foreach($arr2 as $id=>$text){
             if ($columnas == '')
                 $columnas = $id;
             else
                 $columnas = $columnas.','.$id;
             
             if ($datosC == '')
                 $datosC = $id;
             else
                 $datosC = $datosC.','.$id;             

            }
            break; 
         }
      }// FIN TITULOS DE LAS COLUMNAS EN EXCEL

          //// INICIO SALIDA ARCHIVO EXCEL ////
          error_reporting(E_ALL);
          ini_set('display_errors', TRUE);
          ini_set('display_startup_errors', TRUE);
 
          define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');          
          
          $objReader = PHPExcel_IOFactory::createReader('Excel2007');
          $objPHPExcel = $objReader->load("./vendor/Classes/templates/conlogo.xlsx");
          
          ///// LLenar hoja de excel con datos ////
          //* titulo
          $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('D3', strtoupper($titulo));
          
          $colTit = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z",
                          "AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ",);
          $numTit = 4;
          //* ubicar nombre de las columnas 
          $titulo='';$sw=0;$idC=0;
          for( $i=0; $i<=strlen($columnas); $i++)
          {
             $caracter = substr($columnas,$i,1);      
             if ($caracter!=',')
                $titulo=$titulo.$caracter;
             else 
                $sw=1;
         
             if ( ($sw==1)or ($i==strlen($columnas)) )     
             { 
               $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colTit[$idC].$numTit, $titulo); // Impresion titulos de columnas
               $colTitF = $colTit[$idC];
               $titulo='';$sw=0;$idC++;               
             }
          }    
          
          $objPHPExcel->getActiveSheet()->setAutoFilter('A'.$numTit.':'.$colTitF.$numTit);
          
          $objPHPExcel->getActiveSheet()
            ->getStyle('A'.$numTit.':'.$colTitF.$numTit)
            ->getFill()
            ->applyFromArray( 
                    array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' =>'F0F8FF')));
          
          //* ubicar nombre de los datos de las columnas
          $arrCol = array(); // MAtriz con nombre de los campos
          $arrTco = array(); // Matriz con las letras de la cabceera
          $titulo='';$sw=0;$idC=0;
          for( $i=0; $i<=strlen($datosC); $i++)
          {
             $caracter = substr($datosC,$i,1);      
             if ($caracter!=',')
                $titulo=$titulo.$caracter;
             else 
                $sw=1;
         
             if ( ($sw==1)or ($i==strlen($datosC)) )     
             { 
               $arrCol[$idC] = $titulo;
               $arrTco[$idC] = $colTit[$idC];
               
               $titulo='';$sw=0;$idC++;               
             }
          }        
          //print_r($arrTco);
          //print_r($arrCol);
          //--- Recorrido de datos 
          $i = $numTit + 1; // Inicio de columnas
          foreach ($datos as $dato){
            $y=0;  
            foreach ($arrTco as $datTcam){
                $letra = $datTcam;
                $campo = $arrCol[$y];
                $y++;  
                $valor=$dato[''.ltrim($campo)]; // Ojo los espacios con ltrim, sino sale error 
                //echo $y.' '.$letra.' : '.$valor.'<br />';
                $objPHPExcel->getActiveSheet()->setCellValue($letra.$i, $valor );
            }
            $i++;
          }
          //--- Fin recorrido de datos 
         ?><?php $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
          $objWriter->save(str_replace('.php', '.xlsx', "./vendor/Classes/temp/reportTabla2.xlsx"));
         //  Redirect output to a clientâ€™s web browser (Excel5)
          //header('Content-Type: application/vnd.ms-excel');
          //header('Content-Disposition: attachment;filename="reportTabla2.xlsx"');
          //$objWriter->save('php://output');                                     
            
   }      



}
