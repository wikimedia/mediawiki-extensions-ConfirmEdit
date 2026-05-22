<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit;

use MediaWiki\Api\Hook\APIGetAllowedParamsHook;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Content\Content;
use MediaWiki\Content\TextContent;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Hook\AlternateEditPreviewHook;
use MediaWiki\Hook\EditFilterMergedContentHook;
use MediaWiki\Hook\EditPage__showEditForm_fieldsHook;
use MediaWiki\Hook\EditPageBeforeEditButtonsHook;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Hook\TitleReadWhitelistHook;
use MediaWiki\SpecialPage\Hook\AuthChangeFormFieldsHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Specials\Hook\EmailUserFormHook;
use MediaWiki\Specials\Hook\EmailUserHook;
use MediaWiki\Status\Status;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use ReflectionClass;
use Wikimedia\IPUtils;
use Wikimedia\ObjectCache\WANObjectCache;

class Hooks implements
	AlternateEditPreviewHook,
	EditPageBeforeEditButtonsHook,
	EmailUserFormHook,
	EmailUserHook,
	TitleReadWhitelistHook,
	PageSaveCompleteHook,
	EditPage__showEditForm_fieldsHook,
	EditFilterMergedContentHook,
	APIGetAllowedParamsHook,
	AuthChangeFormFieldsHook
{
	public function __construct(
		private readonly WANObjectCache $cache,
		private readonly CaptchaFactory $captchaFactory,
	) {
	}

	/**
	 * Get the global Captcha instance for a specific action.
	 *
	 * If a specific Captcha is not defined in $wgCaptchaTriggers[$action]['class'],
	 * $wgCaptchaClass will be returned instead.
	 *
	 * @deprecated Since 1.47 - Use {@link ConfirmEditCaptchaFactory::getGlobalInstance} instead.
	 */
	public static function getInstance( string $action = '' ): SimpleCaptcha {
		wfDeprecated( __METHOD__, '1.47' );
		return MediaWikiServices::getInstance()->get( 'ConfirmEditCaptchaFactory' )->getGlobalInstance( $action );
	}

	/**
	 * Gets a list of all currently active Captcha classes, in the Wikis configuration.
	 *
	 * This includes the default/fallback Captcha of $wgCaptchaClass and any set under
	 * $wgCaptchaTriggers[$action]['class'].
	 *
	 * @return array<class-string,SimpleCaptcha>
	 */
	public static function getActiveCaptchas(): array {
		$instances = [];

		// We can't rely on static::$instance being loaded with all Captcha Types, so make our own list.
		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = MediaWikiServices::getInstance()->get( 'ConfirmEditCaptchaFactory' );
		$defaultCaptcha = $captchaFactory->getGlobalInstance();
		$instances[ ( new ReflectionClass( $defaultCaptcha ) )->getShortName() ] = $defaultCaptcha;

		$captchaTriggers = MediaWikiServices::getInstance()->getMainConfig()->get( 'CaptchaTriggers' );
		foreach ( $captchaTriggers as $action => $trigger ) {
			if ( isset( $trigger['class'] ) ) {
				$class = $captchaFactory->getGlobalInstance( $action );
				$instances[ $trigger['class'] ] = $class;
			}
		}

		return $instances;
	}

	/** @inheritDoc */
	public function onEditFilterMergedContent(
		IContextSource $context,
		Content $content,
		Status $status,
		$summary,
		User $user,
		$minoredit
	): bool {
		$simpleCaptcha = $this->captchaFactory->getGlobalInstanceFromContext( $context );
		// Set a flag indicating that ConfirmEdit's implementation of
		// EditFilterMergedContent ran.
		// This can be checked by other MediaWiki extensions, e.g. AbuseFilter.
		$simpleCaptcha->setEditFilterMergedContentHandlerInvoked();
		return $simpleCaptcha->confirmEditMerged( $context, $content, $status, $summary,
			$user, $minoredit );
	}

	/** @inheritDoc */
	public function onPageSaveComplete(
		$wikiPage,
		$user,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
	) {
		$title = $wikiPage->getTitle();
		if ( $title->getText() === 'Captcha-ip-whitelist' && $title->getNamespace() === NS_MEDIAWIKI ) {
			$this->cache->delete( $this->cache->makeKey( 'confirmedit', 'ipbypasslist' ) );
		}

		return true;
	}

	/**
	 * Get the relevant CaptchaTriggers action depending on whether the page exists
	 *
	 * @deprecated Since 1.47 - Use {@link CaptchaFactory::getGlobalInstanceFromContext} instead.
	 * @param Title $title
	 * @return string one of "edit" or "create"
	 * @see CaptchaTriggers::EDIT
	 * @see CaptchaTriggers::CREATE
	 */
	public static function getCaptchaTriggerActionFromTitle( Title $title ): string {
		wfDeprecated( __METHOD__, '1.47' );
		return $title->exists() ? CaptchaTriggers::EDIT : CaptchaTriggers::CREATE;
	}

	/** @inheritDoc */
	public function onEditPageBeforeEditButtons( $editpage, &$buttons, &$tabindex ): void {
		$this->captchaFactory->getGlobalInstanceFromContext( $editpage->getContext() )->editShowCaptcha( $editpage );
	}

	/** @inheritDoc */
	public function onEditPage__showEditForm_fields( $editor, $out ): void {
		$this->captchaFactory->getGlobalInstanceFromContext( $out )->showEditFormFields( $editor, $out );
	}

	/** @inheritDoc */
	public function onEmailUserForm( &$form ) {
		return $this->captchaFactory->getGlobalInstance( CaptchaTriggers::SENDEMAIL )->injectEmailUser( $form );
	}

	/** @inheritDoc */
	public function onEmailUser( &$to, &$from, &$subject, &$text, &$error ) {
		return $this->captchaFactory->getGlobalInstance( CaptchaTriggers::SENDEMAIL )
			->confirmEmailUser( $from, $to, $subject, $text, $error );
	}

	/** @inheritDoc */
	public function onAPIGetAllowedParams( $module, &$params, $flags ) {
		// To quote Happy-melon from 32102375f80e72c8c4359abbeff66a75da463efa...
		// > Asking for captchas in the API is really silly

		// Create a merged array of API parameters based on active captcha types.
		// This may result in clashes/overwriting if multiple Captcha use the same parameter names,
		// but there's not a lot we can do about that...
		foreach ( self::getActiveCaptchas() as $instance ) {
			/** @var SimpleCaptcha $instance */
			$instance->apiGetAllowedParams( $module, $params, $flags );
		}
	}

	/** @inheritDoc */
	public function onAuthChangeFormFields(
		$requests, $fieldInfo, &$formDescriptor, $action
	) {
		/** @var CaptchaAuthenticationRequest $req */
		$req = AuthenticationRequest::getRequestByClass(
			$requests,
			CaptchaAuthenticationRequest::class,
			true
		);
		if ( !$req ) {
			return;
		}

		$captcha = $this->captchaFactory->getGlobalInstanceFromAuthenticationRequest(
			$req,
			RequestContext::getMain()->getRequest()->getSession()
		);
		$captcha->onAuthChangeFormFields( $requests, $fieldInfo, $formDescriptor, $action );
	}

	/** @codeCoverageIgnore */
	public static function confirmEditSetup(): void {
		global $wgCaptchaTriggers;

		// There is no need to run (core) tests with enabled ConfirmEdit - bug T44145
		if ( defined( 'MW_PHPUNIT_TEST' ) || defined( 'MW_QUIBBLE_CI' ) ) {
			$wgCaptchaTriggers = array_fill_keys( array_keys( $wgCaptchaTriggers ), false );
		}
	}

	/** @inheritDoc */
	public function onTitleReadWhitelist( $title, $user, &$whitelisted ) {
		$image = SpecialPage::getTitleFor( 'Captcha', 'image' );
		$help = SpecialPage::getTitleFor( 'Captcha', 'help' );
		if ( $title->equals( $image ) || $title->equals( $help ) ) {
			$whitelisted = true;
		}
	}

	/**
	 * Callback for extension.json of FancyCaptcha to set a default captcha directory,
	 * which depends on wgUploadDirectory
	 *
	 * @codeCoverageIgnore
	 */
	public static function onFancyCaptchaSetup(): void {
		global $wgCaptchaDirectory, $wgUploadDirectory;
		if ( !$wgCaptchaDirectory ) {
			$wgCaptchaDirectory = "$wgUploadDirectory/captcha";
		}
	}

	/** @inheritDoc */
	public function onAlternateEditPreview( $editPage, &$content, &$previewHTML,
		&$parserOutput
	) {
		$title = $editPage->getTitle();
		$exceptionTitle = Title::makeTitle( NS_MEDIAWIKI, 'Captcha-ip-whitelist' );

		if ( !$title->equals( $exceptionTitle ) ) {
			return true;
		}

		$ctx = $editPage->getArticle()->getContext();
		$out = $ctx->getOutput();
		$lang = $ctx->getLanguage();
		/** @var TextContent $content */
		'@phan-var TextContent $content';

		$lines = explode( "\n", $content->getText() );
		$previewHTML .= Html::warningBox(
				$ctx->msg( 'confirmedit-preview-description' )->parse()
			) .
			Html::openElement(
				'table',
				[ 'class' => 'wikitable sortable' ]
			) .
			Html::openElement( 'thead' ) .
			Html::element( 'th', [], $ctx->msg( 'confirmedit-preview-line' )->text() ) .
			Html::element( 'th', [], $ctx->msg( 'confirmedit-preview-content' )->text() ) .
			Html::element( 'th', [], $ctx->msg( 'confirmedit-preview-validity' )->text() ) .
			Html::closeElement( 'thead' );

		foreach ( $lines as $count => $line ) {
			$ip = trim( $line );
			if ( $ip === '' || strpos( $ip, '#' ) !== false ) {
				continue;
			}
			if ( IPUtils::isIPAddress( $ip ) ) {
				$validity = $ctx->msg( 'confirmedit-preview-valid' )->escaped();
				$css = 'valid';
			} else {
				$validity = $ctx->msg( 'confirmedit-preview-invalid' )->escaped();
				$css = 'notvalid';
			}
			$previewHTML .= Html::openElement( 'tr' ) .
				Html::element(
					'td',
					[],
					$lang->formatNum( $count + 1 )
				) .
				Html::element(
					'td',
					[],
					// IPv6 max length: 8 groups * 4 digits + 7 delimiter = 39
					// + 11 chars for safety
					$lang->truncateForVisual( $ip, 50 )
				) .
				Html::rawElement(
					'td',
					// possible values:
					// mw-confirmedit-ip-valid
					// mw-confirmedit-ip-notvalid
					[ 'class' => 'mw-confirmedit-ip-' . $css ],
					$validity
				) .
				Html::closeElement( 'tr' );
		}
		$previewHTML .= Html::closeElement( 'table' );
		$out->addModuleStyles( 'ext.confirmEdit.editPreview.ipwhitelist.styles' );

		return false;
	}
}
