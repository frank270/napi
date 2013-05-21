<?php
/**
* Ntpl
*
* @uses     
*
* @category Category
* @package  Package
* @author    <Frank Wang>
* @license  
* @link     
*/
class Ntpl {
    /*private $vars  = array();

    public function __get($name) {
    return $this->vars[$name];
    }

    public function __set($name, $value) {
    if($name == 'view_template_file') {
    throw new Exception("Cannot bind variable named 'view_template_file'");
    }
    $this->vars[$name] = $value;
    }*/
    var $path = "";
    var $isCached = false;
    var $cacheId = "";
    var $expire = 900;

    /**
     * setPath
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function setPath(){

    } 
    /**
     * render
     * 
     * @param mixed $view_template_file Description.
     * @param mixed $vars               Description.
     * @param mixed $outputToStr        Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function render($view_template_file,$vars=null,$outputToStr=false) {
        if ($vars != null){
            if (array_key_exists('view_template_file', $vars)) {
                throw new Exception("Cannot bind variable called 'view_template_file'");
            }
            extract($vars);
        }
        ob_start();
        include($view_template_file);
        if ($outputToStr) {
            return ob_get_clean();
        }
        else {
            $tmp = ob_get_clean();
            echo $tmp;
        }
    }

    
    public function setCache($cacheId = null, $expire = 900){
        $cachefile = 'cache.html';
        $cachetime = 4 * 60;
        // Serve from the cache if it is younger than $cachetime
        if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
            include($cachefile);
            echo "<!-- Cached copy, generated ".date('H:i', filemtime($cachefile))." -->\n";
            exit;
        }
        ob_start(); // Start the output buffer
         
        /* Heres where you put your page content */
         
        // Cache the contents to a file
        $cached = fopen($cacheFile, 'w');
        fwrite($cached, ob_get_contents());
        fclose($cached);
        ob_end_flush(); // Send the output to the browser
    }
}
