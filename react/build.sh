#!/usr/bin/env bash

set -e
REACT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
echo $REACT_DIR
TARGET="$REACT_DIR/../scripts"
echo $TARGET

cd "$REACT_DIR/web-ui"

npm run build

cd "$TARGET"

# remove web-ui from target
ls | grep -v -E '^(core|css|favicon\.ico|img|js|KioskBoard|__pycache__|todo\.txt)$' | xargs rm -rf

# install	web-ui
cp -r "$REACT_DIR/web-ui/dist/"* "$TARGET/"

cd "$SCRIPT_DIR"
