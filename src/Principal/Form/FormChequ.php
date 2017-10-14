<?php
/**
 * Especial camops lista de chqueos NISSI
 * @copyright 2013
 */
namespace Principal\Form;

use Zend\Captcha; 
use Zend\Form\Form;
use Zend\Form\Element;
use Zend\Form\Factory;


class FormChequ extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('album');
        $this->setAttribute('method', 'post');        
        

        // resutados 
        for ($i=1;$i<=40;$i++)
        {
          $this->add(array( 
              'name' => 'res'.$i, 
              'type' => 'textarea', 
              'attributes'  => array( 
                 'required' => 'required', 
                  'id'      => 'res'.$i
              ), 
              'options' => array( 
                 'label' => '',
              ), 
          ));                         
        }

        for ($i=1;$i<=40;$i++)
        {
          $this->add(array( 
              'name' => 'estado'.$i, 
              'type' => 'select', 
              'attributes'  => array( 
                 'required' => 'required', 
                  'id'      => 'estado'.$i
              ), 
              'options' => array( 
                 'label' => '',
                 'empty_option'    => '--- Seleccion el estado ---',
                 'value_options' => array(
                         '1' => 'Aprobado',
                         '2' => 'No aprobado',
                     ), 
              ), 
          ));    
        }

        
    }// Fin funcion
}