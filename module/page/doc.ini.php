; <?php exit(); __halt_compiler(); ?>

[outputs]
done[] = doc_index:done
title = Documentation

[module:doc_index]
.module		= "core/devel/doc/index"
.force-exec	= true
slot-weight	= 60

[module:version_hd]
.module		= "core/out/header"
text		= "Version"
enable[]	= "version:done"
slot-weight	= 30

[module:version]
.module		= "core/devel/version"
format		= "details"
slot-weight	= 40



; vim:filetype=dosini:


