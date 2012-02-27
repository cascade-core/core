; <?php exit(); __halt_compiler(); ?>

[outputs]
done[] = doc_show:done
title = Documentation

[module:doc_show]
.module		= "core/devel/doc/show"
.force-exec	= true
module[]	= "router:path_tail"
show-code	= false


; vim:filetype=dosini:


