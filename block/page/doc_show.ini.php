; <?php exit(); __halt_compiler(); ?>

[outputs]
done[] = doc_show:done
title = Documentation

[block:doc_show]
.block		= "core/devel/doc/show"
.force-exec	= true
block[]		= "router:path_tail"
show-code	= false


; vim:filetype=dosini:


