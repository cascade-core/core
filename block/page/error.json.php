{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "outputs": {
        "done": 1,
        "title": "Page not found"
    },
    "blocks": {
        "skeleton": {
            "block": "core/out/page",
            "force_exec": 1
        },
        "h1": {
            "block": "core/out/header",
            "in_val": {
                "level": 1,
                "slot_weight": 1
            },
            "in_con": {
                "text": [
                    "page_title",
                    "title"
                ]
            }
        },
        "page_error": {
            "block": "core/out/message",
            "force_exec": 1,
            "in_val": {
                "is_error": 1,
                "title": "Sorry!",
                "text": "Page not found.",
                "http_status_code": 404
            }
        }
    }
}