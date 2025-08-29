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

abstract class Recursive_Shortcode_Parser_Token_State {
	public const UNDEFINED = 'UNDEFINED'; 	// 'Undefined state, i.e. not initialized.';
	public const FIN = 'FIN';  							// 'Finalized, self containing.';
	public const CLOSE = 'CLOSE';  					// 'Closing tag.';
	public const OPEN = 'OPEN';  						// 'Open tag.';
	public const CLOSURE = 'CLOSURE';  			// 'Tagged with open term and closing term.';
	public const SOLO = 'SOLO';  						// 'Without closing term/tag.';
}

/**
 * Parser plugin class for token.
 */
class Recursive_Shortcode_Parser_Token {
	public ?string $id;
	private ?string $name;
	private ?string $outer_content;
	private ?string $inner_content;
	private ?int $absolute_start_position;
	private ?int $absolute_end_position;
	private ?string $state;
	private ?Recursive_Shortcode_Parser_Token $included_by;
	private ?array $includes;
	private ?int $level;

	public function __construct(
		?string $name = null,
		?string $outer_content = null,
		?string $inner_content = null,
		?int $absolute_start_position = -1,
		?int $absolute_end_position = -1,
		?string $state = Recursive_Shortcode_Parser_Token_State::UNDEFINED
	) {
		$this->id = Recursive_Shortcode_Parser::uniqid();
		$this->name = $name;
		$this->outer_content = $outer_content;
		$this->inner_content = $inner_content;
		$this->absolute_start_position = $absolute_start_position;
		$this->absolute_end_position = $absolute_end_position;
		$this->state = $state;
		$this->includes = [];
		$this->included_by = null;
		$this->level = 0;
	}  // public function __construct

	public function __toString() {
		$includes = "[]";
		if (!empty($this->includes)) {
			$includes = "[ ";
			foreach ($this->includes as $token) {
				$includes .= (($includes == "[ ") ? "" : ", ") . $token->id;
			}
			$includes .= " ]";
		}
		return "<Recursive_Shortcode_Parser_Token:\n" .
			" ID='" . $this->id . "'" .
			"\n SHORTCODE='" . $this->name . "'" .
			"\n OUTER='" . $this->outer_content . "'" .
			"\n INNER='" . $this->inner_content . "'" .
			"\n ASTART=" . $this->absolute_start_position .
			" AEND=" . $this->absolute_end_position .
			"\n STATE=" . $this->state .
			"\n INCLUDES=" . $includes .
			"\n INCLUDED-BY=" . (($this->included_by == null) ? "-" : "'" . $this->included_by->id . "'") .
			"\n LEVEL=" . $this->level .
			" />";
	}  // public function __toString()

	public function setState(?string $state = Recursive_Shortcode_Parser_Token_State::UNDEFINED) {
		$this->state = $state;
		return $this;
	}

	public function getState() {
		return $this->state;
	}

	public function setName(?string $name = null) {
		$this->name = $name;
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	public function setOuter(?string $outer_content = null) {
		$this->outer_content = $outer_content;
		return $this;
	}

	public function getOuter() {
		return $this->outer_content;
	}

	public function setInner(?string $inner_content = null) {
		$this->inner_content = $inner_content;
		return $this;
	}

	public function getInner() {
		return $this->inner_content;
	}

	public function setAStart(?int $absolute_start_position = -1) {
		$this->absolute_start_position = $absolute_start_position;
		return $this;
	}

	public function getAStart() {
		return $this->absolute_start_position;
	}

	public function setAEnd(?int $absolute_end_position = -1) {
		$this->absolute_end_position = $absolute_end_position;
		return $this;
	}

	public function getAEnd() {
		return $this->absolute_end_position;
	}

	public function setIncludedBy(?Recursive_Shortcode_Parser_Token $included_by = null) {
		$this->included_by = $included_by;
		return $this;
	}

	public function getIncludedBy() {
		return $this->included_by;
	}

	public function addIncludes(?Recursive_Shortcode_Parser_Token $include_this = null) {
		if ($include_this !== null) {
			array_push($this->includes, $include_this);
		}
		return $this;
	}

	public function getLevel() {
		return $this->level;
	}

	public function setLevel(?int $level = 0) {
		$this->level = $level;
		return $this;
	}
}  // class Recursive_Shortcode_Parser_Token

/**
 * Parser plugin class.
 */
class Recursive_Shortcode_Parser {
	/**
	 * The debug trigger.
	 *
	 * @var     bool
	 * @access  private
	 * @since   1.0.0
	 */
	private static bool $_enable_debugging = false; //phpcs:ignore
	/**
	 * Unique ID, i.e. an up counting number.
	 * Call self::uniqid() to get such a number.
	 * @var     int
	 * @access  private
	 * @since   1.0.2
	 */
	private static int $_uniqid = -1;  // -1 means uninitialized.

	/* ---------------------------------------------------------------------
	 * Set debug flag.
	 *
	 * @access  private
	 * @return  bool
	 * @since   1.0.2
	 */
	public static function setDebug(?bool $debug = false) {
		self::$_enable_debugging = $debug;
		return self::$_enable_debugging;
	}  // function setDebug

	/* ---------------------------------------------------------------------
	 * Logging function.
	 *
	 * @access  private
	 * @return  null
	 * @since   1.0.0
	 */
	private static function _write_log($log = null) {
		if (self::$_enable_debugging) {
			$db_bt = debug_backtrace(1);
			$file = $db_bt[0]['file'];
			$line = $db_bt[0]['line'];
			$bn = basename($file);
			$msg = '[' . $bn . ':' . $line . '] ' . ((is_array($log) || is_object($log)) ? print_r($log, true) : $log);
			error_log($msg);
			#if (defined('STDERR')) {
			#	fwrite(STDERR, $msg . PHP_EOL);
			#}
		}
		return null;
	}  // function _write_log

	/**
	 * Render an error message as HTML.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.0
	 */
	private static function _error($msg, $sc = null, $sc_pos = null, $content = null) {
		if ($sc != null and $sc_pos != null and $content != null) {
			$cn = mb_substr($content, 0, $sc_pos) . '<span style="background-color:#AA000F; color:white;">' . mb_substr($content, $sc_pos, mb_strlen($sc)) . '</span>' . mb_substr($content, $sc_pos + mb_strlen($sc));
		} else {
			$cn = null;
		}
		return
			'<div style="unicode-bidi: embed; font-family: monospace; font-size:12px; color:black; background-color:#E0E0E0;">' .
			'[recursive-shortcode]:ERROR -- ' . $msg . ($sc_pos === null ? '' : ' POSITION=' . $sc_pos) . ($sc === null ? '' : sprintf(" SHORTCODE='%s'", $sc)) . "\n" .
			($cn === null ? '' : sprintf("CONTENT='%s'", $cn)) .
			'</div>';
	}  // function _error

	/**
	 * Get a up counting number.
	 *
	 * @access  public
	 * @return  int
	 * @since   1.0.2
	 */
	public static function uniqid() {
		self::$_uniqid++;
		return self::$_uniqid;
	}  // function uniqid

	/**
	 * Get name of shortcode from string.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.2
	 */
	private static function _get_shortcode_name($content) {
		if (preg_match('/^\s*(\/\s*){0,1}([\w_-]+)(\s|\s*\/|\s*$)/', $content, $matches)) {
			return $matches[2];
		}
		return '';
	}  // function _get_shortcode_name

	/**
	 * Parse strings with shortcodes.
	 *
	 * @access  private
	 * @return  boolean
	 * @since   1.0.2
	 */
	private static function _tokenize_matches($atts, $content, &$matches, &$token = [], &$err_msg = '') {
		$token = [];
		$stack_of_open_tags = [];
		$err_msg = '';
		//
		$pattern_finisher_included = '/(\s*' . $atts['finisher'] . '\s*)$/';
		$pattern_start_with_finisher = '/^(\s*' . $atts['finisher'] . ')/';
		$nr_of_open_tags = 0;
		//
		//self::_write_log($matches);
		foreach ($matches as $index => $x) {
			$tag_str = $x[0];
			$tag_pos = $x[1];
			self::_write_log("[" . $index . "/OT=" . $nr_of_open_tags . "] TAG='" . $tag_str . "' ...");
			if (preg_match('/^' . $atts['close'] . '$/', $tag_str)) {
				if ($nr_of_open_tags == 0) {
					// Error: No open tag found before closing tag.
					self::_write_log("ERROR -- Cannot find open tag before closing tag!");
					$err_msg = self::_error("Cannot find open tag before closing tag!", $tag_str, $tag_pos, $content);
					return false;
				} else {
					$nr_of_open_tags--;
					self::_write_log("[" . $index . "/OT=" . $nr_of_open_tags . "] CLOSE");
					//----------
					// Check type of enclosure.
					$open_tag = array_pop($stack_of_open_tags);
					$start_tag = $open_tag[0];
					$start_pos = $open_tag[1];
					$inner_content = mb_substr($content, $start_pos + mb_strlen($start_tag), $tag_pos - $start_pos - mb_strlen($tag_str));
					$outer_content = mb_substr($content, $start_pos, $tag_pos - $start_pos + 1);
					$shortcode_name = self::_get_shortcode_name($inner_content);
					self::_write_log("[" . $index . "] INNER-CONTENT='" . $inner_content . "' SHORTCODE-NAME='" . $shortcode_name . "'");
					//
					if (preg_match($pattern_finisher_included, $inner_content)) {
						// Found self finished shortcode. E.g. '[happy /]'.
						array_push($token, new Recursive_Shortcode_Parser_Token($shortcode_name, $outer_content, $inner_content, $start_pos, $tag_pos, Recursive_Shortcode_Parser_Token_State::FIN));
						self::_write_log("TOK: FINISHED SHORTCODE: '" . strval(end($token)));
					} elseif (preg_match($pattern_start_with_finisher, $inner_content)) {
						array_push($token, new Recursive_Shortcode_Parser_Token($shortcode_name, $outer_content, $inner_content, $start_pos, $tag_pos, Recursive_Shortcode_Parser_Token_State::CLOSE));
						self::_write_log("TOK: FINISHED SHORTCODE: '" . strval(end($token)));
					} else {
						array_push($token, new Recursive_Shortcode_Parser_Token($shortcode_name, $outer_content, $inner_content, $start_pos, $tag_pos, Recursive_Shortcode_Parser_Token_State::OPEN));
						self::_write_log("TOK: FINISHED SHORTCODE: '" . strval(end($token)));
					}
				}
			}
			if (preg_match('/^' . $atts['open'] . '$/', $tag_str)) {
				$nr_of_open_tags++;
				array_push($stack_of_open_tags, $x);
				self::_write_log("[" . $index . "/OT=" . $nr_of_open_tags . "] OPEN");
			}
		}
		//
		//----------
		// Find pairs of OPEN and CLOSE tags.
		//
		$paired_token = [];
		$index = 0;
		$token_len = count($token);
		$search_shortcode_name = '';
		while ($index < $token_len) {
			$tok = $token[$index];
			switch ($tok->getState()) {
				case 'FIN':
					array_push($paired_token, new Recursive_Shortcode_Parser_Token($tok->getName(), $tok->getOuter(), $tok->getInner(), $tok->getAStart(), $tok->getAEnd(), Recursive_Shortcode_Parser_Token_State::FIN));
					break;
				case 'OPEN':
					$search_shortcode_name = $tok->getName();
					$index_2nd = $index + 1;
					$closure_found = false;
					while ($index_2nd < $token_len) {
						if ($search_shortcode_name == $token[$index_2nd]->getName()) {
							$closure_found = true;
							$inner_content = mb_substr($content, $tok->getAEnd() + 1, $token[$index_2nd]->getAStart() - $tok->getAEnd() - 1);
							$start_pos = $tok->getAStart();
							$end_pos = $token[$index_2nd]->getAend();
							$outer_content = mb_substr($content, $start_pos, $end_pos - $start_pos + 1);
							array_push($paired_token, new Recursive_Shortcode_Parser_Token($search_shortcode_name, $outer_content, $inner_content, $start_pos, $end_pos, Recursive_Shortcode_Parser_Token_State::CLOSURE));
							break;
						}
						$index_2nd++;
					}
					if (!$closure_found) {
						$outer_content = mb_substr($content, $tok->getAStart(), $tok->getAEnd() - $tok->getAStart() + 1);
						array_push($paired_token, new Recursive_Shortcode_Parser_Token($search_shortcode_name, $outer_content, $tok->getInner(), $tok->getAStart(), $tok->getAEnd(), Recursive_Shortcode_Parser_Token_State::SOLO));
					}
					break;
			}
			$index++;
		}
		$token = $paired_token;
		return true;
	}  // function _tokenize_matches

	/**
	 * Parse strings with shortcodes.
	 *
	 * @access  private
	 * @return  boolean
	 * @since   1.0.2
	 */
	private static function _find_insides($atts, $content, &$token = [], &$err_msg = '') {
		foreach ($token as $index => $tok) {
			$token[$index]->setIncludedBy(null);
			foreach ($token as $index_2nd => $tok2) {
				if ($index != $index_2nd) {
					$start_pos = $tok->getAStart();
					$end_pos = $tok->getAEnd();
					$start_pos2 = $tok2->getAStart();
					$end_pos2 = $tok2->getAEnd();
					if (($start_pos > $start_pos2) and ($start_pos < $end_pos2) and ($end_pos > $start_pos2) and ($end_pos < $end_pos2)) {
						$token[$index]->setIncludedBy($token[$index_2nd]);
						break;
					}
				}
			}
		}
		return true;
	}  // function _find_insides

	/**
	 * Parse strings with shortcodes.
	 *
	 * @access  private
	 * @return  boolean
	 * @since   1.0.2
	 */
	private static function _find_includes($atts, $content, &$token = [], &$err_msg = '') {
		foreach ($token as $index => $tok) {
			$included_by = $token[$index]->getIncludedBy();
			if ($included_by !== null) {
				$included_by->addIncludes($token[$index]);
			}
		}
		return true;
	}  // function _find_includes

	/**
	 * Parse strings with shortcodes.
	 *
	 * @access  private
	 * @return  boolean
	 * @since   1.0.2
	 */
	private static function _calculate_level_of_include($atts, $content, &$token = [], &$max_level = 0, &$err_msg = '') {
		$max_level = 0;
		foreach ($token as $index => $tok) {
			$level = 0;
			$included_by = $token[$index];
			while (true) {
				$included_by = $included_by->getIncludedBy();
				if ($included_by !== null) {
					$level++;
				} else {
					$token[$index]->setLevel($level);
					$max_level = ($level > $max_level) ? $level : $max_level;
					break;
				}
			}
		}
		return true;
	}  // function _calculate_level_of_include

	/**
	 * Creates a random string from a given alphabet.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.2
	 */
	private static function _random_string($nchar = 8, $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
		$result = '';
		for ($i = 0; $i < $nchar; $i++) {
			$result .= $alphabet[rand(0, mb_strlen($alphabet) - 1)];
		}
		return $result;
	}  // function _random_string

	/**
	 * Evaluate shortcode to something random because Wordpress function do_shortcode() doesn't exists.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.2
	 */
	private static function _do_shortcode($shortcut_text = '', $name = '(unknown)') {
		return sprintf("|%s--%s|", $name, self::_random_string());
	}  // _do_shortcode

	/**
	 * Get random RGBA color for background and a correspondig text color.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.2
	 */
	private static function _random_rgba_color(&$text_color = 'black', $transparency = 1.0) {
		$brightness = 0;
		$colors = array('r' => 255, 'g' => 255, 'b' => 255);
		foreach (array('r' => 0.299, 'g' => 0.587, 'b' => 0.114) as $c => $br) {
			$cval = rand(0, 255);
			$brightness += pow($cval, 2) * $br;
			$colors[$c] = $cval;
		}
		$brightness = sqrt($brightness);
		$text_color = ($brightness < 128 ? 'white' : 'black');
		return sprintf("rgba(%d, %d, %d, %0.1f)", $colors['r'], $colors['g'], $colors['b'], $transparency);
	}  // function _random_rgba_color

	/**
	 * Create HTML spans with colored shortcode.
	 *
	 * @access  private
	 * @return  string HTML span
	 * @since   1.0.2
	 */
	private static function _colorfull_shortcut($shortcut_text = '', $name = '(unknown)', $level = 0, $max_level = 0) {
		$txt_clr = 'black';
		$rnd_clr = self::_random_rgba_color($txt_clr);
		return sprintf("<span style=\"color:%s; background-color:%s; border-top:%dpx solid %s; border-bottom:%dpx solid %s; vertical-align:top;\">%s</span>",
			$txt_clr, $rnd_clr, 0, $rnd_clr, 0, $rnd_clr, $shortcut_text);
	}  // function _colorfull_shortcut

	/**
	 * Evaluate shortcode.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.2
	 */
	private static function _evaluate_single_shortcut($shortcut_text = '', $name = '(unknown)') {
		$eval = function_exists('do_shortcode') ? do_shortcode($shortcut_text) : self::_do_shortcode($shortcut_text, $name);
		return $eval;
	}  // function _evaluate_single_shortcut

	/**
	 * Create colored shortcodes for option deconstruct.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.2
	 */
	private static function _colorfull_shortcuts($atts, $content, &$token = [], $max_level = 0, &$err_msg = '') {
		$new_content = $content;
		foreach ($token as $index => $tok) {
			$to_evaluate = mb_substr($new_content, $tok->getAStart(), $tok->getAEnd() - $tok->getAStart() + 1);
			$eval = self::_colorfull_shortcut($to_evaluate, $tok->getName(), $tok->getLevel(), $max_level);
			$heading_content = mb_substr($new_content, 0, $tok->getAStart());
			$ending_content = mb_substr($new_content, $tok->getAEnd() + 1, null);
			$new_content = $heading_content . $eval . $ending_content;
			$length_difference = mb_strlen($eval) - mb_strlen($to_evaluate);
			//----------
			// Correct positions.
			$token_length = count($token);
			for ($i = $index + 1; $i < $token_length; $i++) {
				$tok_to_correct = $token[$i];
				if ($tok_to_correct->getAStart() > $tok->getAEnd()) {
					$tok_to_correct->setAStart($tok_to_correct->getAStart() + $length_difference);
				}
				if ($tok_to_correct->getAEnd() > $tok->getAEnd()) {
					$tok_to_correct->setAEnd($tok_to_correct->getAEnd() + $length_difference);
				}
			}
		}
		return sprintf("<span style=\"color:white; background-color:black;\">%s</span>", $new_content);
	}  // function _colorfull_shortcuts

	/**
	 * Evaluate all shortcodes.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.2
	 */
	private static function _evaluate_shortcuts($atts, $content, &$token = [], &$err_msg = '') {
		$new_content = $content;
		foreach ($token as $index => $tok) {
			self::_write_log(sprintf("[%d]----------------IN-CONTENT.='%s'", $index, $new_content));
			$to_evaluate = mb_substr($new_content, $tok->getAStart(), $tok->getAEnd() - $tok->getAStart() + 1);
			$eval = self::_evaluate_single_shortcut($to_evaluate, $tok->getName());
			$heading_content = mb_substr($new_content, 0, $tok->getAStart());
			$ending_content = mb_substr($new_content, $tok->getAEnd() + 1, null);
			$new_content = $heading_content . $eval . $ending_content;
			$length_difference = mb_strlen($eval) - mb_strlen($to_evaluate);
			self::_write_log(sprintf("[%d |S=%3d E=%3d] EVALUATE-SHORTCODE: %s => '%s'", $index, $tok->getAStart(), $tok->getAEnd(), $tok->getName(), $eval));
			self::_write_log(sprintf("[%d]                TO-EVALUATE='%s'", $index, $to_evaluate));
			self::_write_log(sprintf("[%d |S=%3d E=%3d]   HEADING....='%s'", $index, $tok->getAStart(), $tok->getAEnd(), $heading_content));
			self::_write_log(sprintf("[%d |S=%3d E=%3d]    ENDING....='%s'", $index, $tok->getAStart(), $tok->getAEnd(), $ending_content));
			self::_write_log(sprintf("[%d |LD=%3d] ****   CONTENT....='%s'", $index, $length_difference, $new_content));
			//----------
			// Correct positions.
			$token_length = count($token);
			for ($i = $index + 1; $i < $token_length; $i++) {
				$tok_to_correct = $token[$i];
				if ($tok_to_correct->getAStart() > $tok->getAEnd()) {
					$tok_to_correct->setAStart($tok_to_correct->getAStart() + $length_difference);
				}
				if ($tok_to_correct->getAEnd() > $tok->getAEnd()) {
					$tok_to_correct->setAEnd($tok_to_correct->getAEnd() + $length_difference);
				}
			}

		}
		return $new_content;
	}  // function _evaluate_shortcuts

	/**
	 * Parse strings with shortcodes.
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0.2
	 */
	private static function _replace_smart_quotes($content) {
		$content = str_replace("’", "'", $content);
		$content = str_replace(["“", "”"], '"', $content);
		$content = str_replace(["&#8220;", "&#8243;"], '"', $content);
		return $content;
	}  // function _replace_smart_quotes

	/**
	 * Parse strings with shortcodes.
	 *
	 * @access  public
	 * @return  string
	 * @since   1.0.0
	 */
	public static function parse($atts, $content) {
		$result = '';
		$pattern_open = '/(' . $atts['open'] . ')/';
		$pattern_close = '/(' . $atts['close'] . ')/';
		$pattern_finisher = '/(' . $atts['finisher'] . ')/';
		$pattern_finisher_included = '/(\s*' . $atts['finisher'] . '\s*)$/';
		$content = self::_replace_smart_quotes(trim($content));
		self::_write_log("ENTER>>>> CONTENT='" . $content . "'");
		self::_write_log("ENTER>>>>    OPEN='" . $pattern_open . "'");
		self::_write_log("ENTER>>>>   CLOSE='" . $pattern_close . "'");
		self::_write_log("ENTER>DECONSTRUCT=" . ($atts['deconstruct'] ? "true" : "false"));
		//
		//----------
		// Check syntax for tags.
		//
		$token_pattern = '/(' . $atts['open'] . '|' . $atts['close'] . ')/';
		$matches_all = null;
		if (preg_match_all($token_pattern, $content, $matches_all, PREG_OFFSET_CAPTURE)) {
			self::_write_log('CONTENT=' . $content);
			#self::_write_log($matches);
			$token = [];
			$err_msg = '';
			if (self::_tokenize_matches($atts, $content, $matches_all[1], $token, $err_msg)) {
				foreach ($token as $index => $tok) {
					self::_write_log("[" . $index . "] " . strval($tok));
				}
				self::_write_log(str_repeat("-", 20));
				//----------
				self::_find_insides($atts, $content, $token, $err_msg);
				self::_find_includes($atts, $content, $token, $err_msg);
				$max_level = 0;
				self::_calculate_level_of_include($atts, $content, $token, $max_level, $err_msg);
				foreach ($token as $index => $tok) {
					self::_write_log("[" . $index . "] " . strval($tok));
				}
				self::_write_log(str_repeat("-", 20));
				//----------
				if ($atts['deconstruct']) {
					//----------
					// Display shortcodes in colors.
					//
					$new_content = self::_colorfull_shortcuts($atts, $content, $token, $max_level, $err_msg);
					return $new_content;
					//
					//----------
				} else {
					//----------
					// Evaluate shortcodes.
					//
					$new_content = self::_evaluate_shortcuts($atts, $content, $token, $err_msg);
					return $new_content;
					//
					//----------
				}
			} else {
				// Final: Error found!
				return self::_error($err_msg);
			}
		} else {
			// Final: No tags found at all!
			return $content;
		}
	}  // function parse
}  // class Recursive_Shortcode_Parser
