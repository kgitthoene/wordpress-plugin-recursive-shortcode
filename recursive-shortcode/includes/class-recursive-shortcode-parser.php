<?php

if (!defined('ABSPATH')) {
	exit;
}

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
 * Parser plugin class file.
 *
 * @package WordPress Plugin Recursive Shortcode/Includes
 */


/**
 * Parser plugin class.
 */
class Recursive_Shortcode_Parser
{
	/**
	 * The debug trigger.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_enable_debugging = false; //phpcs:ignore

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
	 * Render an error message as output (HTML).
	 *
	 * @access  private
	 * @return  String HTML output.
	 * @since   1.0.0
	 */
	private static function _error($msg, $sc = NULL, $sc_pos = NULL, $content = NULL)
	{
		if ($sc != NULL and $sc_pos != NULL and $content != NULL) {
			$cn = substr($content, 0, $sc_pos) . '<span style="background-color:#AA000F; color:white;">' . substr($content, $sc_pos, strlen($sc)) . '</span>' . substr($content, $sc_pos + strlen($sc));
		} else {
			$cn = NULL;
		}
		return
			'<div style="unicode-bidi: embed; font-family: monospace; font-size:12px; color:black; background-color:#E0E0E0;">' .
			'[recursive-shortcode]:ERROR -- ' . $msg . ($sc_pos === NULL ? '' : ' POSITION=' . $sc_pos) . ($sc === NULL ? '' : ' SHORTCODE="' . $sc . '"') . "\n" .
			($cn === NULL ? '' : 'CONTENT="' . $cn . '"') .
			'</div>';
	}  // _error

	/**
	 * Get name and state (OPEN, CLOSE) of a shortcode from shortcode content.
	 *
	 * @access  private
	 * @return  Array With elements 0 => (true, false) - Is OPEN tag. 1 => String Tag name.
	 * @since   1.0.0
	 */
	private static function _get_shortcode_tag($shortcode_content)
	{
		if (preg_match('|^\s*/\s*(\S+)|', $shortcode_content, $matches)) {
			return array(false, $matches[1]);
		}
		if (preg_match('/^\s*(\S+)/', $shortcode_content, $matches)) {
			return array(true, $matches[1]);
		}
		return NULL;
	}  // _get_shortcode_tag

	/**
	 * Parse strings with shortcodes.
	 *
	 * @access  public
	 * @return  String
	 * @since   1.0.0
	 */
	public static function parse($atts, $content, &$evaluate_stack = NULL)
	{
		$pattern_open = '/(' . $atts['open'] . ')/';
		$pattern_close = '/(' . $atts['close'] . ')/';
		self::_write_log("CONTENT='" . $content . "'");
		self::_write_log("OPEN='" . $pattern_open . "'");
    self::_write_log("CLOSE='" . $pattern_close . "'");
		//
		//----------
		// Check syntax for tags.
		//
		//
		//----------
		// Find all shortcodes.
		//
		$offset = 0;
		$match = NULL;
		$a_pos = array();
		$pos = NULL;
		while (preg_match($pattern_open, $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
			$match = $matches[0][0];
			$pos = $matches[0][1];
			self::_write_log('POS=' . $pos);
			// Find the closing brace.
			$offset = $pos + strlen($match);
			if (preg_match($pattern_close, $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
				$match_close = $matches[0][0];
				$pos_close = $matches[0][1];
				$shortcode_content = substr($content, $pos + strlen($match), $pos_close - $pos - strlen($match));
				$shortcode_complete = $match . $shortcode_content . $match_close;
				$a_tag = self::_get_shortcode_tag($shortcode_content);
				if ($a_tag === NULL) {
					return self::_error("Cannot find shortcode tag inside shortcode!", $shortcode_complete, $pos, $content);
				}
				$tag_open = $a_tag[0];
				$tag_name = $a_tag[1];
				array_push($a_pos, array(
					'position' => $pos,
					'shortcode' => $shortcode_complete,
					'content' => $shortcode_content,
					'tag' => $tag_name,
					'open' => $tag_open
				));
				self::_write_log('POS_CLOSE=' . $pos_close);
				self::_write_log('SHORTCODE=' . $shortcode_complete);
				self::_write_log('CONTENT=' . $shortcode_content);
        self::_write_log('TAG=' . $tag_name. ' OPEN='.($tag_open ? 'yes' : 'no'));
			} else {
				return self::_error("Cannot find closing brace for shortcode!", $match, $pos, $content);
			}
		}
		//
		//----------
		// Find the last opening shortcode.
		//
		$index = count($a_pos) - 1;
		while ($index >= 0) {
			$open = $a_pos[$index]['open'];
			if ($open) {
				break;
			}
			$index--;
		}
		$last_open_index = $index;
		//
		if ($last_open_index >= 0) {
      self::_write_log('LAST-OPEN-INDEX=' . $last_open_index);
			$pos = $a_pos[$last_open_index]['position'];
			$shortcode_complete = $a_pos[$last_open_index]['shortcode'];
			$shortcode_content = $a_pos[$last_open_index]['content'];
			$tag_name = $a_pos[$last_open_index]['tag'];
			$tag_open = $a_pos[$last_open_index]['open'];
			//
			//----------
			// Check if we have a closing tag.
			// This must be the next shortcode or it's not existing.
			//
			$has_closing_tag = false;
			if ($last_open_index < (count($a_pos) - 1)) {
				if (!$a_pos[$last_open_index + 1]['open'] and ($a_pos[$last_open_index + 1]['tag'] == $tag_name)) {
					// We have a closing tag.
					$has_closing_tag = true;
				}
			}
			//
			//----------
			// Process the last found shortcode.
			//
			if (!$has_closing_tag) {
				//
				//----------
				// This tag has no closing tag.
				// 
				$content_before = ($pos == 0 ? '' : substr($content, 0, $pos));
				$content_after = substr($content, $pos + strlen($shortcode_complete));
				// Evaluate shortcode.
        $to_eval = $shortcode_complete;
        $to_eval = preg_replace($pattern_open, '[', $to_eval);
        $to_eval = preg_replace($pattern_close, ']', $to_eval);
				self::_write_log('HAS-NO-CLOSING:EVAL[' . $last_open_index . '] ->|' . $content_before . '|' . $to_eval . '|' . $content_after . '|');
				if (function_exists('do_shortcode')) {
					if ($atts['deconstruct']) {
						$eval = str_repeat(" ", strlen($shortcode_complete));
					} else {
						$eval = do_shortcode($to_eval);
					}
				} else {
					$eval = '';
				}
				if ($to_eval == $eval) {
					return self::_error("Unknown shortcode!", $shortcode_complete, $pos, $content);
				}
				if ($atts['deconstruct']) {
					$eval = str_repeat(" ", strlen($shortcode_complete));
					if ($evaluate_stack !== NULL) {
						array_push($evaluate_stack, array($pos, $pos + strlen($shortcode_complete)));
					}
				}
				if ($last_open_index == 0) {
					return $content_before . $eval . $content_after;
				} else {
					return self::parse($atts, $content_before . $eval . $content_after, $evaluate_stack);
				}
			} else {
				//
				//----------
				// We have a closing tag.
				//
				$next_pos = $a_pos[$last_open_index + 1]['position'];
				$next_shortcode_complete = $a_pos[$last_open_index + 1]['shortcode'];
				$shortcode_complete = substr($content, $pos, $next_pos + strlen($next_shortcode_complete) - $pos);
				self::_write_log('HAS-CLOSING: SHORTCODE="' . $shortcode_complete . '"');
				//
				$content_before = ($pos == 0 ? '' : substr($content, 0, $pos));
				$content_after = substr($content, $pos + strlen($shortcode_complete));
				// Evaluate shortcode.
        $to_eval = $shortcode_complete;
        $to_eval = preg_replace($pattern_open, '[', $to_eval);
        $to_eval = preg_replace($pattern_close, ']', $to_eval);
				self::_write_log('HAS-CLOSING:EVAL[' . $last_open_index . '] ->|' . $content_before . '|' . $to_eval . '|' . $content_after . '|');
				if (function_exists('do_shortcode')) {
					if ($atts['deconstruct']) {
						$eval = str_repeat(" ", strlen($shortcode_complete));
					} else {
						$eval = do_shortcode($to_eval);
					}
				} else {
					$eval = '';
				}
				if ($to_eval == $eval) {
					return self::_error("Unknown shortcode!", $shortcode_complete, $pos, $content);
				}
				if ($atts['deconstruct']) {
					$eval = str_repeat(" ", strlen($shortcode_complete));
					if ($evaluate_stack !== NULL) {
						array_push($evaluate_stack, array($pos, $pos + strlen($shortcode_complete)));
					}
				}
				if ($last_open_index == 0) {
					return $content_before . $eval . $content_after;
				} else {
					return self::parse($atts, $content_before . $eval . $content_after, $evaluate_stack);
				}
			}
		}
		return $content;
	}  // parse

}  // class Recursive_Shortcode_Parser
