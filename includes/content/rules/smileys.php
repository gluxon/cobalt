<?php
// Custom script by gluxon
// Notes: This code needs to be able to detect URLS, so we can add this guy -> :/
// Hm.. what about just making sure there's a space before each smiley?

// Replace smileys with HTML IMG commands pointing to their file.
$content=str_replace(' :)', '<img alt=":)" src="/addons/smileys/smile.png" />', $content);
$content=str_replace(' :D', '<img alt=":D" src="/addons/smileys/smile-big.png" />', $content);
$content=str_replace(' :P', '<img alt=":P" src="/addons/smileys/tongue.png" />', $content);
$content=str_replace(' :-/', '<img alt=":-/" src="/addons/smileys/thinking.png" />', $content);
$content=str_replace(' :\'(', '<img alt=\":\'(" src="/addons/smileys/crying.png" />', $content);
$content=str_replace(' ;)', '<img alt=";)" src="/addons/smileys/wink.png" />', $content);
?>
