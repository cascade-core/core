; <?php exit(); __halt_compiler(); ?>

[outputs]
done[] = doc_index:done
title = Documentation

[block:doc_index]
.block		= "core/devel/doc/index"
.force-exec	= true
slot-weight	= 60

[block:version_hd]
.block		= "core/out/header"
text		= "Version"
enable[]	= "version:done"
slot-weight	= 30

[block:version]
.block		= "core/devel/version"
format		= "details"
slot-weight	= 40



; vim:filetype=dosini:


