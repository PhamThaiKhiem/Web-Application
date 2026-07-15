# Web application game "TaskManager: SQLi \u2192 LFI \u2192 RCE"

Linear game for [CyberRangeCZ Platform](https://docs.platform.cyberrange.cz/).

A fictional internal task-tracking web app, deliberately built with a chain
of realistic vulnerabilities. Trainees discover a hidden admin panel,
bypass login with SQL injection, dump a database with sqlmap, escalate a
local file inclusion into source-code disclosure, chain log poisoning into
remote code execution, and finish with a reverse shell to loot a final flag.

**All vulnerable application code (PHP + SQL schema) is included in this
repository** under `provisioning/roles/webserver/files/app/` and
`provisioning/roles/db-server/templates/schema.sql.j2` \u2014 there is no
external app to download; it's deployed by the `webserver` and `db-server`
roles during provisioning.

## Game Levels Summary

- Content/directory discovery with gobuster
- Manual SQL injection authentication bypass
- Automated database enumeration/dumping with sqlmap
- LFI escalated to source-code disclosure via the `php://filter` wrapper
- Apache access-log poisoning to achieve remote code execution
- Reverse shell and final loot retrieval

## Topology summary

| Host | Image | IP | Flavor |
| --- | --- | --- | --- |
| attacker | kali-2020.4 | 172.16.0.100 | standard.small |
| webserver | debian-12-x86_64 (LAMP) | 172.16.0.10 | standard.small |
| db-server | debian-12-x86_64 (MariaDB) | 172.16.0.20 | standard.small |

`db-server` only accepts connections from `webserver` (172.16.0.10); it is
not directly reachable from `attacker`, matching the scenario's design.

## Provisioning structure

```
provisioning/
├── playbook.yml                    # entry point: hosts -> attacker/webserver/db-server -> sandbox-logging
├── requirements.yml                 # shared roles: sandbox-logging, chrony, hosts-aliases, user-access
├── host_vars/                       # ansible_python_interpreter: python3 for each host
└── roles/
    ├── hosts/tasks/main.yml         # common baseline (net-tools, unzip)
    ├── attacker/tasks/main.yml      # ensures gobuster/sqlmap/netcat/wordlists are present
    ├── webserver/
    │   ├── tasks/main.yml           # installs LAMP, deploys the app, sets hostname, enables system()
    │   └── files/app/               # the actual vulnerable PHP application source
    │       ├── index.php
    │       └── admin_portal_v2/
    │           ├── login.php        # SQLi-vulnerable auth (Level 2)
    │           ├── dashboard.php    # LFI via ?page= (Levels 4 & 5)
    │           ├── config.php       # contains DB_PASS (Level 4 target)
    │           ├── home.php / tasks.php / profile.php / logout.php
    │           └── .secret_vault/crown.txt   # final flag (Level 6)
    └── db-server/
        ├── tasks/main.yml           # installs MariaDB, opens remote access, loads schema
        └── templates/schema.sql.j2  # users + secrets tables (Levels 2 & 3)
```

## Design notes / intentional adjustments from the original scenario draft

1. **LFI include logic**: the original scenario's Level 4 payload
   (`?page=php://filter/convert.base64-encode/resource=config`, no `.php`
   suffix) and Level 5 payload (`?page=/var/log/apache2/access.log`, an
   absolute path) can't both work if the vulnerable code blindly appends
   `.php` to every value of `page`. To make **both** payloads work exactly
   as written in the scenario, `dashboard.php` does **not** auto-append
   `.php` \u2014 it only strips `../` sequences and calls `include()` directly.
   The Level 4 walkthrough in `training.json` therefore uses
   `resource=config.php` (with the extension spelled out) instead of the
   original `resource=config`. Flag itself is unaffected.
2. All flags, usernames, and secrets are original values written for this
   scenario \u2014 nothing here is derived from a real breach or real system.

## Open items that still need to be verified against your CyberRangeCZ Platform instance

1. **Not tested in a real sandbox.** All PHP/SQL code and Ansible tasks were
   written and reviewed manually but not executed end-to-end (this
   environment has no PHP/MySQL runtime available to test against). Please
   dry-run the full attack chain once before publishing.
2. **`requirements.yml`** reuses the same 4 shared roles confirmed for the
   other games in this library (`sandbox-logging`, `chrony`,
   `hosts-aliases`, `user-access`).
3. **PHP version path**: the webserver role edits
   `/etc/php/8.2/apache2/php.ini` to clear `disable_functions`. If your
   `debian-12-x86_64` base image ships a different PHP version, update that
   path accordingly.
4. **Exact image names / outbound internet access**: same caveats as the
   other games in this library \u2014 confirm `kali-2020.4` and
   `debian-12-x86_64` are real image names on your platform.

## License

This repository uses a dual licensing approach:

- The code (Ansible, topology, and the vulnerable application source) is
  licensed under the terms of the MIT License
  (<https://opensource.org/license/mit>).
- The game design is licensed under a Creative Commons Attribution 4.0
  International License (CC BY 4.0).

**Scenario design and adaptation for CyberRangeCZ Platform by:** [your name / team]
