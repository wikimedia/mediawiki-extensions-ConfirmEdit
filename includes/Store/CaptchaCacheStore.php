<?php

namespace MediaWiki\Extension\ConfirmEdit\Store;

use BagOStuff;
use MediaWiki\MediaWikiServices;

class CaptchaCacheStore extends CaptchaStore {
	/** @var BagOStuff */
	private $mainStashStore;

	/** @var BagOStuff */
	private $microStashStore;

	public function __construct() {
		parent::__construct();

		$services = MediaWikiServices::getInstance();
		$this->mainStashStore = $services->getMainObjectStash();
		$this->microStashStore = $services->getMicroStash();
	}

	/**
	 * @inheritDoc
	 */
	public function store( $index, $info ) {
		global $wgCaptchaSessionExpiration;

		$microStashStore = $this->microStashStore;
		$microStashStore->set(
			$microStashStore->makeKey( 'captcha', $index ),
			$info,
			$wgCaptchaSessionExpiration,
			// Assume the write will reach the master DC before the user sends the
			// HTTP POST request attempted to solve the captcha and perform an action
			$microStashStore::WRITE_BACKGROUND
		);
	}

	/**
	 * @inheritDoc
	 */
	public function retrieve( $index ) {
		$microStashStore = $this->microStashStore;
		$data = $microStashStore->get( $microStashStore->makeKey( 'captcha', $index ) );

		if ( !$data ) {
			$mainStashStore = $this->mainStashStore;
			$data = $mainStashStore->get( $mainStashStore->makeKey( 'captcha', $index ) );
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function clear( $index ) {
		$mainStashStore = $this->mainStashStore;
		$mainStashStore->delete( $mainStashStore->makeKey( 'captcha', $index ) );

		$microStashStore = $this->microStashStore;
		$microStashStore->delete( $microStashStore->makeKey( 'captcha', $index ) );
	}

	public function cookiesNeeded() {
		return false;
	}
}

class_alias( CaptchaCacheStore::class, 'CaptchaCacheStore' );
