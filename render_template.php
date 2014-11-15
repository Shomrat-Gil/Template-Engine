<?php

             
 class render_template{     
 
        // placeholders contractor
        private static $arrPlaceholders = array(
                                    "varStart"=>"{"  // variable placeholders start
                                    ,"varEnd"=>"}"  // variable placeholders end
                                    ,"loopStart"=>"{&%s.*?start" // loop placeholders start -> !important '%s' Return a formatted string
                                    ,"loopStartClose"=>"&}" // loop placeholders start -> !important '%s' Return a formatted string
                                    ,"loopEnd"=>"{&%s.*?end&}"  // loop placeholders end -> !important '%s' Return a formatted string
                                    ,"incStart"=>"{@"  // include placeholders start  
                                    ,"incEnd"=>"@}"  // include placeholders end  
                                    ,"showIncludePath"=>true // wrap nested include path with visual indication for the path
                                    ); 
        private static $arrVars = array();
        private static $arrTemplates = array();
        
        /**
        * start rendering
        * 
        * @param mixed $arrVars
        * @param mixed $arrTemplates
        */
        public function render_template_start($arrVars = array(),$arrTemplates = array()){
            render_template::$arrVars = $arrVars;
            render_template::$arrTemplates = $arrTemplates;
            ob_start();
        }
        
        /** 
        * finish rendering
        *                             
        */
        public function render_template_end(){
              $strTemplate = ob_get_clean();
              if(!empty($strTemplate)){   
                  // re cofigure keys
                    render_template::$arrVars = render_template::replace_numeric_keys(render_template::$arrVars); 
                    $strTemplate = preg_replace('/\s+/', ' ', $strTemplate);   
                    $strTemplate = render_template::loadInternalTemplates($strTemplate);    
                    $strTemplate = render_template::render_html_placeholders($strTemplate,render_template::$arrVars );
                    $strTemplate = render_template::recursiveCheck($strTemplate);
                     
             
              }
              return $strTemplate;
        }
        
        /**
        * Replace string keys with numeric value from array
        * 
        * @param mixed $array
        * @param mixed $binChildKey
        */
        private static function replace_numeric_keys(&$array,$binChildKey = false) {
            $result = array();
            $loop = 0;
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    if($binChildKey){
                        $key = $loop++;
                    }
                    $value = render_template::replace_numeric_keys($value,true);
                }
                $result[$key] = $value;
            }
            return $result;
        }
        
         
                                    
        /**
        * remove all not exist or empty loop elements from the template
        * 
        * @param mixed $strTemplate
        */
        private function recursiveCheck($strTemplate){
            $strKey = sprintf(render_template::$arrPlaceholders['loopStart'],'(.*?)');
             preg_match('/'.$strKey.'/', $strTemplate, $matches);
             if(!empty($matches[1])){  
                 $arrVars = array() ;
                 $arrVars[$matches[1]][0] = array();
                 $strTemplate = render_template::render_html_placeholders($strTemplate,$arrVars);
                 $strTemplate = render_template::recursiveCheck($strTemplate);
             }
             return $strTemplate;
        }                             
        
        /**
        * render HTML and replace placeholders with variable
        *                                            
        * @param mixed $strTemplate
        * @param mixed $arrVars
        */
        private function render_html_placeholders($strTemplate=null,$arrVars=array() ,$strCloseTag=null  ){    
                $strTemp = "";
                $arrPlaceHolders = array();
                foreach($arrVars as $var=>$value){  
                       if(empty($value) && is_array($value)) {
                           unset($arrVars[$var]); 
                           $value = array(array());                             
                       }     
                       $strContant = ""; 
                       if(render_template::is_array_multidimensional($value)  ){  
                           unset($arrVars[$var]);  
                           $arrCurrentValue = current($value);
                           if(empty( $arrCurrentValue )){
                               $problem=true;
                           } 
                           if(is_numeric($var)){
                               $arrVarsArray = array();
                               $arrVarsMulti = array();
                               foreach($value as $key=>$val){
                                    if(is_numeric($key)){
                                        $arrVarsMulti[] = $val;
                                    }else{
                                        $arrVarsArray[$key] = $val;
                                    }
                               }  
                               $arrString = render_template::find_last_tag($strTemplate);
                               $strContant = render_template::render_html_placeholders($arrString['Main'],$arrVarsArray,$strCloseTag);
                               if(!empty($arrVarsMulti)){  
                                   $strContantChild =  render_template::render_html_placeholders($strTemplate,$arrVarsMulti,$strCloseTag)  ;
                                   if($arrString['CloseTag'] == "</li>" && empty($strCloseTag)){
                                       // if template string contains <li> tag - force this into <ul>  
                                       $strCloseTag = "ul";
                                   }  
                                   $strContant .=  "\n\t\t<{$strCloseTag}>{$strContantChild}\n</{$strCloseTag}>\n"  ;
                               }
                               $strContant .= $arrString['CloseTag'];
                           } else{                                  
                                $arrLoopPlaceholders = array(
                                                "start" =>sprintf(render_template::$arrPlaceholders['loopStart'],$var)
                                                ,"startClose" =>sprintf(render_template::$arrPlaceholders['loopStartClose'],$var)
                                                ,"end" =>sprintf(render_template::$arrPlaceholders['loopEnd'],$var)
                                            ) ;
                                preg_match('#'.$arrLoopPlaceholders['start'].'('.$arrLoopPlaceholders['startClose'].'|.+?'.$arrLoopPlaceholders['startClose'].')(.+?)'.$arrLoopPlaceholders['end'].'#s', $strTemplate, $matches); 
                                if(!empty($matches[0]) && !empty($matches[1]) && !empty($matches[2]) ){
                                    // if there is a loop definition
                                    $strCloseTag = render_template::retriveLoopCloseTag($matches); 
                                    if(count($value)==1 && empty( $arrCurrentValue )){
                                        // if no value to replace
                                         $strTemplateMulti = '';
                                     } else{
                                        $matches[2] = "\n\t{$matches[2]}\n"; 
                                        $strTemplateMulti = render_template::render_html_placeholders($matches[2],$value,$strCloseTag); 
                                    }
                                    $strTemplate = str_replace($matches[0], $strTemplateMulti, $strTemplate);      
                                }
                           }    
                       }elseif(is_array($value)  ){  
                                $strContant = render_template::render_html_placeholders($strTemplate,$value,$strCloseTag);  
                             
                       } else{                             
                            $arrPlaceHolders[] = $var ;//'{'.$var.'}';

                       } 
                       $strTemp .=$strContant ;
                   }  
                   if(!empty($strTemp)){
                       $strTemplate = $strTemp;
                   }   
                   if(!empty($arrPlaceHolders)){     
                            // if not return from recursive     
                            foreach($arrPlaceHolders as $strPlaceHolder){
                                if(array_key_exists($strPlaceHolder,$arrVars)){
                                    $strHolder = render_template::$arrPlaceholders['varStart'] . $strPlaceHolder . render_template::$arrPlaceholders['varEnd'];
                                     $strTemplate = str_replace($strHolder, $arrVars[$strPlaceHolder], $strTemplate); 
                                }
                            }    
                   }   
               return  $strTemplate;
        }

        /**
        * retrive tag on a loop element
        * 
        * @param mixed $matches
        */
        private function retriveLoopCloseTag($matches){
                        // remove the clode placeholder tag
            $strCloseDef = str_replace(render_template::$arrPlaceholders['loopStartClose'],"",$matches[1]);
            $strCloseDef = strtolower($strCloseDef);
            $arrCloseDef = explode("=",trim($strCloseDef));
            if(!empty($arrCloseDef)){
                switch($arrCloseDef[0]){
                    case "tag" :
                        $strCloseTag = $arrCloseDef[1] ;
                    break;
                    default:
                        $strCloseTag = '';
                    break;
                }
            }
            return $strCloseTag;
        }
        
           

        /**
        * check whether an array is multidimensional or not
        * 
        * @param mixed $arrArray
        */
        private function is_array_multidimensional($arrArray){ 
            $binMultidimensional = false;
            if( !empty($arrArray) && is_array($arrArray)){  
                rsort( $arrArray );  
                // return isset( $arrArray[0] ) && is_array( $arrArray[0] );  
                $arrCurrentValue = current($arrArray);
                $binMultidimensional = is_array( $arrCurrentValue ); 
            }
            return $binMultidimensional;          
        }
         
          
         /**
        * splite a string from the last Tag -closer tag 
        * 
        * @param mixed $strString
        */
        private function find_last_tag($strString){
           $intTagStart = strrpos($strString, "</");
           $intTagEnd = strlen($strString) - 1; // strlen start from 1 and substr starts from 0
           $strMain = substr($strString,0,$intTagStart);
           $strCloseTag = substr($strString,$intTagStart,$intTagEnd);
           $arrString = array(
                             "Main"=>$strMain
                             ,"CloseTag"=>trim($strCloseTag)
                            ) ;
            return $arrString;
        }
        
        /**
        * recursively include inner templates
        * 
        * @param mixed $strTemplate
        */
        private function loadInternalTemplates($strTemplate){     
                $binMatchFound = false;  // flag that no inner template was loaded - to disallow recurring
                $regex = '#'.render_template::$arrPlaceholders['incStart'].'(.*?)'.render_template::$arrPlaceholders['incEnd'].'#';   
                preg_match_all($regex, $strTemplate, $matches); 
                if(!empty($matches[0]) && !empty($matches[1])){  
                    foreach($matches[0] as $intKey=>$strVal){  
                        // get the placeholder
                        $strMatchPlaceholder = $matches[1][$intKey];
                        // get the item from the templates array
                        $strMatchItem = isset(render_template::$arrTemplates[$strMatchPlaceholder])?render_template::$arrTemplates[$strMatchPlaceholder]:false;
                        // if item exist in the template array and the file exist
                        if($strMatchItem !== false ){  
                            $binMatchFound = true; // flag that inner template was loaded - to allow recurring
                            if(empty($strMatchItem)){ 
                                  $strFileTemplate = '';
                            }else{
                            ob_start();  
                                     
                                if(render_template::$arrPlaceholders['showIncludePath']){
                                    // display hidden referanse to the included file where its start
                                    echo "\n<!--  {$strMatchItem} start -->\n";
                                }
                                if( file_exists($strMatchItem)){
                                    include($strMatchItem); // add the template file 
                                }
                                if(render_template::$arrPlaceholders['showIncludePath']){
                                    // display hidden referanse to the included file where its end
                                    echo "\n<!--  {$strMatchItem} end -->\n";
                                }                              
                                $strFileTemplate = ob_get_clean(); 
                            }
                            // add the loaded template to the parse template
                            $strTemplate = str_replace($strVal, $strFileTemplate, $strTemplate);
                        }
                        
                    }
                    //after loading the templates on this request-recursively include inner templates
                    if($binMatchFound){
                        // if inner template was loaded
                        $strTemplate = render_template::loadInternalTemplates($strTemplate);
                    }
                }
                return $strTemplate;
        }  
  // END CLASS
 }                        
    