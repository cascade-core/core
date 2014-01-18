{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "outputs": {
        "done": [
            "doc:done"
        ],
        "title": "Documentation"
    },
    "block:doc": {
        ".block": "core/devel/doc/everything",
        ".force_exec": 1,
        "heading_level": 1,
        "require_description": "",
        "slot_weight": 60
    },
    "block:version_hd": {
        ".block": "core/out/header",
        "text": "Version",
        "enable": [
            "version:done"
        ],
        "level": "1*",
        "slot_weight": 30
    },
    "block:version": {
        ".block": "core/devel/version",
        "format": "details",
        "slot_weight": 40
    }
}