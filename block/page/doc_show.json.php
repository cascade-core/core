{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "outputs": {
        "done": [
            "doc_show:done"
        ],
        "title": "Documentation"
    },
    "blocks": {
        "slot_main": {
            "block": "core/out/slot",
            "in_val": {
                "name": "doc_main"
            }
        },
        "slot_index": {
            "block": "core/out/slot",
            "in_val": {
                "name": "doc_index"
            }
        },
        "doc_index": {
            "block": "core/devel/doc/index",
            "force_exec": 1,
            "in_con": {
                "slot": [
                    "slot_index",
                    "name"
                ]
            },
            "in_val": {
                "slot_weight": 60
            }
        },
        "doc_show": {
            "block": "core/devel/doc/show",
            "force_exec": 1,
            "in_con": {
                "block": [
                    "router",
                    "path_tail"
                ],
                "slot": [
                    "slot_main",
                    "name"
                ]
            },
            "in_val": {
                "show_code": ""
            }
        }
    }
}