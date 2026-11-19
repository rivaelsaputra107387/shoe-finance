<?php

return [

    /*
    | Koreksi jurnal posted hanya dibuka sementara selama pembetulan pembukuan.
    | Setelah selesai, kembalikan ke false lalu jalankan php artisan config:clear.
    */
    'allow_posted_correction' => (bool) env('FINLOG_ALLOW_POSTED_JOURNAL_CORRECTION', false),

];
