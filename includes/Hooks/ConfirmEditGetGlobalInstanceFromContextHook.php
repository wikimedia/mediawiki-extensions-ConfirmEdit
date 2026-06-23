<?php

declare( strict_types=1 );
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Extension\ConfirmEdit\Hooks;

use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "ConfirmEditGetGlobalInstanceFromContext" to register handlers implementing this interface.
 *
 * @stable to implement
 * @since 1.47
 * @ingroup Hooks
 */
interface ConfirmEditGetGlobalInstanceFromContextHook {
	/**
	 * This hook is called when getting the CAPTCHA instance from an {@link IContextSource}
	 *
	 * Other extensions which add their own CAPTCHA triggers should handle this
	 * hook to set the action when the {@link IContextSource} matches their trigger.
	 *
	 * @param IContextSource $context
	 * @param string &$action If $context matches one of your CAPTCHA triggers, set this as
	 *   the CAPTCHA trigger. Otherwise leave it empty.
	 * @return bool|void True or no return value to continue or false to abort
	 * @see CaptchaFactory::getGlobalInstanceFromContext()
	 */
	public function onConfirmEditGetGlobalInstanceFromContext( IContextSource $context, string &$action );
}
