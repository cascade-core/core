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

[module:page_error]
.module		= core/out/message
.force-exec	= true
is-error	= true
title		= "Sorry!"
text		= "Page not found."
http-status-code = 404
hide[]		= page:content_0_done

[module:menu_builder]
.module		= core/ini/router_links
config		= core/routes.ini.php

[module:main_menu]
.module		= core/out/menu
items[]		= menu_builder:links
slot-weight	= 5

; vim:filetype=dosini:

