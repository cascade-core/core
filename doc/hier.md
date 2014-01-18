Application filesystem hierarchy
================================

Application is expected to have following structure:

    document root
    │
    ├── app  . . . . . . . . . . .  Application specific files
    │   ├── block  . . . . . . . .  Blocks
    │   ├── class  . . . . . . . .  Classes within application namespace
    │   ├── style  . . . . . . . .  CSS and related picture
    │   ├── template . . . . . . .  Templates (subdir for each output type)
    │   │   ├── css
    │   │   ├── html5
    │   │   ├── json
    │   │   └── latex
    │   └── test . . . . . . . . .  Tests
    │
    ├── core . . . . . . . . . . .  Framework core (this package)
    │   ├── bin  . . . . . . . . .  Helper scripts and binaries
    │   ├── block  . . . . . . . .  Blocks
    │   ├── class  . . . . . . . .  Core framework implementation
    │   ├── doc  . . . . . . . . .  Documentation
    │   │   └── doxygen  . . . . .  Generated documentation (by Doxygen)
    │   ├── examples   . . . . . .  Example files and configuration templates
    │   ├── style  . . . . . . . .  Elementary styles and related images
    │   ├── template . . . . . . .  Core template (subdir for each output type)
    │   │   ├── css
    │   │   ├── html5
    │   │   ├── json
    │   │   └── latex
    │   └── test . . . . . . . . .  Tests (using PHPT)
    │
    ├── data   . . . . . . . . . .  Application data (writable by webserver)
    │
    ├── lib  . . . . . . . . . . .  3rd party libraries (managed by Composer)
    │   ├── cascade
    │   │   └── composer-plugin
    │   ├── composer   . . . . . .  Composer's autoloader
    │   :
    │
    ├── plugin   . . . . . . . . .  Plugins (managed by Composer)
    │   ├── plugin_name  . . . . .  Each plugin has its own subdirectory
    │   │   ├── block               with the same structure as core and app.
    │   │   ├── class
    │   │   ├── style
    │   │   ├── template
    │   │   └── test
    │   :
    │
    └── var  . . . . . . . . . . .  Variable data, caches, temporary files (writable by webserver)
        ├── graphviz   . . . . . .  Diagrams rendered by Graphviz
        :


