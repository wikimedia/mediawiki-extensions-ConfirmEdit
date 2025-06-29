<?php
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

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Page\PageIdentity;
use MediaWiki\User\User;

/**
 * Run hooks provided by ConfirmEdit.
 *
 * @author Zabe
 */
class HookRunner implements
	ConfirmEditTriggersCaptchaHook,
	ConfirmEditCanUserSkipCaptchaHook
{
	private HookContainer $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/** @inheritDoc */
	public function onConfirmEditTriggersCaptcha(
		string $action,
		?PageIdentity $page,
		bool &$result
	) {
		$this->hookContainer->run(
			'ConfirmEditTriggersCaptcha',
			[
				$action,
				$page,
				&$result
			]
		);
	}

	/** @inheritDoc */
	public function onConfirmEditCanUserSkipCaptcha( User $user, bool &$result ) {
		$this->hookContainer->run(
			'ConfirmEditCanUserSkipCaptcha',
			[
				$user,
				&$result
			]
		);
	}
}
