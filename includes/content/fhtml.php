<?php
$content=strip_tags($content, '<a><p><br><em><strong>');

// Prepend paragraph tag to content
$content="<p>" . $content;

// Replace new lines with paragraph and break tags
$content=preg_replace('/\r\n\r\n/', "</p><p>", $content);
$content=preg_replace('/\r\n/', "<br />", $content);

// Append paragraph tag to end
$content=$content . "</p>";

include("includes/content/rules/smileys.php"); // custom smileys script
include("includes/content/rules/base.php"); // BASE

echo $content;
?>
