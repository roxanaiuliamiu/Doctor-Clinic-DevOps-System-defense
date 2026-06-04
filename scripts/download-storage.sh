#!/bin/bash
# download-storage.sh
# Downloads application images from GitHub to storage/app/public/
# Run this after deployment: bash scripts/download-storage.sh

set -e

GITHUB_RAW="https://raw.githubusercontent.com/MunirAlTawil/Doctor-Clinic-DevOps-System/main/storage_images"
STORAGE_DIR="$(dirname "$0")/../storage/app/public"

mkdir -p "$STORAGE_DIR/specialties" "$STORAGE_DIR/doctor-profiles" "$STORAGE_DIR/pages"

echo "Downloading specialty images..."
SPECIALTIES=(
    "1861503635930689.webp"
    "1861504019482036.webp"
    "1861504078914091.png"
    "1861504099470042.jpg"
    "1861510649115236.webp"
    "1861511170098071.webp"
    "1861511223131855.jpg"
    "1861511273602480.jpg"
    "1861511309794122.jpg"
    "1861511524244317.jpg"
    "1861511561093747.jpeg"
    "1861511594045550.jpg"
    "1861511639824742.jpg"
    "1861511668439185.jpg"
)
for f in "${SPECIALTIES[@]}"; do
    curl -sL "$GITHUB_RAW/specialties/$f" -o "$STORAGE_DIR/specialties/$f" && echo "✓ specialties/$f"
done

echo "Downloading doctor profile images..."
PROFILES=(
    "aKvFJHB8KjRBC4MemMk5WvOyrQJMQzQXiO54WN3Z.jpg"
    "345q8Wm5Pxra0gPmgw1a1ESRTwd2uY03XKfC6fPX.jpg"
    "dhLWlsDbTI3BmwO1qOUb37uBc3yJGBOJEzLNa9Rl.jpg"
)
for f in "${PROFILES[@]}"; do
    curl -sL "$GITHUB_RAW/doctor-profiles/$f" -o "$STORAGE_DIR/doctor-profiles/$f" && echo "✓ doctor-profiles/$f"
done

echo "Downloading page images..."
curl -sL "$GITHUB_RAW/pages/ba49E5eYvMvaVtxZksN8BNGACb8ZV6q9SBC6LXoZ.jpg" -o "$STORAGE_DIR/pages/ba49E5eYvMvaVtxZksN8BNGACb8ZV6q9SBC6LXoZ.jpg" && echo "✓ pages/page_image"

echo ""
echo "All storage images downloaded successfully!"
