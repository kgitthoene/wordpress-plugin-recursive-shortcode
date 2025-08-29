<?php
if (!defined('ABSPATH')) {
  define('ABSPATH', dirname(__FILE__));
}
//
echo "->START---\n";
//
// Load plugin class files.
require_once '../recursive-shortcode/includes/class-recursive-shortcode.php';
require_once '../recursive-shortcode/includes/class-recursive-shortcode-parser.php';
//
//----------
// Set debug!
//
Recursive_Shortcode_Parser::setDebug(true);
//
//----------
//
$atts = array(
  'open' => '\[',
  'close' => '\]',
  'finisher' => '\/',
  'escaped-open' => '\[\[',
  'escaped-close' => '\]\]',
  //
  //----------
  // Set deconstruct!
  // (Creates colorfull HTML output.)
  //
  'deconstruct' => true,
  //
);

//
//----------
// Custom test content.
//
echo "SET CONTENT\n";
//
//$content = 'Content here![icon name="external-link-square"][/icon][icon name="external-link-square" 2][/icon 2]And here!';
$content = 'Text here![happy /][calc-pages]100[/calc-pages][display-posts category="Verein" orderby="title" include_content="true" image_size="thumbnail" wrapper="div" wrapper_class="display-posts-listing-vereine grid" order="ASC" tag="[urlparam param="tag, [get_category id="[get_id]something[/get_id]"]"]" posts_per_page="[calc-pages]100[/calc-pages]"]';
//$content = 'Hoppla[display-posts category="[urlparam param="ort" /]" include_title="true" include_excerpt="true" excerpt_length="10" excerpt_more_link="true" excerpt_more="Weiter lesen …" include_excerpt_dash="false" include_date="true" include_excerpt_dash="false" image_size="thumbnail" wrapper="div" wrapper_class="display-posts-listing-news grid" orderby="date" date_format="M Y" order="DESC"]';
//$content = '[display-posts category="Verein" orderby="title" include_content="true" image_size="thumbnail" wrapper="div" wrapper_class="display-posts-listing-vereine grid" order="ASC" tag="[urlparam param="tag, Sportart"]" posts_per_page="100"]';
//
//
echo "BRACE_OPEN='" . $atts['open'] . "'\n";
echo "BRACE_CLOSE='" . $atts['close'] . "'\n";
echo "CALL FUNC\n\n";
echo Recursive_Shortcode_Parser::parse($atts, $content) . "\n";
echo "-<END-----\n";
//
