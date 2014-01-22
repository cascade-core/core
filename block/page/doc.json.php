{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "outputs": {
        "done": [
            "doc_index:done"
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
        "version_hd": {
            "block": "core/out/header",
            "in_val": {
                "text": "Version",
                "slot_weight": 30
            },
            "in_con": {
                "enable": [
                    "version",
                    "done"
                ],
                "slot": [
                    "slot_main",
                    "name"
                ]
            }
        },
        "version": {
            "block": "core/devel/version",
            "in_val": {
                "format": "details",
                "slot_weight": 40
            },
            "in_con": {
                "slot": [
                    "slot_main",
                    "name"
                ]
            }
        },
        "phpinfo_hd": {
            "block": "core/out/header",
            "in_val": {
                "text": "PHP Info",
                "slot_weight": 60
            },
            "in_con": {
                "enable": [
                    "phpinfo",
                    "done"
                ],
                "slot": [
                    "slot_main",
                    "name"
                ]
            }
        },
        "phpinfo": {
            "block": "core/devel/phpinfo",
            "in_con": {
                "slot": [
                    "slot_main",
                    "name"
                ]
            },
            "in_val": {
                "slot_weight": 70
            }
        }
    }
}