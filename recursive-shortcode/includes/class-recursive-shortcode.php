<?php
/*
Copyright (c) 2020 Kai Thoene

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

/**
 * Main plugin class file.
 *
 * @package WordPress Plugin Template/Includes
 */

/*
if (!defined('ABSPATH')) {
	exit;
}
*/

/**
 * Main plugin class.
 */
class Recursive_Shortcode
{

	/**
	 * The single instance of Recursive_Shortcode.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * Default shortcode parameters.
	 * 
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_default_shortcode_params = null;

	/**
	 * The debug trigger.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_enable_debugging = false; //phpcs:ignore

	/**
	 * Local instance of Recursive_Shortcode_Admin_API
	 *
	 * @var Recursive_Shortcode_Admin_API|null
	 */
	public $admin = null;

	/**
	 * Settings class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version; //phpcs:ignore

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token; //phpcs:ignore

	/**
	 * The main plugin file.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/* ---------------------------------------------------------------------
	 * Add log function.
	 */
	private static function _write_log($log = NULL)
	{
		if (self::$_enable_debugging) {
			$bn = basename(__FILE__);
			$msg = '[' . $bn . ':' . __LINE__ . '] ' . ((is_array($log) || is_object($log)) ? print_r($log, true) : $log);
			error_log($msg);
			if (defined('STDERR')) {
				fwrite(STDERR, $msg . PHP_EOL);
			}
		}
	}  // self::_write_log

	/**
	 * Constructor funtion.
	 *
	 * @param string $file File constructor.
	 * @param string $version Plugin version.
	 */
	public function __construct($file = '', $version = '1.0.0')
	{
		$this->_version = $version;
		$this->_token   = 'recursive_shortcode';

		// Load plugin environment variables.
		$this->file       = $file;
		$this->dir        = dirname($this->file);
		$this->assets_dir = trailingslashit($this->dir) . 'assets';
		$this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

		$this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook($this->file, array($this, 'install'));

		// Load frontend JS & CSS.
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 10);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10);

		// Load admin JS & CSS.
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);

		// Load API for generic admin functions.
		if (is_admin()) {
			$this->admin = new Recursive_Shortcode_Admin_API();
		}

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action('init', array($this, 'load_localisation'), 0);
	} // __construct

	/**
	 * Setup and return class default shortcode parameters.
	 *
	 * @return Array Associative Array with default parameters.
	 */
	private static function default_shortcode_params()
	{
		if (self::$_default_shortcode_params === null) {
			self::$_default_shortcode_params = array(
				'brace_open' => '\[',
				'brace_close' => '\]',
				'deconstruct' => false,
			);
		}
		return self::$_default_shortcode_params;
	}  // default_shortcode_params

	/**
	 * Register post type function.
	 *
	 * @param string $post_type Post Type.
	 * @param string $plural Plural Label.
	 * @param string $single Single Label.
	 * @param string $description Description.
	 * @param array  $options Options array.
	 *
	 * @return bool|string|Recursive_Shortcode_Post_Type
	 */
	public function register_post_type($post_type = '', $plural = '', $single = '', $description = '', $options = array())
	{

		if (!$post_type || !$plural || !$single) {
			return false;
		}

		$post_type = new Recursive_Shortcode_Post_Type($post_type, $plural, $single, $description, $options);

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $plural Plural Label.
	 * @param string $single Single Label.
	 * @param array  $post_types Post types to register this taxonomy for.
	 * @param array  $taxonomy_args Taxonomy arguments.
	 *
	 * @return bool|string|Recursive_Shortcode_Taxonomy
	 */
	public function register_taxonomy($taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array())
	{

		if (!$taxonomy || !$plural || !$single) {
			return false;
		}

		$taxonomy = new Recursive_Shortcode_Taxonomy($taxonomy, $plural, $single, $post_types, $taxonomy_args);

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 *
	 * @access  public
	 * @return void
	 * @since   1.0.0
	 */
	public function enqueue_styles()
	{
		wp_register_style($this->_token . '-frontend', esc_url($this->assets_url) . 'css/frontend.css', array(), $this->_version);
		wp_enqueue_style($this->_token . '-frontend');
	} // enqueue_styles

	/**
	 * Load frontend Javascript.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_register_script($this->_token . '-frontend', esc_url($this->assets_url) . 'js/frontend' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);
		wp_enqueue_script($this->_token . '-frontend');
	} // enqueue_scripts

	/**
	 * Admin enqueue style.
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles($hook = '')
	{
		wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/admin.css', array(), $this->_version);
		wp_enqueue_style($this->_token . '-admin');
	} // admin_enqueue_styles

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts($hook = '')
	{
		wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);
		wp_enqueue_script($this->_token . '-admin');
	} // admin_enqueue_scripts

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_localisation()
	{
		load_plugin_textdomain('recursive-shortcode', false, dirname(plugin_basename($this->file)) . '/lang/');
	} // load_localisation

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain()
	{
		$domain = 'recursive-shortcode';

		$locale = apply_filters('plugin_locale', get_locale(), $domain);

		load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
		load_plugin_textdomain($domain, false, dirname(plugin_basename($this->file)) . '/lang/');
	} // load_plugin_textdomain

	/**
	 * Main Recursive_Shortcode Instance
	 *
	 * Ensures only one instance of Recursive_Shortcode is loaded or can be loaded.
	 *
	 * @param string $file File instance.
	 * @param string $version Version parameter.
	 *
	 * @return Object Recursive_Shortcode instance
	 * @see Recursive_Shortcode()
	 * @since 1.0.0
	 * @static
	 */
	public static function instance($file = '', $version = '1.0.0')
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self($file, $version);
		}

		return self::$_instance;
	} // instance

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone()
	{
		_doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of Recursive_Shortcode is forbidden')), esc_attr($this->_version));
	} // __clone

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup()
	{
		_doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of Recursive_Shortcode is forbidden')), esc_attr($this->_version));
	} // __wakeup

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function install()
	{
		$this->_log_version_number();
	} // install

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function _log_version_number()
	{ //phpcs:ignore
		update_option($this->_token . '_version', $this->_version);
	} // _log_version_number

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  String
	 * @since   1.0.0
	 */
	public static function recursive_shortcode_func($atts, $content)
	{
		if (function_exists('shortcode_atts')) {
			$atts = shortcode_atts(self::default_shortcode_params(), $atts);
		}
		$atts['deconstruct'] = (strtolower($atts['deconstruct']) == 'true');
		$evaluate_stack = ($atts['deconstruct'] ? array() : NULL);
		$eval = Recursive_Shortcode_Parser::parse($atts, $content, $evaluate_stack);
		if ($atts['deconstruct']) {
			// Calculate inclusion levels.
			foreach ($evaluate_stack as $i => $position) {
				array_push($evaluate_stack[$i], -1);
			}
			$level = 0;
			while (true) {
				$index = -1;
				// Search for position without level.
				for ($i = 0; $i < count($evaluate_stack); $i++) {
					if ($evaluate_stack[$i][2] == -1) {
						$index = $i;
						break;
					}
				}
				// Break, if all positions have levels.
				if ($index == -1) {
					break;
				}
				// Check, if this position is includes by another position.
				$included = false;
				for ($i = 0; $i < count($evaluate_stack); $i++) {
					if ($i != $index) {
						if (($evaluate_stack[$index][0] >= $evaluate_stack[$i][0]) and ($evaluate_stack[$index][1] <= $evaluate_stack[$i][1])) {
							if ($evaluate_stack[$index][2] == -1) {
								$evaluate_stack[$index][2] = 0;
							}
							$evaluate_stack[$index][2]++;
							$included = true;
						}
					}
				}
				//
				if (!$included) {
					$evaluate_stack[$index][2] = 0;
				}
			}
			$max_level = 0;
			for ($i = 0; $i < count($evaluate_stack); $i++) {
				$max_level = ($evaluate_stack[$i][2] > $max_level ? $evaluate_stack[$i][2] : $max_level);
			}
			self::_write_log("MAX-LEVEL=" . $max_level . " POSITIONS=" . print_r($evaluate_stack, true));
			//
			$operations = array();
			$transparency = 1.0;
			$font_size = 12;
			$border_size_pre_level = 3;
			foreach ($evaluate_stack as $positions) {
				$color = '';
				$brightness = 0;
				foreach (array('r' => 0.299, 'g' => 0.587, 'b' => 0.114) as $c => $br) {
					$cval = rand(0, 255);
					$color .= $cval . ',';
					$brightness += pow($cval, 2) * $br;
				}
				$brightness = sqrt($brightness);
				$text_color = ($brightness < 128 ? ' color:white;' : ' color:black;');
				$border_size = ($max_level - $positions[2]) * $border_size_pre_level;
				$border = 'border-top:' . $border_size . 'px solid rgba(' . $color . $transparency . '); border-bottom:' . $border_size . 'px solid rgba(' . $color . $transparency . '); ';
				array_push($operations, array($positions[0], '<span style="' . $border . 'background:rgba(' . $color . $transparency . ');' . $text_color . '">'));
				array_push($operations, array($positions[1], '</span>'));
			}
			usort($operations, function ($a, $b) {
				return ($a[0] == $b[0] ? 0 : ($a[0] > $b[0] ? -1 : 1));
			});
			foreach ($operations as $operation) {
				$content = substr_replace($content, $operation[1], $operation[0], 0);
			}
			return '<div style="unicode-bidi: embed; font-family: monospace; font-size:' . $font_size . 'px; line-height:' . ($font_size + ($max_level + 1) * (2 * $border_size_pre_level)) . 'px;">' . $content . '</div>';
			return '<pre>' . $content . '</pre>';
		} else {
			return $eval;
		}
	} // recursive_shortcode_func

}  // class Recursive_Shortcode

/*
.transparent-bg{
	background: rgba(255, 165, 0, 0.73);
}*/

/**
 * Register shortcode.
 */
if (function_exists('add_shortcode')) {
	add_shortcode('recursive-shortcode', array('Recursive_Shortcode', 'recursive_shortcode_func'));
}
