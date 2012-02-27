; <?php exit(); __halt_compiler(); ?>

[outputs]
done = true
title = "Cascade skeleton"

[block:skeleton]
.block		= core/out/page
.force-exec	= true

[block:h1]
.block		= core/out/header
level		= 1
text[]		= page_title:title
slot-weight	= 1

[block:menu_builder]
.block		= core/ini/router_links
config		= core/routes.ini.php

[block:main_menu]
.block		= core/out/menu
items[]		= menu_builder:links
slot-weight	= 5

; vim:filetype=dosini:

