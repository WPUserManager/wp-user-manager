<?php
/**
 * Minimal stand-in for Elementor's widget base class, so WPUM's widget classes can be
 * loaded in tests without Elementor installed. Only used when Elementor isn't loaded.
 */

namespace Elementor;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	/**
	 * Stub of \Elementor\Widget_Base.
	 */
	abstract class Widget_Base {
	}
}
