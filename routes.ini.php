; <?php exit(); __halt_compiler(); ?>
;
; Default routes for 'hello world' and documentation browser
;
; Look at core/value/cascade_loader block before you use this.
;

; default values
[#]
skeleton = true
content = false
extra = false
title_fmt = %s - Cascade
title = "(missing title)"
type = html5

; -----------------------------------------------

[/]
title = Hello
content = core/page/hello
title_fmt = %s

[/documentation]
title = Documentation
content = core/page/doc

[/documentation/everything]
title = "Documentation (single page)"
content = core/page/doc_everything

[/documentation/everything.tex]
title = "Documentation (single page)"
type = latex
content = core/page/doc_everything


[/documentation/block/**]
title = Documentation
content = core/page/doc_show
; path_tail is '**' part

[/profiler]
title = Profiler
content = core/page/profiler


; vim:filetype=dosini:

