<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    | Aquí forzamos la URL para que, si Railway no la lee, use esta por defecto.
    */

    'cloud_url' => env('CLOUDINARY_URL', 'cloudinary://942191234587844:VmNYB6w4vj3DdLql9SZSKVofOi0@dcj5rcpi8'),

];