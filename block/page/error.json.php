{
    "_": "<?php printf('_%c%c}%c',34,10,10);__halt_compiler();?>",
    "outputs": {
        "done": 1,
        "title": "Page not found"
    },
    "block:skeleton": {
        ".block": "core/out/page",
        ".force_exec": 1
    },
    "block:h1": {
        ".block": "core/out/header",
        "level": 1,
        "text": [
            "page_title:title"
        ],
        "slot_weight": 1
    },
    "block:page_error": {
        ".block": "core/out/message",
        ".force_exec": 1,
        "is_error": 1,
        "title": "Sorry!",
        "text": "Page not found.",
        "http_status_code": 404
    }
}