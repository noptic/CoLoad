<?php
/*/
class daliaIT\coload\CoLoad
================================================================================
PSR-0 compliant hybrid autoloader.

CoLoader uses a map to determinate which file to include.
If no map file is not available or the class is not listed in the map it 
searches all registered source directorys for a matching file and 
updates the map.

If an map entry is wrong it will be corrected without raising an error.

By default the loader will search for files ending with '.php' and '.class.php'
but you can change this behaviour by editing the property 'extensions'.

To enable the CoLoad autoloader create and instance and call he method 'enable'.

Meta
--------------------------------------------------------------------------------
 * Author:  Oliver Anan <oliver@ananit.de>
 * License: lgpl 3 <http://www.gnu.org/licenses/lgpl-3.0.en.html    
 
Exammples
--------------------------------------------------------------------------------
### Edit Extensions ###    
    
    $myLoader = new CoLoad('myMapFile.json');
    $myLodder->extensins[] = '.def.php';
    $mLoader->extensions = array('.php');
    
### Enable Autloader ###
    
    $myLoader = new CoLoad('myMapFile.json');
    $myLoader
        ->addSource('relative/path/to/src/dir')
        ->addSource('/absolute/path/to/other/src/dir)
        ->enable();
        
Properties
--------------------------------------------------------------------------------

### array $map
List of source files for classes.

The class name is the key and the path to the file is the value.
The map will be autoupdated if
 - the ile in the map is not ound
 - the class is not listed in the map but can be ound in the source diectorys

If your controllers are in diferent directorys you should use absolute pathes
for your source directorys or create a diferent map for eah directory.

### array $extensions
A array containing all file extensions used for class definition files.

By default CoLoade will search for files ending with '.php' and '.class.php'
    
Methods
--------------------------------------------------------------------------------
### CoLoad __construct([mixedd $map = null])
If $map is an array it will me used as classmap else it will be 
casted to a string nd loaded as json encoded array.

### CoLoad register()
Registers the auoloader with 'spl_autoload_register' and returns 
the called instance

### CoLoad unregister() 
Remomes Auoloader from 'spl_autoload_register' and returns 
the called instance

### Coload addSource(string 4source)
Add a source directiry to search for unkown classes.

### array geSources()
A list of all registered source Directories.

### Coload loadMap()

### Coload saveMap()

Source
--------------------------------------------------------------------------------
/*/
namespace daliaIT\CoLoad;
use InvalidArgumentException;
    class CoLoad
    {             
        public
            $map = array(),
            $extensions = array('.php','.class.php');

        protected 
            $sources = array(),
            $mapFile;
       
        private
            $callback = null,
            $saveOnShutdownSet; 
            
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
            $loader = $this;
            $this->callback = function($name) use ($loader){
                return $loader->loadSourceCode($name);
            };
            if($autoRegister){
                $this->register;
            }
        }
        
        public function register(){
            spl_autoload_register( $this->callback );
        }
        
        public static function unregister(){
           spl_autoload_unregister( static::$callback ); 
        }
        
        public  function addSource($src){
            if(array_search($src, $this->sources) === false){
                $this->sources[] = $src;    
            }
            return $this;
        }
        
        public  function getSources(){
            return $this->sources;
        }
        
        public function loadMap(){       
            if(!is_readable($this->mapFile)){
                throw new InvalidArgumentException(
                    "Can not read file '$mapFilePath'"
                );           
            }
            $this->map = json_decode(
                file_get_contents($this->mapFile), 
                true
            );
            return $this;
        }
        
        public function saveMap(){
            $mapDir = dirname($this->mapFile);
            if(! file_exists($mapDir) ){
                mkdir($mapDir, 0777, true);
            }
            file_put_contents(
                $this->mapFile,
                json_encode($this->map)
            );
        }
        
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
                } else {
                    return false;
                }
            }
        }
        
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
?>