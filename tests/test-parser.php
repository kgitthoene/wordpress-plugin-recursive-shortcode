<?php


if (!defined('ABSPATH')) {
  define('ABSPATH', dirname(__FILE__));
}


// Load plugin class files.
require_once '../recursive-shortcode/includes/class-recursive-shortcode.php';
require_once '../recursive-shortcode/includes/class-recursive-shortcode-parser.php';

$atts = array(
  'open' => '\[',
  'close' => '\]',
  'deconstruct' => false,
);
$content = 'Content here![icon name="external-link-square"][/icon][icon name="external-link-square" 2][/icon 2]And here!';
$content = '[calc-pages]100[/calc-pages][display-posts category="Verein" orderby="title" include_content="true" image_size="thumbnail" wrapper="div" wrapper_class="display-posts-listing-vereine grid" order="ASC" tag="[urlparam param="tag, Sportart"]" posts_per_page="[calc-pages]100[/calc-pages]"]';
//$content = '[display-posts category="Verein" orderby="title" include_content="true" image_size="thumbnail" wrapper="div" wrapper_class="display-posts-listing-vereine grid" order="ASC" tag="[urlparam param="tag, Sportart"]" posts_per_page="100"]';

echo "BRACE_OPEN='" . $atts['open'] . "'\n";
echo "BRACE_CLOSE='" . $atts['close'] . "'\n";
echo "---\n" . Recursive_Shortcode_Parser::parse($atts, $content) . "\n...\n";


$content = '[calc-pages]100[/calc-pages][display-posts category="Verein" orderby="title" include_content="true" image_size="thumbnail" wrapper="div" wrapper_class="display-posts-listing-vereine grid" order="ASC" tag="[urlparam param="tag, Sportart"]" posts_per_page="[calc-pages]100[/calc-pages]"]';
$atts['deconstruct'] = 'true';

echo "BRACE_OPEN='" . $atts['open'] . "'\n";
echo "BRACE_CLOSE='" . $atts['close'] . "'\n";
echo "DECONSTRUCT='" . $atts['deconstruct'] . "'\n";
$evaluate_stack = array();
echo "---\n" . Recursive_Shortcode::recursive_shortcode_func($atts, $content) . "\n...\n";
