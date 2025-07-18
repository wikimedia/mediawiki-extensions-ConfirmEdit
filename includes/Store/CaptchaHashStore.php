<?php

namespace MediaWiki\Extension\ConfirmEdit\Store;

class CaptchaHashStore extends CaptchaStore {
	/** @var array */
	protected $data = [];

	/** @inheritDoc */
	public function store( $index, $info ) {
		$this->data[$index] = $info;
	}

	/** @inheritDoc */
	public function retrieve( $index ) {
		if ( array_key_exists( $index, $this->data ) ) {
			return $this->data[$index];
		}
		return false;
	}

	/** @inheritDoc */
	public function clear( $index ) {
		unset( $this->data[$index] );
	}

	/** @inheritDoc */
	public function cookiesNeeded() {
		return false;
	}
}
