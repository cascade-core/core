; <?php exit(); __halt_compiler(); ?>

[outputs]
done[] = doc:done
title = Documentation

[block:doc]
.block		= "core/devel/doc/everything"
.force_exec	= true
heading_level	= 2
require_description = false
slot_weight	= 60


[block:version_hd]
.block		= "core/out/header"
text		= "Version"
enable[]	= "version:done"
level		= 2
slot_weight	= 30

[block:version]
.block		= "core/devel/version"
format		= "details"
slot_weight	= 40


; vim:filetype=dosini:

