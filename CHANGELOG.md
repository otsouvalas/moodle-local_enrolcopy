# Changelog

## 1.3.0 (2026102500) — 2026-10-25
Preparation for the Moodle plugins directory.
- Courses are chosen with Moodle's course search field instead of a list of every course on the site.
- Moodle forms throughout; no inline JavaScript or styles. The per-level "select all" is now Moodle's
  standard *select all/none* control.
- Only methods that belong to the source course can be copied (the submitted ids are checked).
- Managers who are not site admins can now open the page: `local/enrolcopy:use` alone is enough (the
  admin page used to be registered for site admins only).
- Same-course group cloning now also checks that the user may add the method to the course.
- The front page (site course) cannot be used as source or target.
- An error thrown by another enrolment plugin no longer stops the remaining copies.
- Group values for Field rules cloning are entered in one search/tag field (menu values are offered,
  free text is accepted when the group field is not a menu).
- The group field comes only from the `enrol_fieldrules` setting (no hard-coded default).
- Requires Moodle 4.5; supported 4.5 to 5.2.
- PHPUnit tests, GPL headers and docblocks, Moodle coding style clean; README and CHANGELOG in English.
- Capability risk now also includes RISK_CONFIG.

## 1.2.1 (2026100800) — 2026-10-08
- Privacy API: null provider (the plugin stores no personal data of its own).

## 1.2.0 (2026091702) — 2026-09-17
- Field rules enrolment methods are grouped by level (`customchar1`) in step 2, with a "select all" per
  level.

## 1.1.0 (2026091701) — 2026-09-17
- The source and the target course can be the same course.
- Same-course cloning of Field rules enrolment methods for other groups: every ticked method times every
  chosen group value becomes a new instance with the same level and role, only the group (`customchar2`)
  changes. Existing level+group rules are skipped.

## 1.0.0 (2026071001) — 2026-07-10
- First version: copy enrolment methods between courses.
