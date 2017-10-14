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


class FormCon extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('album');
        $this->setAttribute('method', 'post');        

        // Id
        for ($i=1;$i<=80;$i++)
        {
          $this->add(array( 
              'name' => 'idi'.$i, 
              'type' => 'hidden', 
              'attributes'  => array( 
                  'id'      => 'res'.$i
              ), 
              'options' => array( 
                 'label' => '',
              ), 
          ));                         
        }        
        
        // Checkbox
        for ($i=1;$i<=80;$i++)
        {
          $this->add(array( 
              'name' => 'checki'.$i, 
              'type' => 'Checkbox', 
              'attributes'  => array( 
                  'id'      => 'checki'.$i,
                  'class'   => 'ace-checkbox-2'
              ), 
              'options' => array( 
                 'label' => '',
              ), 
          ));                         
        }        

    }// Fin funcion
}