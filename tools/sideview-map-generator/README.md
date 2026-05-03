# Side-View Pixel Map Generator

Script này tạo map pixel 2D góc nhìn ngang theo hướng "game-ready" thay vì map phẳng/chán.

## Mục tiêu thiết kế

- Map có nhịp chơi: đoạn an toàn, đoạn thử thách, khoảng trống có thể nhảy.
- Độ cao mặt đất thay đổi có kiểm soát (không bị "không thể qua").
- Có nhiều lớp rõ ràng: nền, terrain, hazard, decor, collision, marker.
- Sinh map theo `seed` để tái lập đúng bản đồ khi cần debug.

## Chạy script

```bash
node tools/sideview-map-generator/generate-sideview-map.js --seed 20260504 --out game_maps/forest_run.json
```

## Tùy chọn

- `--seed`: số nguyên để tái lập map.
- `--width`: số cột tile (mặc định `220`).
- `--height`: số hàng tile (mặc định `44`).
- `--tileSize`: kích thước tile pixel (mặc định `16`).
- `--out`: file output JSON (mặc định `game_maps/sideview_map.json`).
- `--theme`: nhãn theme metadata.
- `--biome`: nhãn biome metadata.

Ví dụ:

```bash
node tools/sideview-map-generator/generate-sideview-map.js --seed 99 --width 280 --height 52 --tileSize 16 --theme ruins --biome volcanic --out game_maps/ruins_99.json
```

## Output

Script xuất:

- `*.json`: file map chuẩn Tiled JSON.
- `*.preview.txt`: bản preview ASCII để xem layout nhanh không cần mở editor.

## Layer trong map

- `bg`: nền trời.
- `terrain`: mặt đất, nền đất, platform.
- `hazards`: spike/hazard.
- `decor`: cây đá, coin, cloud.
- `collision`: object layer tự build từ terrain.
- `markers`: object spawn/goal.

## Lưu ý tích hợp game engine

1. Script đang dùng tile id mẫu (`TILE_IDS`) để demo.
2. Bạn thay image tileset thật ở phần:
   - `tilesets[0].image`
3. Trong engine, đọc layer `collision` để tạo collider chuẩn.
4. Nếu dùng Godot/Unity/Phaser, giữ `seed` để build map tái lập giữa client/server.

## Hướng nâng cấp tiếp

- Thêm generator theo "zone": village -> cave -> boss room.
- Thêm luật "landmark placement" để map có điểm nhớ.
- Thêm template mini-biome riêng cho mỗi chương.
