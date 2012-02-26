; <?php exit(); __halt_compiler(); ?>
;
; Default routes for 'hello world' and documentation browser
;
; Look at core/value/pipeline_loader module before you use this.
;

; default values
[#]
skeleton = core/page/skeleton
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

[/doc]
title = Documentation
content = core/page/doc

[/doc/**]
title = Documentation
content = core/page/doc_show
; path_tail is '**' part

[/profiler]
title = Profiler
content = core/page/profiler


; vim:filetype=dosini:

