<?php

/**
 * Ảnh phòng: lưu dưới storage/app/public/{directory}/...
 * URL công khai: /storage/{directory}/... (cần php artisan storage:link)
 */
return [

    'cache_version' => env('ROOM_IMAGES_VERSION', '20260406'),

    'directory' => env('ROOM_IMAGES_DIRECTORY', 'room_images'),

    'subdirs' => [
        'rooms' => 'rooms',
        'room_types' => 'room_types',
        'pool' => env('ROOM_IMAGES_POOL_SUBDIR', 'pool'),
    ],

    /**
     * URL mẫu để tải ảnh (pexels — phòng khách sạn).
     */
    'sample_urls' => [
        'https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271619/pexels-photo-271619.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/262048/pexels-photo-262048.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/279746/pexels-photo-279746.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271816/pexels-photo-271816.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/2376997/pexels-photo-2376997.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/212269/pexels-photo-212269.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271897/pexels-photo-271897.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/14746032/pexels-photo-14746032.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/10343928/pexels-photo-10343928.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/10389176/pexels-photo-10389176.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/5439496/pexels-photo-5439496.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/14021931/pexels-photo-14021931.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/3940733/pexels-photo-3940733.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/13722872/pexels-photo-13722872.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/3688261/pexels-photo-3688261.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/29006838/pexels-photo-29006838.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/1579253/pexels-photo-1579253.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271643/pexels-photo-271643.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/276224/pexels-photo-276224.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271706/pexels-photo-271706.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271707/pexels-photo-271707.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271708/pexels-photo-271708.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271711/pexels-photo-271711.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271713/pexels-photo-271713.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271715/pexels-photo-271715.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271716/pexels-photo-271716.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/189295/pexels-photo-189295.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=800',
        'https://images.pexels.com/photos/271612/pexels-photo-271612.jpeg?auto=compress&cs=tinysrgb&w=800',
    ],

    /**
     * Bộ ảnh thay thế (Unsplash CDN — khác nguồn Pexels/Picsum).
     * Dùng bởi: php artisan room-images:replace-all
     */
    'unsplash_room_urls' => [
        'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1566664047869-c9e72579a23c?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1560185007-c5ca9d2c014d?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1512918728675-ed5a149efc6b?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1540518612216-5b69f9f01d5c?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1538686230502-18bf23d75a25?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1574643156929-51fa098b0394?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1556020682-ae6ab7da5215?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1560448075-bb485b067938?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1536376072261-38c75010e6c9?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1600210492493-0946911123ea?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1600566752355-35792bedcfea?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1600047509807-ba8f99d2cd5a?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1600121848594-d8644e57abab?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1600047509352-33bbbf65d21c?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1591088398330-aa7ad18c7e6f?auto=format&fit=crop&w=1200&q=85',
        'https://images.unsplash.com/photo-1567767292278-a4f21b2e1217?auto=format&fit=crop&w=1200&q=85',
    ],

];
