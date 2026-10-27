# Recon Script & PHP Version Auditor (PHP2Shell Detector)

This repository contains a specialized Bash reconnaissance and audit tool developed by **@Pauloxc6**. It is designed to evaluate web servers, detect Web Application Firewalls (WAFs), verify SSL/TLS configurations, and identify outdated or vulnerable PHP versions exposed to engine-level security risks like **PHP2Shell**.

<video width="320" height="240" controls style="border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); background-color: #000; max-width: 100%; height: auto;">
  <source src="./videos/record.mp4" type="video/mp4">
  Your browser does not support the video tag.
</video>

---

## ⚠️ Disclaimer & Responsible Use

> **FOR EDUCATIONAL AND AUTHORIZED AUDITING PURPOSES ONLY.**
> This tool is provided solely for security researchers, system administrators, and authorized penetration testers to assess the security posture of systems they own or have explicit, written permission to test.
> * **No Malicious Intent:** The author (@Pauloxc6) assumes no responsibility or liability for any misuse, unauthorized scanning, or damage caused by this script.
> * **Legal Compliance:** Unlawful scanning of networks or systems without prior authorization is strictly illegal and violates computer misuse laws globally.

---

## 🛡️ Non-Exploitative Detection Model & Risk Mitigation

### Non-Destructive Scanning (Safe Verification)

This tool **DOES NOT** attempt to exploit systems, upload payloads, execute malicious commands, or deliver web shells. It performs purely **passive and safe active fingerprinting**:

- Reads standard HTTP response headers (e.g., `X-Powered-By`, `Server`) in `web` mode.
- Queries local runtime environments using the PHP CLI interpreter in `local` mode.
- Normalizes version strings safely to evaluate patch levels.
- Cross-references version integers against known vulnerable release lifecycles without injecting malicious data or stressing memory limits.

### Associated Operational Risks

When executing reconnaissance scripts against target infrastructure, operators should be aware of potential side effects:

1. **WAF / IDS / IPS Tripping:** Rapid automated queries or banner probing may trigger perimeter defense mechanisms, leading to temporary IP blocks or rate limiting.
2. **Log Artifacts:** Generating connection requests creates standard HTTP access and error log entries on the target host.

---

## 🚀 Features

- **Dual Operation Modes:** 

- **Web Mode (`web`):** Remote HTTP/HTTPS reconnaissance, SSL checks, WAF detection, and banner grabbing.
- **Local Mode (`local`):** Local CLI inspection to audit runtime environment configuration, SAPI details, and version vulnerabilities directly.

- **WAF Detection:** Scans server headers, cookies, and network IP ranges against a predefined YAML definition (`waf.yaml`) to fingerprint enterprise WAFs (Cloudflare, Akamai, Imperva, AWS WAF, etc.).
- **SSL/TLS Validation:** Performs automated checks using OpenSSL to verify if secure connections are established and valid.
- **HTTP Header Analysis:** Extracts status codes, redirection locations, and server banners (`Server`, `X-Powered-By`).
- **PHP Version Auditing:** Parses underlying PHP versions, safely normalizes semantic versioning structures, and checks them against predefined baseline rules (`msgaction`).

- **Vulnerability Alerting (`PHP2Shell` Context):** Highlights risky or end-of-life PHP runtimes, notifying operators of potential exposure vectors related to core engine memory-safety flaws.

---

## 📋 Prerequisites

Before running the script, ensure you have the following dependencies installed on your Linux/Unix system:

- `bash` (v4+ recommended)
- `curl`
- `wget`
- `openssl`
- `nc` (Netcat)
- `yq` (YAML processor for command-line)
- `ping` / `grep` / `awk` / `sed` / `cut` (standard coreutils)

---

## ⚙️ Installation & Setup

1. Clone or download the script files into your working directory.
2. Ensure you have the `waf.yaml` configuration file present in the same directory for WAF detection to function properly.
3. Make the main script executable:

```bash
chmod +x php2shell-check.sh
```

---

## 💻 Usage

Run the script from your terminal using the available command-line flags:

```bash
┌─[~/github-rep/security/CVEs/php2shell-check]─[paulo@voidlinux]─[192.168.4.50]
└─╼ $ bash php2shell-check.sh --help

   ___  __ _____  ___  ______ ________   __      _______ _____________ __
  / _ \/ // / _ \|_  |/ __/ // / __/ /  / / ____/ ___/ // / __/ ___/ //_/
 / ___/ _  / ___/ __/_\ \/ _  / _// /__/ /_/___/ /__/ _  / _// /__/ ,<   
/_/  /_//_/_/  /____/___/_//_/___/____/____/   \___/_//_/___/\___/_/|_|  
                                                                         
By: @Pauloxc6
Usage: bash php2shell-check.sh [OPTIONS]

Options:
    -h, --help               Display this help message
    -d, --debug              Enable mode debuging

    Basic:
    --uri <uri>              Enables URI connection support | Ex: php://example.com:80
    -u, --url <url>          Target URL (for 'web' mode)
    -p, --port <port>        Target port (default: standard HTTP/HTTPS)
    -m, --mod <mod>          Operation mode: 'web' (remote HTTP check) or 'local' (use local PHP CLI version check)
    
    Misc:
    -c, --cookie <string>    Cookie string to include in requests (e.g., session cookies)
    -a, --user-agent <str>   Custom User-Agent string
    -s, --ssl                Enable HTTPS/SSL connection
    --no-bar                 Disables the progress bar

    Output:
    -o, --output             Enable the saving of information! Ex: -o csv:foo (CSV, JSON, YAML)
    -f, --separator          Change the type of delimiter! Supported: | , ; :

Examples:
    # Check a remote web target
    bash php2shell-check.sh -u example.com -p 443 -m web -s
    bash php2shell-check.sh --uri php://example.com:443 -m web -s
    bash php2shell-check.sh --uri php://example.com:8080 --output json:filename
    bash php2shell-check.sh --uri php://example.com:8080 --output csv:filename -f ';'

    # Check using local PHP CLI
    bash php2shell-check.sh -m local
```

### Examples

**1. Check a remote web target via HTTPS:**

```bash
./php2shell-check.sh -u example.com -p 443 -m web -s
```

**2. Check local PHP CLI version and environment:**

```bash
./php2shell-check.sh -m local
```

**3. Scan multiple remote targets via file:**

```bash
while IFS= read -r line; do
    ip="$(   echo "${line}" | awk -F: '{print $1}' )"
    port="$( echo "${line}" | awk -F: '{print $2}' )"
    ./php2shell-check.sh --uri php://"${ip}":"${port}" -m web --no-bar
done < "ips_list.txt"

ips_list.txt
10.0.0.1:8080
10.0.1.2:8080
10.0.0.10:80
10.0.2.5:443
```

**4. Using the Crontab Service:***

```bash
* * * * * /opt/php2shell-check.sh --uri php://server001:80 -m web --no-bar -o json:log-server001

# Read
tail -f /opt/output/log-server.json | jq -r '.[].vuln'
```

---

## 🔍 Understanding the Vulnerability: PHP2Shell Risk

### What is PHP2Shell?

**PHP2Shell** refers to a class of critical memory-safety vulnerabilities in core PHP (tracked under identifiers such as **CVE-2026-109618** and **CVE-2026-109619**). These flaws allow unauthenticated remote code execution (RCE) or sensitive memory disclosure across multiple legacy and unpatched PHP versions.

1. **Engine-Level Flaw:** Unlike common application bugs or plugin vulnerabilities, PHP2Shell resides directly in the core interpreter logic dealing with out-of-memory handling and stale global states across requests.
2. **Pre-Authentication Exposure:** Attackers can target ordinary web requests without requiring valid credentials or application-level entry points.
3. **Severe Impact:** Unpatched instances can permit threat actors to bypass security controls, extract environment secrets, and deploy persistent backdoors (**web shells**).

### How this Script Helps (Without Exploiting)

The script checks the target's exposed `X-Powered-By` HTTP header (or local CLI interpreter version), strips formatting/whitespace anomalies, extracts version milestones, and cross-references them against secure baseline criteria. If an outdated release vulnerable to such engine flaws is detected, it flags the host immediately so administrators can apply official core updates (e.g., upgrading to fixed releases like PHP 8.2.35, 8.3.36, 8.4.27, 8.5.12, or newer).
