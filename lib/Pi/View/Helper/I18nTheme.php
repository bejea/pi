<?php
/**
 * Theme i18n helper
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
 * @package         Pi\View
 * @subpackage      Helper
 * @version         $Id$
 */

namespace Pi\View\Helper;

use Pi;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper for loading theme Intl resource
 * @see Pi\Application\Service\I18n
 * @see Pi\Application\Service\Asset
 *
 * Usage inside a phtml template:
 * <code>
 *  $this->i18nTheme('main');
 *  $this->i18nTheme('main', 'default');
 *  $this->i18nTheme('main', null, 'en');
 * </code>
 */
class I18nTheme extends AbstractHelper
{
    /**
     * Load a theme i18n resource
     *
     * @param   string  $domain
     * @param   string|null  $theme
     * @param   string|null  $locale
     * @return  string
     */
    public function __invoke($domain, $theme = null, $locale = null)
    {
        return Pi::service('i18n')->loadTheme($domain, $theme, $locale);
    }
}
