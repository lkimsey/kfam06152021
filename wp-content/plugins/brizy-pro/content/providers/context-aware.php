<?php

trait BrizyPro_Content_Providers_ContextAware {


	/**
	 * @var Brizy_Content_Context
	 */
	protected $context;

	/**
	 * @return Brizy_Content_Context
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * @param Brizy_Content_Context $context
	 *
	 * @return BrizyPro_Content_Providers_ContextAware
	 */
	public function setContext( $context ) {
		$this->context = $context;

		return $this;
	}

}