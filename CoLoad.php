<?php
/*/
author:     Oliver Anan <oliver@ananit.de>
package:    CoLoad
license:    lgpl 3 <http://www.gnu.org/licenses/lgpl-3.0.en.html
tags:       [psr-0, pear2, autoloader]
type:       class
version:    0.2.1.0

================================================================================
class daliaIT\coload\CoLoad
================================================================================
PSR-0 compliant hybrid autoloader.

All namesace seperators and underscores are replaced with directory seperators.
This way PSR-0 and PEAR2 naming style is supported.  

CoLoad uses a map to determinate which file to include.
If an map entry is wrong, or no map exists, CoLoad will look for a matching file all source 
directories.

At the end of the script execution the map file is updated, or created. 

By default the loader will search for files ending with '.php' and '.class.php'
but you can change this behaviour by editing the property 'extensions'.

To enable the CoLoad autoloader create and instance and call the method 
'register'.
 
Exammples
--------------------------------------------------------------------------------
### Edit Extensions   
    
    $myLoader = new CoLoad('myMapFile.json');
    $myLodder->extensins[] = '.def.php';
    $mLoader->extensions = array('.php');
    
### Enable Autloader
    
    $myLoader = new CoLoad('myMapFile.json');
    $myLoader
        ->addSource('relative/path/to/src/dir')
        ->addSource('/absolute/path/to/other/src/dir)
        ->register();
        
Source
--------------------------------------------------------------------------------
/*/
    namespace daliaIT\CoLoad;
    use InvalidArgumentException;
    class CoLoad
    {             
        public
        #>array
            $map = array(),
            $extensions = array('.php','.class.php');
            #<
            
        protected 
        #:array    
            $sources = array(),
        #:string    
            $mapFile;
       
        private
        #:bool
            $saveOnShutdownSet; 
        
        #:this
        public function __construct(
            $mapFile = null, 
            $sources=array(), 
            $autoRegister=false
        ){
            $this->mapFile = $mapFile;
            $this->sources = $sources;
            if($mapFile && is_readable($mapFile)){
                $this->loadMap();
            }
            if($autoRegister){
                $this->register();
            }
        }
        
        #:this
        public function register(){
            $loader = $this;
            spl_autoload_register( function($name) use ($loader){
                return $loader->loadSourceCode($name);
            });
            return $this;
        }
        
        #:this
        public  function addSource($src){
            if(array_search($src, $this->sources) === false){
                $this->sources[] = $src;    
            }
            return $this;
        }
        
        #:array
        public  function getSources(){
            return $this->sources;
        }
        
        #:this
        public function loadMap(){       
            if(!is_readable($this->mapFile)){
                throw new InvalidArgumentException(
                    "Can not read file '{$this->mapFile}'"
                );           
            }
            $this->map = json_decode(
                file_get_contents($this->mapFile), 
                true
            );
            return $this;
        }
        
        #:this
        public function saveMap(){
            $mapDir = dirname($this->mapFile);
            if(! file_exists($mapDir) ){
                mkdir($mapDir, 0777, true);
            }
            file_put_contents(
                $this->mapFile,
                json_encode($this->map)
            );
            return $this;
        }
        
        #:bool
        public function loadSourceCode($name){
            if(isset($this->map[$name]) && is_readable($this->map[$name])){
                require $this->map[$name];
            } else {
                $path = $this->search($name);
                if($path && is_readable($path)){
                    $this->map[$name] = $path;
                    require $path;
                    if(!$this->saveOnShutdownSet){
                        $this->saveOnShutdownSet = true;
                        $loader = $this;
                        register_shutdown_function(
                            function() use ($loader){
                                $loader->saveMap();
                            } 
                        );
                    }
                    return true;
                } else {
                    return false;
                }
            }
        }
        
        #:string|null
        public function search($name){
            $normalized = str_replace(
                array('\\','_'),
                DIRECTORY_SEPARATOR,
                $name
            );
            foreach($this->sources as $source){
                $pathWithoutExtension = 
                    $source . DIRECTORY_SEPARATOR . $normalized;
                $pathWithoutExtension = preg_replace(
                    '|/+|','/', $pathWithoutExtension
                );   
                foreach($this->extensions as $extension){
                    $path = $pathWithoutExtension . $extension; 
                    if(is_readable($path)){
                       return $path;
                    }
                }
            }
            return null;
        }
    }