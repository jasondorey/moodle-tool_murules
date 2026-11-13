# Rules (`tool_murules`)

Local Moodle admin tool for IF→THEN automation on site. Create rules that match users on conditions (quiz score, activity completion, cohort, enrolment, profile fields, and other Workplace-style general conditions) and allocate them into MuTMS programs.

**Component:** `tool_murules`  
**Requires:** Moodle 5.3 (`supported [503,503]`), `tool_muprog`  
**Install path:** `{dirroot}/public/admin/tool/murules` (Moodle 5.x public layout)

## Quick start

```bash
git clone https://github.com/jasondorey/moodle-tool_murules.git admin/tool/murules
```

Then visit **Site administration → Notifications** to install, or:

```bash
sudo -u www-data php admin/cli/upgrade.php --non-interactive
sudo -u www-data php admin/cli/purge_caches.php
```

Admin UI: **Site administration → Programs → Rules**.

## Features

- WHEN → THEN rule cards and Event → Actions → Review wizard
- Primary path: quiz score / activity completed → allocate to MuTMS program (manual source)
- Workplace General conditions G1–G12 (cohort, competency, course, enrolment, login, profile, …)
- Event observers + scheduled scan for negative / time-window conditions
- Match log with apply / skip / fail; global kill-switch

## License

GNU GPL v3 or later — see [LICENSE](LICENSE).
