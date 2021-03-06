<?php
/**
 * Dispatcher - Core dispatcher - directs request to appropriate Controller
 * File : /src/Dispatcher.php
 *
 * PHP version 7.0
 *
 * @package  Stationer\Graphite
 * @author   LoneFry <dev@lonefry.com>
 * @license  MIT https://github.com/stationer/Graphite/blob/master/LICENSE
 * @link     https://github.com/stationer/Graphite
 */

namespace Stationer\Graphite;

use Stationer\Graphite\data\DataBroker;

/**
 * Dispatcher class - dispatches Controllers to perform requested Actions
 *
 * @package  Stationer\Graphite
 * @author   LoneFry <dev@lonefry.com>
 * @license  MIT https://github.com/stationer/Graphite/blob/master/LICENSE
 * @link     https://github.com/stationer/Graphite
 * @see      /src/Controller.php
 */
class Dispatcher {
    /** @var string Name of Controller to load */
    protected $controller        = 'Default';
    /** @var string Path of Controller to load */
    protected $controllerPath    = '';
    /** @var string Name of 404 Controller to load if required */
    protected $controller404     = 'Default';
    /** @var string Path of 404 Controller to load if required */
    protected $controller404Path = '';
    /** @var array Paths in which to find Controllers */
    protected $includePath       = array();
    /** @var array Arguments to pass to Controllers */
    protected $argv              = array();

    /**
     * Dispatcher Constructor
     *
     * @param array $cfg Configuration array
     */
    public function __construct(array $cfg) {
        // set hard default for controller paths
        $this->controllerPath = $this->controller404Path =
            SITE.DIRECTORY_SEPARATOR.'^'.DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR;

        // Check for and validate location of Controllers
        if (isset(G::$G['includePath'])) {
            foreach (explode(';', G::$G['includePath']) as $v) {
                $s = realpath(SITE.$v.'/controllers');
                if (file_exists($s) && '' != $v) {
                    $this->includePath[] = $s.DIRECTORY_SEPARATOR;
                }
            }
        }
        if (0 == count($this->includePath)) {
            $this->includePath[] = $this->controller404Path;
        }

        // set config default first, incase passed path is not found
        if (isset($cfg['controller404'])) {
            $this->controller404($cfg['controller404']);
        }
        // Path based requests take priority, check for path and parse
        if (isset($cfg['path'])) {
            $a = explode('/', trim($cfg['path'], '/'));

            if (count($a) > 0) {
                $this->controller(urldecode(array_shift($a)));
            }
            // argv should contain the rest of the request path, action at [0]
            $this->argv = $a;
            array_shift($a);

            // If we have other argv, pair them up and add them to the _GET array
            // Yes, this will result in redundancy: paired and unpaired; intentional
            // I wonder if this belongs elsewhere
            if (0 < count($this->argv)) {
                while (count($a) > 0) {
                    $k = urldecode(array_shift($a));
                    $v = urldecode(array_shift($a));
                    // Don't let pairings overwrite existing (numeric) indexes
                    if (!isset($this->argv[$k])) {
                        $this->argv[$k] = $v;
                    }
                }
                // add argv to _GET array without overriding
                $_GET = $_GET + $this->argv;
            }
        } else {
            // If Path was not passed, check for individual configs
            if (isset($cfg['controller'])) {
                $this->controller($cfg['controller']);
            }
            if (isset($cfg['params'])) {
                $this->argv = $cfg['params'];
            }
            if (isset($cfg['action'])) {
                array_unshift($this->argv, $cfg['action']);
            }
            // passing an argv config will override the params and action configs
            if (isset($cfg['argv'])) {
                $this->argv = $cfg['argv'];
            }
        }
    }

    /**
     * Set and return 404 controller name
     * Verifies Controller file exists in configured location
     *
     * @return string name of 404 controller
     */
    public function controller404() {
        if (0 < count($a = func_get_args())) {
            foreach ($this->includePath as $v) {
                $s = realpath($v.$a[0].'Controller.php');
                if (false !== strpos($s, $v) && file_exists($s)) {
                    $this->controller404 = $a[0];
                    $this->controller404Path = $v;
                    break;
                }
            }
        }
        return $this->controller404;
    }

    /**
     * Set and return controller name
     * Verifies Controller file exists in configured location
     *
     * @return string name of requested controller
     */
    public function controller() {
        if (0 < count($a = func_get_args())) {
            foreach ($this->includePath as $v) {
                $s = realpath($v.$a[0].'Controller.php');
                if (false !== strpos($s, $v) && file_exists($s)) {
                    $this->controller = $a[0];
                    $this->controllerPath = $v;
                    break;
                } else {
                    $this->controller = $this->controller404;
                    $this->controllerPath = $this->controller404Path;
                }
            }
        }
        return $this->controller;
    }

    /**
     * Perform specified action in specified Controller
     *
     * @param array $argv Arguments list to pass to action
     *
     * @return mixed
     */
    public function Act($argv = null) {
        if (null === $argv) {
            $argv = $this->argv;
        }
        Localizer::loadLib($this->controller);
        /** @var DataBroker $DB */
        $DB = G::build(DataBroker::class);
        /** @var Controller $Controller */
        G::$V->_controller = strtolower($this->controller);
        $Controller = $this->controller.'Controller';
        $Controller = G::build($Controller, $argv, $DB, G::$V);
        G::$V->setTemplate('template', $this->controller.'.'.$Controller->action().'.php');
        if (!method_exists($Controller, 'do_'.$Controller->action())) {
            // else use 404 controller
            G::$V->_controller = strtolower($this->controller404);
            $Controller = $this->controller404.'Controller';
            $Controller = G::build($Controller, $argv, $DB, G::$V);
        }
        $result = $Controller->act();
        if (is_a($result, View::class)) {
            G::$V = $result;
        }
        if (!isset(G::$V->_action)) {
            G::$V->_action = strtolower($Controller->action());
        }

        return G::$V;
    }
}
