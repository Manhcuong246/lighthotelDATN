#!/usr/bin/env node
/**
 * Side-view pixel map generator (Tiled JSON output).
 *
 * Design goals:
 * - Deterministic generation by seed.
 * - Platformer-safe topology (jumpable gaps, controlled height deltas).
 * - Separated layers for terrain, hazards, decorations, pickups.
 * - Collision object layer auto-built from terrain tiles.
 *
 * Usage:
 *   node tools/sideview-map-generator/generate-sideview-map.js --seed 20260504 --out game_maps/forest_run.json
 */

const fs = require("node:fs");
const path = require("node:path");

const TILE_IDS = {
  EMPTY: 0,
  SKY: 1,
  GROUND_TOP: 2,
  GROUND_FILL: 3,
  PLATFORM: 4,
  SPIKES: 5,
  PLANT: 6,
  ROCK: 7,
  COIN: 8,
  CLOUD: 9,
};

const DEFAULTS = {
  width: 220,
  height: 44,
  tileSize: 16,
  seed: 20260504,
  output: "game_maps/sideview_map.json",
  theme: "forest",
  biome: "temperate",
};

function parseArgs(argv) {
  const args = { ...DEFAULTS };
  for (let i = 2; i < argv.length; i += 1) {
    const token = argv[i];
    const next = argv[i + 1];
    if (!token.startsWith("--")) continue;
    const key = token.slice(2);
    if (next && !next.startsWith("--")) {
      args[key] = next;
      i += 1;
    } else {
      args[key] = true;
    }
  }

  args.width = clamp(parseInt(args.width, 10) || DEFAULTS.width, 80, 800);
  args.height = clamp(parseInt(args.height, 10) || DEFAULTS.height, 28, 120);
  args.tileSize = clamp(parseInt(args.tileSize, 10) || DEFAULTS.tileSize, 8, 64);
  args.seed = Number.parseInt(args.seed, 10) || DEFAULTS.seed;
  args.output = String(args.out || args.output || DEFAULTS.output);
  args.theme = String(args.theme || DEFAULTS.theme);
  args.biome = String(args.biome || DEFAULTS.biome);
  return args;
}

function clamp(value, min, max) {
  return Math.max(min, Math.min(max, value));
}

function mulberry32(seed) {
  let t = seed >>> 0;
  return () => {
    t += 0x6d2b79f5;
    let x = Math.imul(t ^ (t >>> 15), 1 | t);
    x ^= x + Math.imul(x ^ (x >>> 7), 61 | x);
    return ((x ^ (x >>> 14)) >>> 0) / 4294967296;
  };
}

function randomInt(rng, min, max) {
  return Math.floor(rng() * (max - min + 1)) + min;
}

function weightedChoice(rng, items) {
  const total = items.reduce((acc, item) => acc + item.weight, 0);
  let roll = rng() * total;
  for (const item of items) {
    roll -= item.weight;
    if (roll <= 0) return item.value;
  }
  return items[items.length - 1].value;
}

function createLayer(width, height, fill = TILE_IDS.EMPTY) {
  const data = new Array(width * height).fill(fill);
  return {
    width,
    height,
    data,
    get(x, y) {
      if (x < 0 || y < 0 || x >= width || y >= height) return TILE_IDS.EMPTY;
      return data[y * width + x];
    },
    set(x, y, value) {
      if (x < 0 || y < 0 || x >= width || y >= height) return;
      data[y * width + x] = value;
    },
  };
}

function fillBackground(bg) {
  for (let y = 0; y < bg.height; y += 1) {
    for (let x = 0; x < bg.width; x += 1) {
      bg.set(x, y, TILE_IDS.SKY);
    }
  }
}

function generateGroundProfile(width, height, rng) {
  const profile = new Array(width);
  const topMin = Math.floor(height * 0.55);
  const topMax = Math.floor(height * 0.75);
  let current = Math.floor(height * 0.68);

  let x = 0;
  let lastSegment = "flat";
  while (x < width) {
    const segmentType = weightedChoice(rng, [
      { value: "flat", weight: 36 },
      { value: "rise", weight: lastSegment === "rise" ? 8 : 20 },
      { value: "fall", weight: lastSegment === "fall" ? 8 : 20 },
      { value: "gap", weight: 14 },
      { value: "plateau", weight: 10 },
    ]);
    lastSegment = segmentType;

    if (segmentType === "gap") {
      const gapW = randomInt(rng, 2, 4);
      for (let i = 0; i < gapW && x < width; i += 1, x += 1) {
        profile[x] = null;
      }
      continue;
    }

    const segW = randomInt(rng, 7, 16);
    let target = current;
    if (segmentType === "rise") target = clamp(current - randomInt(rng, 1, 2), topMin, topMax);
    if (segmentType === "fall") target = clamp(current + randomInt(rng, 1, 2), topMin, topMax);
    if (segmentType === "plateau") target = clamp(current + randomInt(rng, -1, 1), topMin, topMax);

    for (let i = 0; i < segW && x < width; i += 1, x += 1) {
      if (segmentType === "flat" || segmentType === "plateau") {
        profile[x] = current;
      } else {
        const t = (i + 1) / segW;
        const y = Math.round(current + (target - current) * t);
        profile[x] = clamp(y, topMin, topMax);
      }
    }
    current = target;
  }

  // Safety pass: avoid impossible jumps due to consecutive gaps.
  for (let i = 2; i < width - 2; i += 1) {
    if (profile[i] === null && profile[i - 1] === null) {
      profile[i] = profile[i - 2] ?? profile[i + 2] ?? current;
    }
  }

  return profile;
}

function paintTerrain(terrain, profile) {
  for (let x = 0; x < terrain.width; x += 1) {
    const topY = profile[x];
    if (topY === null) continue;
    terrain.set(x, topY, TILE_IDS.GROUND_TOP);
    for (let y = topY + 1; y < terrain.height; y += 1) {
      terrain.set(x, y, TILE_IDS.GROUND_FILL);
    }
  }
}

function placePlatforms(terrain, pickups, profile, rng) {
  const zones = Math.floor(terrain.width / 24);
  for (let i = 0; i < zones; i += 1) {
    const startX = i * 24 + randomInt(rng, 4, 12);
    const width = randomInt(rng, 3, 7);
    if (startX + width >= terrain.width - 2) continue;

    const anchorY = nearestGroundY(profile, startX);
    if (anchorY === null) continue;
    const platformY = clamp(anchorY - randomInt(rng, 3, 5), 4, terrain.height - 10);

    for (let x = startX; x < startX + width; x += 1) {
      if (profile[x] !== null && profile[x] - platformY < 3) continue;
      terrain.set(x, platformY, TILE_IDS.PLATFORM);
      if (rng() < 0.4) {
        pickups.set(x, platformY - 1, TILE_IDS.COIN);
      }
    }
  }
}

function nearestGroundY(profile, x) {
  for (let dx = 0; dx < 8; dx += 1) {
    const right = profile[x + dx];
    if (right !== undefined && right !== null) return right;
    const left = profile[x - dx];
    if (left !== undefined && left !== null) return left;
  }
  return null;
}

function placeHazards(hazards, profile, rng) {
  for (let x = 3; x < hazards.width - 3; x += 1) {
    const y = profile[x];
    if (y === null) continue;

    const isSafeZone = x < 8 || x > hazards.width - 12;
    const shouldSpike = !isSafeZone && rng() < 0.06;

    if (shouldSpike) {
      const left = profile[x - 1];
      const right = profile[x + 1];
      if (left !== null && right !== null && Math.abs(left - y) <= 1 && Math.abs(right - y) <= 1) {
        hazards.set(x, y - 1, TILE_IDS.SPIKES);
      }
    }
  }
}

function placeDecor(bgDecor, terrain, profile, rng) {
  // Foreground plants/rocks.
  for (let x = 2; x < terrain.width - 2; x += 1) {
    const y = profile[x];
    if (y === null) continue;
    const tileAbove = terrain.get(x, y - 1);
    if (tileAbove !== TILE_IDS.EMPTY) continue;
    const roll = rng();
    if (roll < 0.05) bgDecor.set(x, y - 1, TILE_IDS.PLANT);
    else if (roll < 0.08) bgDecor.set(x, y - 1, TILE_IDS.ROCK);
  }

  // Background clouds.
  for (let x = 5; x < terrain.width - 5; x += randomInt(rng, 6, 14)) {
    if (rng() < 0.45) {
      const y = randomInt(rng, 2, Math.floor(terrain.height * 0.3));
      bgDecor.set(x, y, TILE_IDS.CLOUD);
    }
  }
}

function buildCollisionObjects(terrain, tileSize) {
  const solids = new Set([TILE_IDS.GROUND_TOP, TILE_IDS.GROUND_FILL, TILE_IDS.PLATFORM]);
  const objects = [];
  let id = 1;
  for (let y = 0; y < terrain.height; y += 1) {
    let x = 0;
    while (x < terrain.width) {
      if (!solids.has(terrain.get(x, y))) {
        x += 1;
        continue;
      }
      const start = x;
      while (x < terrain.width && solids.has(terrain.get(x, y))) {
        x += 1;
      }
      const runW = x - start;
      objects.push({
        id: id++,
        name: "solid",
        type: "collision",
        x: start * tileSize,
        y: y * tileSize,
        width: runW * tileSize,
        height: tileSize,
        rotation: 0,
        visible: true,
      });
    }
  }
  return objects;
}

function findSpawnAndGoal(profile, width, height, tileSize) {
  const spawnX = 3;
  const goalX = width - 4;
  const spawnY = (nearestGroundY(profile, spawnX) ?? height - 8) - 1;
  const goalY = (nearestGroundY(profile, goalX) ?? height - 8) - 1;
  return [
    { id: 100000, name: "spawn", type: "spawn", x: spawnX * tileSize, y: spawnY * tileSize, width: tileSize, height: tileSize, visible: true },
    { id: 100001, name: "goal", type: "goal", x: goalX * tileSize, y: goalY * tileSize, width: tileSize, height: tileSize, visible: true },
  ];
}

function makeTileLayer(id, name, layer) {
  return {
    id,
    name,
    type: "tilelayer",
    width: layer.width,
    height: layer.height,
    opacity: 1,
    visible: true,
    x: 0,
    y: 0,
    data: layer.data,
  };
}

function toTiledMap(config, layers, collisionObjects, markerObjects) {
  return {
    compressionlevel: -1,
    infinite: false,
    renderorder: "right-down",
    orientation: "orthogonal",
    tilewidth: config.tileSize,
    tileheight: config.tileSize,
    width: config.width,
    height: config.height,
    version: "1.10",
    tiledversion: "1.10.2",
    type: "map",
    properties: [
      { name: "seed", type: "int", value: config.seed },
      { name: "theme", type: "string", value: config.theme },
      { name: "biome", type: "string", value: config.biome },
      { name: "generator", type: "string", value: "sideview-map-generator-v1" },
    ],
    layers: [
      makeTileLayer(1, "bg", layers.bg),
      makeTileLayer(2, "terrain", layers.terrain),
      makeTileLayer(3, "hazards", layers.hazards),
      makeTileLayer(4, "decor", layers.decor),
      {
        id: 5,
        name: "collision",
        type: "objectgroup",
        visible: true,
        opacity: 1,
        x: 0,
        y: 0,
        draworder: "topdown",
        objects: collisionObjects,
      },
      {
        id: 6,
        name: "markers",
        type: "objectgroup",
        visible: true,
        opacity: 1,
        x: 0,
        y: 0,
        draworder: "topdown",
        objects: markerObjects,
      },
    ],
    tilesets: [
      {
        firstgid: 1,
        name: "pixel-sideview-tiles",
        tilewidth: config.tileSize,
        tileheight: config.tileSize,
        tilecount: 64,
        columns: 8,
        image: "tilesets/pixel-sideview-tiles.png",
        imagewidth: config.tileSize * 8,
        imageheight: config.tileSize * 8,
      },
    ],
    nextlayerid: 7,
    nextobjectid: 100002,
  };
}

function writeJson(filePath, payload) {
  const absolute = path.resolve(process.cwd(), filePath);
  const dir = path.dirname(absolute);
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(absolute, JSON.stringify(payload, null, 2), "utf8");
  return absolute;
}

function writeAsciiPreview(filePath, layers) {
  const toGlyph = (x, y) => {
    if (layers.hazards.get(x, y) === TILE_IDS.SPIKES) return "^";
    if (layers.terrain.get(x, y) === TILE_IDS.GROUND_TOP) return "#";
    if (layers.terrain.get(x, y) === TILE_IDS.GROUND_FILL) return "█";
    if (layers.terrain.get(x, y) === TILE_IDS.PLATFORM) return "=";
    if (layers.decor.get(x, y) === TILE_IDS.COIN) return "o";
    if (layers.decor.get(x, y) === TILE_IDS.PLANT) return "*";
    return " ";
  };

  const lines = [];
  for (let y = 0; y < layers.terrain.height; y += 1) {
    let line = "";
    for (let x = 0; x < layers.terrain.width; x += 1) {
      line += toGlyph(x, y);
    }
    lines.push(line);
  }
  const previewPath = filePath.replace(/\.json$/i, ".preview.txt");
  const absolute = path.resolve(process.cwd(), previewPath);
  fs.writeFileSync(absolute, lines.join("\n"), "utf8");
  return absolute;
}

function main() {
  const config = parseArgs(process.argv);
  const rng = mulberry32(config.seed);

  const bg = createLayer(config.width, config.height, TILE_IDS.EMPTY);
  const terrain = createLayer(config.width, config.height, TILE_IDS.EMPTY);
  const hazards = createLayer(config.width, config.height, TILE_IDS.EMPTY);
  const decor = createLayer(config.width, config.height, TILE_IDS.EMPTY);

  fillBackground(bg);
  const profile = generateGroundProfile(config.width, config.height, rng);
  paintTerrain(terrain, profile);
  placePlatforms(terrain, decor, profile, rng);
  placeHazards(hazards, profile, rng);
  placeDecor(decor, terrain, profile, rng);

  const collisionObjects = buildCollisionObjects(terrain, config.tileSize);
  const markerObjects = findSpawnAndGoal(profile, config.width, config.height, config.tileSize);
  const map = toTiledMap(config, { bg, terrain, hazards, decor }, collisionObjects, markerObjects);

  const mapPath = writeJson(config.output, map);
  const previewPath = writeAsciiPreview(config.output, { terrain, hazards, decor });

  // Keep output short and CI-friendly.
  process.stdout.write(
    [
      "Side-view map generated successfully.",
      `- seed: ${config.seed}`,
      `- map: ${mapPath}`,
      `- preview: ${previewPath}`,
      `- size: ${config.width}x${config.height} tiles`,
    ].join("\n"),
  );
}

main();
