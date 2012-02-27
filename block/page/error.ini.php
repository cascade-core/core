; <?php exit(); __halt_compiler(); ?>

[outputs]
done = true
title = "Page not found"

[block:skeleton]
.block		= core/out/page
.force-exec	= true

[block:h1]
.block		= core/out/header
level		= 1
text[]		= page_title:title
slot-weight	= 1

[block:page_error]
.block		= core/out/message
.force-exec	= true
is-error	= true
title		= "Sorry!"
text		= "Page not found."
http-status-code = 404

; vim:filetype=dosini:

