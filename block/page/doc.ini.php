; <?php exit(); __halt_compiler(); ?>

[outputs]
done[] = doc_index:done
title = Documentation

[block:slot_main]
.block		= core/out/slot
name		= doc_main

[block:slot_index]
.block		= core/out/slot
name		= doc_index

[block:doc_index]
.block		= "core/devel/doc/index"
.force_exec	= true
slot[]		= slot_index:name
slot_weight	= 60


[block:version_hd]
.block		= "core/out/header"
text		= "Version"
enable[]	= "version:done"
slot[]		= slot_main:name
slot_weight	= 30

[block:version]
.block		= "core/devel/version"
format		= "details"
slot[]		= slot_main:name
slot_weight	= 40



; vim:filetype=dosini:


