CoLoad
================================================================================
PSR-0 compliant hybrid autoloader.

CoLoader uses a map to determinate which file to include.
If no map file is available or the class is not listed in the map it 
searches all registered source directorys for a matching file and 
updates the map.

All namesace seperators and underscores are replaced with directory seperators.
This way PSR-0 and PEAR2 naming style is supported.  

If an map entry is wrong,  CoLOad will look for a matching file all source 
directories.

At the end of the script execution the map file is updated. 

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
        