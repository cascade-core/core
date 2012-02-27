; <?php exit(); __halt_compiler(); ?>

[outputs]
done = true
title = "Cascade"


[block:hello]
.block		= core/out/raw
.force_exec	= true
; Never do this:
data		= "<p>Hello world!</p><p>Look at <a href='/doc'>documentation</a>!</p>"


; vim:filetype=dosini:

