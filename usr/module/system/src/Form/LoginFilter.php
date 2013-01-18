<?php
/**
 * Login form input filter
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
 * @since           3.0
 * @package         Module\System
 * @subpackage      Form
 * @version         $Id$
 */

namespace Module\System\Form;

use Pi;
use Zend\InputFilter\InputFilter;

class LoginFilter extends InputFilter
{
    public function __construct()
    {
        $this->add(array(
            'name'          => 'identity',
            'required'      => true,
            'filters'    => array(
                array(
                    'name'  => 'StringTrim',
                ),
            ),
        ));

        $this->add(array(
            'name'          => 'credential',
            'required'      => true,
            'filters'    => array(
                array(
                    'name'  => 'StringTrim',
                ),
            ),
        ));

        if (Pi::config('rememberme', 'user')) {
            $this->add(array(
                'name'  => 'rememberme',
            ));
        }

        $this->add(array(
            'name'  => 'redirect',
        ));
    }
}