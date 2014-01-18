{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "outputs": {
        "done": [
            "doc_show:done"
        ],
        "title": "Documentation"
    },
    "block:slot_main": {
        ".block": "core/out/slot",
        "name": "doc_main"
    },
    "block:slot_index": {
        ".block": "core/out/slot",
        "name": "doc_index"
    },
    "block:doc_index": {
        ".block": "core/devel/doc/index",
        ".force_exec": 1,
        "slot": [
            "slot_index:name"
        ],
        "slot_weight": 60
    },
    "block:doc_show": {
        ".block": "core/devel/doc/show",
        ".force_exec": 1,
        "block": [
            "router:path_tail"
        ],
        "show_code": "",
        "slot": [
            "slot_main:name"
        ]
    }
}