#!/bin/sh
# Evil Portal Universal v3.0 - Pineapple Install Script
# Run this on your WiFi Pineapple via SSH

MODULE="/root/pineapple/modules/EvilPortal"
PORTALS="$MODULE/portals"

echo "[*] Evil Portal Universal v3.0 Installer"
echo "[*] Target: $PORTALS/MyPortal.php"

# Create directories
mkdir -p "$PORTALS"
mkdir -p "$MODULE/logs"

# Copy files (assumes this script is run from the extracted folder)
cp MyPortal.php "$PORTALS/"
cp helper.php "$MODULE/"

# Set permissions
chmod 644 "$PORTALS/MyPortal.php"
chmod 644 "$MODULE/helper.php"
chmod 755 "$MODULE/logs"

echo "[+] MyPortal.php installed to Portal Library"
echo "[*] Next steps:"
echo "    1. Open Pineapple Web UI: http://172.16.42.1:1471/"
echo "    2. Go to Modules -> Evil Portal"
echo "    3. Click 'Portal Library' and select 'MyPortal'"
echo "    4. Click 'Activate'"
echo ""
echo "[*] API: http://172.16.42.1/captiveportal/helper.php?action=status"
