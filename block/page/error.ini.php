; <?php exit(); __halt_compiler(); ?>

[outputs]
done = true
title = "Page not found"

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

; vim:filetype=dosini:

