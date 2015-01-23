<?php

class ConfirmEditHooks {
	/**
	 * Get the global Captcha instance
	 *
	 * @return SimpleCaptcha
	 */
	static function getInstance() {
		global $wgCaptcha, $wgCaptchaClass;

		static $done = false;

		if ( !$done ) {
			$done = true;
			$wgCaptcha = new $wgCaptchaClass;
		}

		return $wgCaptcha;
	}

	static function confirmEditMerged( $context, $content, $status, $summary, $user, $minorEdit ) {
		return self::getInstance()->confirmEditMerged( $context, $content, $status, $summary,
			$user, $minorEdit );
	}

	static function confirmEditPage( $editpage, $buttons, $tabindex ) {
		self::getInstance()->editShowCaptcha( $editpage );
	}

	static function confirmEditAPI( $editPage, $newtext, &$resultArr ) {
		return self::getInstance()->confirmEditAPI( $editPage, $newtext, $resultArr );
	}

	static function showEditFormFields( &$editPage, &$out ) {
		return self::getInstance()->showEditFormFields( $editPage, $out );
	}
	
	static function addNewAccountApiForm( $apiModule, $loginForm ) {
		return self::getInstance()->addNewAccountApiForm( $apiModule, $loginForm );
	}

	static function addNewAccountApiResult( $apiModule, $loginPage, &$result ) {
		return self::getInstance()->addNewAccountApiResult( $apiModule, $loginPage, $result );
	}

	static function injectUserCreate( &$template ) {
		return self::getInstance()->injectUserCreate( $template );
	}

	static function confirmUserCreate( $u, &$message, &$status = null ) {
		return self::getInstance()->confirmUserCreate( $u, $message, $status );
	}

	static function triggerUserLogin( $user, $password, $retval ) {
		return self::getInstance()->triggerUserLogin( $user, $password, $retval );
	}

	static function injectUserLogin( &$template ) {
		return self::getInstance()->injectUserLogin( $template );
	}

	static function confirmUserLogin( $u, $pass, &$retval ) {
		return self::getInstance()->confirmUserLogin( $u, $pass, $retval );
	}

	static function injectEmailUser( &$form ) {
		return self::getInstance()->injectEmailUser( $form );
	}

	static function confirmEmailUser( $from, $to, $subject, $text, &$error ) {
		return self::getInstance()->confirmEmailUser( $from, $to, $subject, $text, $error );
	}

	// Default $flags to 1 for backwards-compatible behavior
	public static function APIGetAllowedParams( &$module, &$params, $flags = 1 ) {
		return self::getInstance()->APIGetAllowedParams( $module, $params, $flags );
	}

	public static function APIGetParamDescription( &$module, &$desc ) {
		return self::getInstance()->APIGetParamDescription( $module, $desc );
	}

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param array &$files
	 * @return boolean
	 */
	public static function onUnitTestsList( array &$files ) {
		// @codeCoverageIgnoreStart
		$directoryIterator = new RecursiveDirectoryIterator( __DIR__ . '/tests/' );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		$ourFiles = array();
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$ourFiles[] = $fileInfo->getPathname();
			}
		}

		$files = array_merge( $files, $ourFiles );
		return true;
		// @codeCoverageIgnoreEnd
	}
}

class CaptchaSpecialPage extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct( 'Captcha' );
	}

	function execute( $par ) {
		$this->setHeaders();

		$instance = ConfirmEditHooks::getInstance();

		switch( $par ) {
			case "image":
				if ( method_exists( $instance, 'showImage' ) ) {
					return $instance->showImage();
				}
			case "help":
			default:
				return $instance->showHelp();
		}
	}
}
