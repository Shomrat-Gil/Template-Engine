<?php
require_once('render_template.php'); 
   
$arrTemplates = array(); // force a needed file
$arrTemplates['module-1'] = "inc.render1.php";        
$arrTemplates['module-2'] = "inc.render2.php";        
$arrTemplatePlaceHolders = array();
  $arrTemplatePlaceHolders['test'] = "123";     
 
$arrTemplatePlaceHolders['users']["aa"] = array(
                        "name" =>"name1"
                        ,"city" =>"halifax"
                        ,"id" =>"1"  
                        ,"BB"=>array(
                            "name" =>"***name2***"
                            ,"city" =>"***halifax***"
                            ,"id" =>"123"
                            ,"cc"=>array(
                                "name" =>"***name3***"
                                ,"city" =>"***halifax***"
                                ,"id" =>"12345"
                            )
                            ) 
                        ,"dd"=>array(
                            "name" =>"***nameA***"
                            ,"city" =>"***halifax***"
                            ,"id" =>"123"
                            ,"ee"=>array(
                                "name" =>"***nameB***"
                                ,"city" =>"***halifax***"
                                ,"id" =>"12345"
                            )
                            )
                        );


 $objRenderTemplate = new  render_template;                                              
 $objRenderTemplate->render_template_start($arrTemplatePlaceHolders,$arrTemplates);   
?>                                                                                    
       
 
<ul>
    {&users start tag=ul  &}
    <li id='{id}' >{name} , {city}</li> 
    {&users end&}
</ul>
<br> 
{test}
{@module-1@}

<ul>
    {&nest start&}
    <li>
        {up} 
            <ul>
            {&down start&}
            <li>{id} </li> 
            {&down end&}
            </ul>
    </li> 
    {&nest end&}
</ul>

     
           <br>
           {@module-2@}
 <?php 

$strTemplate = $objRenderTemplate->render_template_end();        
echo $strTemplate;
  
?>                        