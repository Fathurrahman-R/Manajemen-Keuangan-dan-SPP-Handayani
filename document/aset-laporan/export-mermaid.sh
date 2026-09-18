#!/usr/bin/env bash
# Export semua diagram mermaid (*.mmd) di direktori target ke PNG pakai
# mermaid-cli via Docker (image: minlag/mermaid-cli). Tiap file .mmd
# diexport ke export/<nama>.png relatif terhadap direktori itu.
#
# Pakai:
#   ./export-mermaid.sh                        # export semua mermaid-* di sini
#   ./export-mermaid.sh mermaid-erd            # export satu direktori saja
#   ./export-mermaid.sh mermaid-erd -s 2       # override scale (default 4)
#   ./export-mermaid.sh mermaid-erd -b white   # override background (default: transparent; contoh lain: '#ffffff')

set -euo pipefail

cd "$(dirname "$0")"

SCALE=4
BACKGROUND=transparent
DIRS=()

while [[ $# -gt 0 ]]; do
    case "$1" in
        -s)
            SCALE="$2"
            shift 2
            ;;
        -b)
            BACKGROUND="$2"
            shift 2
            ;;
        *)
            DIRS+=("$1")
            shift
            ;;
    esac
done

if [[ ${#DIRS[@]} -eq 0 ]]; then
    for d in mermaid-*/; do
        DIRS+=("${d%/}")
    done
fi

for dir in "${DIRS[@]}"; do
    [[ -d "$dir" ]] || { echo "Lewati '$dir': direktori tidak ada"; continue; }

    mkdir -p "$dir/export"

    for mmd in "$dir"/*.mmd; do
        [[ -e "$mmd" ]] || continue
        name="$(basename "${mmd%.mmd}")"
        echo "Export $mmd -> $dir/export/$name.png"
        MSYS_NO_PATHCONV=1 docker run --rm -u "$(id -u):$(id -g)" \
            -v "$(pwd)/$dir":/data \
            -w /data \
            minlag/mermaid-cli \
            -i "$name.mmd" -o "export/$name.png" -s "$SCALE" -b "$BACKGROUND"
    done
done
