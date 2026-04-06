<?php

/**
 * Ảnh phòng: lưu dưới storage/app/public/{directory}/...
 * URL công khai: /storage/{directory}/... (cần php artisan storage:link)
 */
return [

    'directory' => env('ROOM_IMAGES_DIRECTORY', 'room_images'),

    'subdirs' => [
        'rooms' => 'rooms',
        'room_types' => 'room_types',
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

];
