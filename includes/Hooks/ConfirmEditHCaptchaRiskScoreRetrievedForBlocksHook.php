<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks;

use MediaWiki\Request\WebRequest;
use MediaWiki\User\UserIdentity;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "ConfirmEditHCaptchaRiskScoreRetrievedForBlocks" to
 * register handlers implementing this interface.
 *
 * @stable to implement
 * @since 1.47
 * @ingroup Hooks
 */
interface ConfirmEditHCaptchaRiskScoreRetrievedForBlocksHook {
	/**
	 * This hook is called when a risk score has been retrieved from hCaptcha's siteverify API
	 * for a set of blocks via the dedicated risk score REST endpoint.
	 *
	 * @param float $riskScore The risk score returned by hCaptcha (0.0–1.0, or -1.0 if unavailable)
	 * @param int[] $localBlockIds The local block IDs associated with this risk score assessment
	 * @param int[] $globalBlockIds The global block IDs associated with this risk score assessment
	 * @param UserIdentity $user The user who submitted the hCaptcha token
	 * @param string $pageViewId The page view ID associated with this risk score assessment
	 * @param WebRequest $request The current web request
	 */
	public function onConfirmEditHCaptchaRiskScoreRetrievedForBlocks(
		float $riskScore,
		array $localBlockIds,
		array $globalBlockIds,
		UserIdentity $user,
		string $pageViewId,
		$request
	): void;
}
