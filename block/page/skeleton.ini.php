; <?php exit(); __halt_compiler(); ?>

[outputs]
done = true
title = "Cascade skeleton"

[module:skeleton]
.module		= core/out/page
.force-exec	= true

[module:h1]
.module		= core/out/header
level		= 1
text[]		= page_title:title
slot-weight	= 1

[module:menu_builder]
.module		= core/ini/router_links
config		= core/routes.ini.php

[module:main_menu]
.module		= core/out/menu
items[]		= menu_builder:links
slot-weight	= 5

; vim:filetype=dosini:

