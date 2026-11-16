
<style>
    .log-card {
  background: #1f2937;
  color: #d1d5db;
  border-radius: 10px;
  padding: 20px;
  font-family: monospace;
  margin-top: 30px;
}
.log-header {
  font-weight: bold;
  font-size: 1.2rem;
  margin-bottom: 10px;
}
.log-body {
  max-height: 500px;
  overflow-y: auto;
  background: #111827;
  padding: 15px;
  border-radius: 8px;
}
.log-line {
  padding: 5px 0;
  border-bottom: 1px solid #374151;
}
.ip {
  color: #9ca3af;
  font-size: 0.85rem;
}

</style>

<div class="log-card">
  <div class="log-header">📝 LOG AKTIVITAS</div>
  <div class="log-body" id="log-body">
    <?php
    $logs = mysqli_query($conn, "SELECT * FROM logs ORDER BY timestamp DESC LIMIT 100");
    while ($log = mysqli_fetch_assoc($logs)) {
      echo "<div class='log-line'>[{$log['timestamp']}] <strong>{$log['username']}</strong>: {$log['action']} - <span class='ip'>{$log['ip_address']}</span></div>";
    }
    ?>
  </div>

  <!-- Terminal Output Fake -->
   
  <div class="log-header">Terminal</div>
  <div class="terminal-output" style="
    font-family: monospace;
    background-color: #000;
    color: #0f0;
    padding: 10px;
    height: 300px;
    overflow-y: auto;
    margin-top: 10px;
    border-radius: 5px;
  "></div>

  <!-- Input Bar -->
  <div style="width: 100%; padding: 10px; background-color: #111; border-radius: 0 0 5px 5px;">
    <span style="color: #0f0;">&gt;</span>
    <input id="input-command" type="text" autocomplete="off" style="
      width: 95%;
      background-color: transparent;
      color: #0f0;
      border: none;
      outline: none;
      font-family: monospace;
      font-size: 14px;
    ">
  </div>
</div>


<script>
  document.querySelector("#input-command").addEventListener("keypress", function(e) {
  if (e.key === "Enter") {
    const val = this.value.trim();
    const logContainer = document.querySelector('.terminal-output');

    function logLine(text) {
      const div = document.createElement('div');
      div.textContent = text;
      logContainer.appendChild(div);
      logContainer.scrollTop = logContainer.scrollHeight;
    }

    // ===== Command Processing =====
    if (val.toLowerCase() === "restart") {
      simulateFakeRestart();
    } else if (val.toLowerCase() === "clear") {
      logContainer.innerHTML = "";
    } else if (val.toLowerCase() === "help") {
      const helpLines = [
        "> help",
        "Available Commands:",
        "  restart                          Simulate system restart",
        "  clear                            Clear the terminal",
        "  scanvirus                        Fake virus scanning",
        "  ipconfig                         Show fake network config",
        "  createresetpassword user='xyz'  Fake password reset",
        "  delete user xyz                  Fake delete user",
      ];
      helpLines.forEach(line => logLine(line));
    } else if (val.toLowerCase() === "scanvirus") {
      logLine("> scanvirus");
      logLine("[SCAN] Scanning C:/ drive...");
      setTimeout(() => logLine("[ OK ] No threats found"), 1000);
      setTimeout(() => logLine("[SCAN] Scanning D:/ drive..."), 1500);
      setTimeout(() => logLine("[THREAT] Found: win32.fakevirus.gen"), 2500);
      setTimeout(() => logLine("[ACTION] Quarantined successfully."), 3500);
      setTimeout(() => logLine("[SCAN] Completed in 4.2s"), 4200);
    } else if (val.toLowerCase() === "ipconfig") {
      const fakeIP = "192.168.1.104";
      logLine("> ipconfig");
      logLine(`Ethernet adapter eth0:`);
      logLine(`  IPv4 Address . . . . . . . . . . : ${fakeIP}`);
      logLine(`  Subnet Mask  . . . . . . . . . . : 255.255.255.0`);
      logLine(`  Default Gateway . . . . . . . . : 192.168.1.1`);
    } else if (/^createresetpassword user='.+?'$/i.test(val)) {
      const username = val.match(/^createresetpassword user='(.+?)'$/i)[1];
      logLine(`> ${val}`);
      logLine(`[ OK ] Reset code generated for user '${username}'`);
      logLine(`[INFO] Code expires in 1 hour.`);
    } else if (/^delete user .+$/i.test(val)) {
      const username = val.split(" ")[2];
      logLine(`> ${val}`);
      logLine(`[INFO] Attempting to delete user '${username}'...`);
      setTimeout(() => logLine(`[ OK ] User '${username}' deleted.`), 1500);
    } else {
      logLine(`> ${val}`);
      logLine(`[ERROR] Unknown command: '${val}'`);
    }

    this.value = "";
  }
});

const fakeRestartOutput = [
  "> restart",
  "[INFO] Shutting down system...",
  "[ OK ] Terminating active sessions",
  "[ OK ] Closing open file handles",
  "[ OK ] Sending SIGTERM to services...",
  "[ OK ] nginx terminated",
  "[ OK ] php-fpm gracefully shut down",
  "[ OK ] mysqld flushed and stopped",
  "[INFO] Releasing system resources...",
  "[ OK ] /dev/shm cleared",
  "[ OK ] /tmp cleaned",
  "[ OK ] Orphan processes killed",
  "[INFO] Syncing and unmounting file systems...",
  "[ OK ] Disk I/O flushed",
  "[ OK ] /boot unmounted",
  "[ OK ] /home unmounted",
  "[ OK ] /sys unmounted",
  "[ OK ] /proc unmounted",
  "[ OK ] /dev unmounted",
  "[INFO] Network interfaces shutting down...",
  "[ OK ] eth0: down",
  "[ OK ] lo: down",
  "[INFO] Powering down kernel subsystems...",
  "[ OK ] Power Management Controller disabled",
  "[ OK ] Interrupt Controllers disabled",
  "[ OK ] Secure Boot Signature verified",
  "--------------------------------------------------------",
  "   _____ _           _     _               ",
  "  / ____| |         (_)   | |              ",
  " | |  __| | __ _ ___ _  __| | ___ _ __ ___ ",
  " | | |_ | |/ _` / __| |/ _` |/ _ \\ '__/ __|",
  " | |__| | | (_| \\__ \\ | (_| |  __/ |  \\__ \\",
  "  \\_____|_|\\__,_|___/_|\\__,_|\\___|_|  |___/",
  "",
  " > Created By   : Ghani",
  " > Class        : TKJ X A",
  " > Build        : Pseudo-System v3.9.21",
  " > Boot Type    : Virtualized BIOS",
  "--------------------------------------------------------",
  "[BOOT] Initializing system hardware...",
  "[ OK ] CPU: Intel(R) Core™ i7-9750H @ 2.60GHz",
  "[ OK ] RAM: 8192MB DDR4",
  "[ OK ] Storage: 240GB SSD (/dev/sda)",
  "[ OK ] Network Controller: RTL8111/8168",
  "[BOOT] Verifying bootloader...",
  "[ OK ] GRUB 2.06 located on sector 2048",
  "[ OK ] Secure Boot: ENABLED",
  "[ OK ] UEFI mode: ACTIVE",
  "[BOOT] Loading kernel version 5.15.92...",
  "[ OK ] Kernel loaded to memory @ 0x100000",
  "[ OK ] Initial RAM disk mounted",
  "[INIT] Starting essential services...",
  "[ OK ] udev daemon started",
  "[ OK ] journald log service running",
  "[ OK ] hostname set to 'server01.local'",
  "[ OK ] random seed initialized",
  "[ OK ] time synced with time.cloudflare.com",
  "[INIT] Mounting root and system partitions...",
  "[ OK ] / (root) mounted with ext4",
  "[ OK ] /var mounted",
  "[ OK ] /home mounted",
  "[ OK ] tmpfs created",
  "[INIT] Starting network...",
  "[ OK ] eth0 link up (192.168.1.100)",
  "[ OK ] DNS resolver active",
  "[ OK ] Gateway reachable",
  "[INIT] Starting user-level services...",
  "[ OK ] mysqld started on port 3306",
  "[ OK ] php-fpm socket listening",
  "[ OK ] nginx running on port 80",
  "[ OK ] sshd ready (port 22)",
  "[ OK ] cron daemon active",
  "[ OK ] firewall rules loaded (14 rules)",
  "[INFO] Performing final checks...",
  "[ OK ] CPU temperature: 42°C",
  "[ OK ] Memory usage: 318MB / 8192MB",
  "[ OK ] Disk usage: 12.4GB / 240GB",
  "[ OK ] Swap: 0MB used / 1024MB total",
  "[INFO] System restart complete.",
  "[INFO] Uptime: 0 seconds",
  "> Command completed successfully."
];

function simulateFakeRestart() {
  const logContainer = document.querySelector('.terminal-output') || document.body;
  fakeRestartOutput.forEach((line, i) => {
    setTimeout(() => {
      const div = document.createElement('div');
      div.textContent = line;
      logContainer.appendChild(div);
      window.scrollTo(0, document.body.scrollHeight);
    }, 100 * i);
  });
}

// Jalankan jika command yang diketik == "restart"
document.querySelector("#input-command").addEventListener("keypress", function(e) {
  if (e.key === "Enter") {
    const val = this.value.trim().toLowerCase();
    if (val === "restart") {
      e.preventDefault();
      this.value = "";
      simulateFakeRestart();
    }
  }
});
</script>

