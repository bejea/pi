<?php
/**
 * Pi Engine host and path container class
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) Pi Engine http://www.xoopsengine.org
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 * @package         Pi\Application
 * @since           3.0
 * @version         $Id$
 */

namespace Pi\Application;

/**
 * Host handler
 *
 * Single host
 * 1. In www/boot.php:
 *  <code>
 *      define('PI_PATH_HOST', '/path/to/pi/var/config/host.php');
 *  </code>
 * 2. In /path/to/pi/var/config/host.php:
 *  <code>
 *      return array(
 *          'uri'   => array(
 *              ...
 *          ),
 *          'path'  => array(
 *              ...
 *          ),
 *      );
 *  </code>
 *
 * Multiple hosts
 * 1. In www/boot.php:
 *  <code>
 *      define('PI_PATH_HOST', '/path/to/pi/var/config/hosts.php');
 *  </code>
 * 2. In /path/to/pi/var/config/hosts.php, see /var/config/hosts.php
 */
class Host
{
    /**
     * Base URL, segment after baseLocation in installed URL which is: ($scheme:://$hostName[:$port])$baseUrl with leading slash
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Base location: $scheme:://$hostName[:$port]
     * @var string
     */
    protected $baseLocation = '';

    /**
     * Specified URIs
     * @var array
     */
    protected $uri = array(
        'www'       => '',
        'asset'     => '',
        'upload'    => '',
        'static'    => '',
    );

    /**
     * Specified paths
     * @var array
     */
    protected $path = array(
        // paths specified in local hosts file
        'www'       => '',
        'asset'     => '',
        'upload'    => '',
        'static'    => '',
        'usr'       => '',
        'module'    => '',
        'theme'     => '',

        // paths defined in boot.php or in application host
        'lib'       => '',
        'var'       => '',

        // path dependent on var
        'config'    => '',

        // paths dependent on var or specified in host file
        'cache'     => '',
        'log'       => '',
    );

    /**
     * Constructor
     *
     * @param string|array  $config Host file path or array of path settings
     * @return void
     */
    public function __construct($config = array())
    {
        $this->setHost($config);
    }

    /**
     * Build base location
     *
     * @return string
     */
    protected function getBaseLocation()
    {
        // Build current request URI
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'];
        if (!$host) {
            $port = $_SERVER['SERVER_PORT'];
            $name = $_SERVER['SERVER_NAME'];
            if (($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443)) {
                $host = $name;
            } else {
                $host = $name . ':' . $port;
            }
        }
        $baseLocation = $scheme . '://' . $host;
        return $baseLocation;
    }

    /**
     * Lookup host configuration file path in central host configuration
     *
     * @param  string    $hostIdentifier
     * @return array
     */
    protected function lookup($config, $hostIdentifier = '')
    {
        // Valid host data, return directly
        if (isset($config['path']) || isset($config['uri'])) {
            return $config;
        }
        // Invalid hosts data, return empty data
        if (!isset($config['hosts']) || !isset($config['alias'])) {
            trigger_error('Invalid hosts config.', E_USER_ERROR);
            return array();
        }

        // Build current request URI
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'];
        $requestUri = rtrim($this->getBaseLocation() . ($uri ? '/' . trim($uri, '/') : ''), '/') . '/';

        // Lookup identifier against alias list
        $lookup = function ($conf) use ($requestUri)
        {
            foreach($conf as $uri => $identifier) {
                $uri = rtrim($uri, '/') . '/';
                if (0 === strpos($requestUri, $uri)) {
                    return $identifier;
                }
            }
            return false;
        };

        // Find identifier
        if (!$hostIdentifier) {
            $hostIdentifier = $lookup($config['alias']) ?: 'default';
        }
        // Get host data
        $hostData = $config['hosts'][$hostIdentifier];
        // Read from file
        if (is_string($hostData)) {
            $hostData = include $hostData;
        }

        return $hostData;
    }

    /**
     * Set host data based on passed config or data loaded from config file
     *
     * @param string|array  $config Host file path or array of path settings
     * @return void
     */
    public function setHost($config)
    {
        $hostConfig = array();
        $hostFile = '';
        $hostIdentifier = '';

        // Host file path is specified
        if (is_string($config)) {
            $config['file'] = $config;
        }
        if (isset($config['file'])) {
            $hostFile = $config['file'];
            unset($config['file']);
        }
        // Get host identifier
        if (isset($config['identifier'])) {
            $hostIdentifier = $config['identifier'];
            unset($config['identifier']);
        }
        // Get custom host config
        if (isset($config['host'])) {
            $hostConfig = $config['host'];
            unset($config['host']);
        }
        // Load host data from file
        if ($hostFile) {
            $config = include $hostFile;
        }

        // Find host config data
        $configs = $this->lookup($config, $hostIdentifier);
        // Merge with custom host config
        if (isset($hostConfig['path'])) {
            $hostConfig['path'] = array_merge($configs['path'], $hostConfig['path']);
        } else {
            $hostConfig['path'] = $configs['path'];
        }
        if (isset($hostConfig['uri'])) {
            $hostConfig['uri'] = array_merge($configs['uri'], $hostConfig['uri']);
        } else {
            $hostConfig['uri'] = $configs['uri'];
        }

        // Canonize www URI
        if (empty($hostConfig['uri']['www'])) {
            $hostConfig['uri']['www'] = $this->getBaseLocation();
        }

        // Load from config file
        $this->path = $hostConfig['path'];
        $this->uri = $hostConfig['uri'];

        // Set baseLocation
        $pos = strpos($hostConfig['uri']['www'], '/', 9);
        if ($pos === false) {
            $this->baseLocation = $hostConfig['uri']['www'];
            $this->baseUrl = '';
        } else {
            $this->baseLocation = substr($hostConfig['uri']['www'], 0, $pos);
            $this->baseUrl = substr($hostConfig['uri']['www'], $pos);
        }

        // Set dependent paths
        foreach (array('config', 'cache', 'log') as $path) {
            if (empty($this->path[$path])) {
                $this->path[$path] = $this->path['var'] . '/' . $path;
            }
        }

        return $this;
    }

    /**
     * Get a protected variable
     *
     * @param  string    $var
     * @return
     */
    public function get($var)
    {
        if (isset($this->$var)) {
            return $this->$var;
        }
        return null;
    }

    /**
     * Get value for a protected variable
     *
     * @param  string   $var
     * @param  mixed    $value
     * @return
     */
    public function set($var, $value = null)
    {
        $this->$var = $value;
        return $this;
    }

    /**
     * Convert Pi Engine path to corresponding physical one
     *
     * @param string    $url        Pi Engine path:
     *                                  with ':' or leading slash '/' - absolute path, do not convert
     *                                  First part as section, map to www if no section matched
     * @param string
     */
    public function path($url)
    {
        $uri = null;
        // Path of predefined section, w/o sub path
        if (isset($this->path[$url])) {
            list($section, $path) = array($url, '');
        // Relative path
        } elseif (false === strpos($url, ':') && $url{0} !== '/') {
            // No '/' included, map to www path
            if (false === strpos($url, '/')) {
                list($section, $path) = array('www', $url);
            // Split at the first '/'
            } else {
                list($section, $path) = explode('/', $url, 2);
                // If $root is not a section, match to www
                if (!isset($this->path[$section])) {
                    list($section, $path) = array('www', $url);
                }
            }
        } else {
            $uri = $url;
        }

        if (null === $uri) {
            // Convert section path
            $sectionUri = $this->path[$section];
            if (false !== strpos($sectionUri, ':') || $sectionUri{0} === '/') {
                $uri = $sectionUri;
            } else {
                // Append www path to sectionUri if it is relative
                $uri = $this->path['www'] . ($sectionUri ? '/' . $sectionUri : '');
            }
            // Assemble full path
            $uri .= $path ? '/' . $path : '';
        }

        return $uri;
    }

    /**
     * Convert a Pi Engine path to an URL
     *
     * @param string    $url        Pi Engine URI:
     *                                  With URI scheme '://' - absolute URI, do not convert
     *                                  First part as section, map to www if no section matched
     *                                  If section URI is relative, www URI will be appended
     * @param bool      $absolute   whether convert to full URI; relative URI is used by default, i.e. no hostname
     * @return string
     */
    public function url($url, $absolute = false)
    {
        $uri = null;
        $path = '';
        // URI of predefined section, w/o sub path
        if (isset($this->uri[$url])) {
            list($section, $path) = array($url, '');
        // Relative URI
        } elseif (false === strpos($url, '://')) {
            // No '/' included, map to www path
            if (false === strpos($url, '/')) {
                list($section, $path) = array('www', $url);
            // Split at the first '/'
            } else {
                list($section, $path) = explode('/', $url, 2);
                // If $root is not a section, match to www
                if (!isset($this->uri[$section])) {
                    list($section, $path) = array('www', $url);
                }
            }
        // Absolute URI
        } else {
            $uri = $url;
        }

        if (null === $uri) {
            // Convert section URI
            $sectionUri = $this->uri[$section];
            if (false !== strpos($sectionUri, '://')) {
                $uri = $sectionUri;
            } else {
                // Append baseUrl to sectionUri if it is relative
                $uri = $this->baseUrl . ($sectionUri ? '/' . $sectionUri : '');
                if ($absolute) {
                    $uri = $this->baseLocation . ($uri ? '/' . ltrim($uri, '/') : '');
                }
            }
            // Assemble full URI
            $uri .= $path ? '/' . $path : '';
        }

        return $uri;
    }
}
