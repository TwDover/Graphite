<?php
/**
 * View - core View processor
 * File : /src/View.php
 *
 * PHP version 7.0
 *
 * @package  Stationer\Graphite
 * @author   LoneFry <dev@lonefry.com>
 * @license  MIT https://github.com/stationer/Graphite/blob/master/LICENSE
 * @link     https://github.com/stationer/Graphite
 */

namespace Stationer\Graphite;

/**
 * View class - core View processor
 *  manages which templates will be used
 *  manages which variables will be in scope
 *
 * @package  Stationer\Graphite
 * @author   LoneFry <dev@lonefry.com>
 * @license  MIT https://github.com/stationer/Graphite/blob/master/LICENSE
 * @link     https://github.com/stationer/Graphite
 */
class View {
    /** @var array registered templates by type */
    protected $templates = array(
        'header'   => 'header.php',
        'footer'   => 'footer.php',
        'template' => '404.php',
        'login'    => 'Account.Login.Form.php',
        );
    /** @var array List of paths in which to find templates */
    protected $includePath = array();
    /** @var array Values to expose to templates */
    public $vals = array(
        '_meta'   => array(),
        '_script' => array(),
        '_link'   => array(),
        '_head'   => '',
        '_tail'   => '',
        );

    public $_format = null;

    /**
     * View Constructor
     *
     * @param array $cfg Configuration array
     */
    function __construct($cfg) {
        // Check for and validate location of Controllers
        if (isset(G::$G['includePath'])) {
            foreach (explode(';', G::$G['includePath']) as $v) {
                $s = realpath(SITE.$v.'/templates');
                if (file_exists($s) && '' != $v) {
                    $this->includePath[] = $s.DIRECTORY_SEPARATOR;
                }
            }
        }

        if (isset($cfg['_header'])) {
            $this->setTemplate('header', $cfg['_header']);
            unset($cfg['_header']);
        }
        if (isset($cfg['_footer'])) {
            $this->setTemplate('footer', $cfg['_footer']);
            unset($cfg['_footer']);
        }
        if (isset($cfg['_template'])) {
            $this->setTemplate('template', $cfg['_template']);
            unset($cfg['_template']);
        }
        if (isset($cfg['_debug'])) {
            $this->setTemplate('debug', $cfg['_debug']);
            unset($cfg['_debug']);
        }
        if (isset($cfg['_meta']) && is_array($cfg['_meta']) && 0 < count($cfg['_meta'])) {
            foreach ($cfg['_meta'] as $name => $content) {
                $this->_meta($name, $content);
            }
            unset($cfg['_meta']);
        }
        if (isset($cfg['_script']) && is_array($cfg['_script']) && 0 < count($cfg['_script'])) {
            foreach ($cfg['_script'] as $src) {
                $this->_script($src);
            }
            unset($cfg['_script']);
        }
        if (isset($cfg['_link']) && is_array($cfg['_link']) && 0 < count($cfg['_link'])) {
            foreach ($cfg['_link'] as $a) {
                $this->_link(
                    isset($a['rel']) ? $a['rel'] : '',
                    isset($a['type']) ? $a['type'] : '',
                    isset($a['href']) ? $a['href'] : '',
                    isset($a['title']) ? $a['title'] : ''
                );
            }
            unset($cfg['_link']);
        }
        $this->vals = array_merge($this->vals, $cfg);
    }

    /**
     * Add values for a META tag to be written to document <HEAD>
     *
     * @param string $name    META name=
     * @param string $content META content=
     *
     * @return mixed
     */
    public function _meta($name = null, $content = null) {
        if (null === $name) {
            return $this->vals['_meta'];
        }
        $this->vals['_meta'][$name] = $content;

        return null;
    }

    /**
     * Add values for a SCRIPT tag to be written to document <HEAD>
     *
     * @param string $src Javascript Source URL
     *
     * @return mixed
     */
    public function _script($src = null) {
        if (null === $src) {
            return $this->vals['_script'];
        }
        if (preg_match('~^/[^/].*/\w+\.\w+\.js$~', $src)) {
            $src = $src.'?v='.VERSION;
        }
        $this->vals['_script'][] = $src;

        return null;
    }

    /**
     * Add values for a LINK tag to be written to document <HEAD>
     *
     * @param string $rel   LINK rel=
     * @param string $type  LINK type=
     * @param string $href  LINK href=
     * @param string $title LINK title=
     *
     * @return mixed
     */
    public function _link($rel = null, $type = '', $href = '', $title = '') {
        if (null === $rel) {
            return $this->vals['_link'];
        }
        $this->vals['_link'][] = array('rel' => $rel, 'type' => $type, 'href' => $href, 'title' => $title);

        return null;
    }

    /**
     * Add values for a css STYLE tag to be written to document <HEAD>
     * Merely wrap _link()
     *
     * @param string $src CSS Source URL
     *
     * @return mixed
     */
    public function _style($src = null) {
        if (null === $src) {
            return false;
        }
        if (preg_match('~^/[^/].*/\w+\.\w+\.css$~', $src)) {
            $src = $src.'?v='.VERSION;
        }
        $this->_link('stylesheet', 'text/css', $src);

        return null;
    }

    /**
     * Test whether a file exists in a path sanity checking with realpath
     *
     * @param string $path Filesystem path to check
     * @param string $file Filename to check for
     *
     * @return string|bool Corrected filename relative to path if found,
     *                     false if not found
     */
    public function in_realpath($path, $file) {
        if ('' == $file) {
            return '';
        }
        // Get the realpath of the file, then verify it exists in passed path
        $s = realpath($path.'/'.$file);
        if (false !== strpos($s, $path) && file_exists($s)) {
            return substr($s, strlen($path));
        }
        return false;
    }

    /**
     * Set view template for rendering request
     *
     * @param string $template Template part, eg: 'header', 'footer',
     *                         for main template use 'template'
     * @param string $file     Filename of template, relative to template path
     *
     * @return string Set template, or prior set template on failure
     */
    public function setTemplate($template, $file) {
        foreach ($this->includePath as $dir) {
            if (false !== $s = $this->in_realpath($dir, $file)) {
                $this->templates[$template] = $s;
                break;
            }
        }
        return ifset($this->templates[$template]);
    }

    /**
     * Get view template for rendering request
     *
     * @param string $template Template part, eg: 'header', 'footer',
     *                         for main template use 'template'
     *
     * @return string Prior set template
     */
    public function getTemplate($template) {
        return ifset($this->templates[$template]);
    }


    /**
     * __set magic method called when trying to set a var which is not available
     * If name is of a template this will passoff the set to setTemplate()
     * All other names will be added to unrestricted vals array
     *
     *  @param string $name  Property to set
     *  @param mixed  $value Value to use
     *
     *  @return mixed
     */
    function __set($name, $value) {
        switch ($name) {
            case '_header':
                return $this->setTemplate('header', $value);
            case '_footer':
                return $this->setTemplate('footer', $value);
            case '_template':
                return $this->setTemplate('template', $value);
            default:
                $this->vals[$name] = $value;
                break;
        }

        return null;
    }

    /**
     * __get magic method called when trying to get a var which is not available
     * If name is of a template this will pass off the get to getTemplate()
     * All other names will be pulled from unrestricted vals array
     *
     * @param string $name Property to set
     *
     * @return mixed Found value
     */
    function __get($name) {
        switch ($name) {
            case '_header':
                return $this->getTemplate('header');
            case '_footer':
                return $this->getTemplate('footer');
            case '_template':
                return $this->getTemplate('template');
            default:
                if (isset($this->vals[$name])) {
                    return $this->vals[$name];
                }
                $trace = debug_backtrace();
                trigger_error('Undefined property via __get(): '.$name.' in '
                              .$trace[0]['file'].' on line '.$trace[0]['line'],
                              E_USER_NOTICE);
                break;
        }

        return null;
    }

    /**
     * __isset magic method restores the normal operation of isset()
     *
     * @param string $k Property to test
     *
     * @return bool Return true if set, false otherwise
     */
    public function __isset($k) {
        return array_key_exists($k, $this->vals);
    }

    /**
     * __unset magic method restores the normal operation of unset()
     *
     * @param string $k Property to unset
     *
     * @return void
     */
    public function __unset($k) {
        unset($this->vals[$k]);
    }

    /**
     * Executes any actions that need to be handled before output.
     *
     * @return void
     */
    public function preoutput() {
        if (G::$G['MODE'] == 'prd') {
            $ver = isset(G::$G['VIEW']['version']) ? G::$G['VIEW']['version'] : 0;
            // JS
            foreach ($this->vals['_script'] as $key => $scriptName) {
                $minFile = '/min/' . $this->_getMinName($scriptName);
                if (file_exists(SITE . $minFile)) {
                    $this->vals['_script'][$key] = $minFile . '?ver=' . $ver;
                }
            }

            // CSS
            foreach ($this->vals['_link'] as $key => $link) {
                if ($link['type'] !== 'text/css') {
                    continue;
                }
                $minFile = SITE . '/min/' . $this->_getMinName($link['href']);
                if (file_exists(SITE . $minFile)) {
                    $this->vals['_link'][$key]['href'] = $minFile . '?ver=' . $ver;
                }
            }
        }
    }

    /**
     * Render the view with any secondary processing.
     *
     * @return void
     */
    public function output() {
        $output = $this->render();
        if ($this->_format === 'pdf' && class_exists(mPDF::class)) {
            // Give us some time to render this...
            set_time_limit(60);

            $mpdf = new mPDF();
            // Process and load the CSS separately.
            // Keeps mpdf from flubbing up on big css files. (>100,000)
            ini_set("pcre.backtrack_limit", "1000000");
            $styleSheets = $this->_getStyleSheetUrls();
            foreach ($styleSheets as $link) {
                $css = file_get_contents(SITE . $link);
                // In PDF format body tags lose classes.  This keeps body styles still relevant
                if (isset($this->vals['_controller']) && isset($this->vals['_action'])) {
                    $css = str_replace(
                        'body.' . $this->vals['_controller'] . '-' . $this->vals['_action'],
                        'body',
                        $css
                    );
                    $css = str_replace('body.' . $this->vals['_controller'], 'body', $css);
                }
                $mpdf->WriteHTML($css, 1);
            }
            // Process and load HTML
            $mpdf->WriteHTML($output, 2);
            $mpdf->Output();
        } else {
            echo $output;
        }
    }

    /**
     * Returns an array of StyleSheet URLs
     *
     * @return array
     */
    private function _getStyleSheetUrls() {
        $styleSheets = array();
        foreach ($this->vals['_link'] as $link) {
            if ($link['rel'] !== 'stylesheet') {
                continue;
            }
            $styleSheets[] = $link['href'];
        }
        return $styleSheets;
    }

    /**
     * Render requested template by bringing $this->vals into scope and
     * including template file
     *
     * @param string $_template Template to render
     * @param array  $vals      Values to scope into template
     *
     * @return string|bool Buffered output on success, false otherwise
     */
    public function render($_template = 'template', $vals = null) {
        if (null !== $vals) {
            extract($vals);
        } else {
            extract($this->vals);
        }
        $View = $this;
        // To prevent applications from altering these vars, they are set last
        if (G::$S && G::$S->Login) {
            $_login_id  = G::$S->Login->login_id;
            $_loginname = G::$S->Login->loginname;
        } else {
            $_login_id  = 0;
            $_loginname = 'world';
        }

        // Find the requested template in the include path
        foreach ($this->includePath as $_v) {
            if (isset($this->templates[$_template])
                && file_exists($_v.$this->templates[$_template])
                && !is_dir($_v.$this->templates[$_template])
            ) {
                ob_start();
                include $_v.$this->templates[$_template];
                return ob_get_clean();
            }
        }

        // If we got here, we didn't find the template.
        return false;
    }

    /**
     * Returns a min name of a css/js file.
     *
     * @param string $filename File name to evaluate
     *
     * @return string
     */
    private function _getMinName($filename) {
        $basename = basename($filename);
        $ext = strrchr($basename, '.');
        if (strpos($basename, '.min' . $ext) === false) {
            $final = substr($basename, 0, strripos($basename, '.')) . '.min' . $ext;
        } else {
            $final = $basename;
        }
        return $final;
    }
}
