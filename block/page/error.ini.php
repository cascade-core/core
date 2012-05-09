; <?php exit(); __halt_compiler(); ?>

[outputs]
done = true
title = "Page not found"

[block:skeleton]
.block		= core/out/page
.force_exec	= true

[block:h1]
.block		= core/out/header
level		= 1
text[]		= page_title:title
slot_weight	= 1

[block:page_error]
.block		= core/out/message
.force_exec	= true
is_error	= true
title		= "Sorry!"
text		= "Page not found."
http_status_code = 404

; vim:filetype=dosini:

