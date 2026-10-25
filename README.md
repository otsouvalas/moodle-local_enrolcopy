# Copy enrolment methods (local_enrolcopy)

Copies **enrolment method configurations** from one course to another: a self enrolment method, a cohort
sync, a profile-field rule, or any other enrolment plugin instance. It copies the rule itself, **not the
users** enrolled through it. Once copied, the target course computes its own members from that rule.

Course backup and restore does not always carry automatic enrolment methods over to a new course. This
tool lets you set up the rules once and reuse them across courses instead of re-creating each one by hand.

## Requirements

Moodle 4.5 to 5.2.

## Installation

1. Copy the plugin folder to `<moodle>/local/enrolcopy` (so that `local/enrolcopy/version.php` exists),
   or install the ZIP from *Site administration > Plugins > Install plugins*.
2. Visit *Site administration > Notifications* to finish the installation.

## Usage

Go to *Site administration > Courses > Copy enrolment methods*
(`/local/enrolcopy/index.php`).

1. **Courses.** Pick the source course (copy from) and the target course (copy to).
2. **Methods.** All enrolment methods of the source course are listed, ticked by default. Methods that
   cannot be added to the target course are shown greyed out: for example the target already has a manual
   enrolment or guest access instance, which allow one instance per course.
3. **Results.** The number of methods copied, plus what was skipped and why.

Each new instance is created through the enrolment plugin's own `add_instance()`, so the plugin's own
checks apply. The source course is not changed.

### Caveat: course-specific values

Some enrolment plugins store ids that only make sense inside one course in their settings, for example
the default group of a self enrolment method. These values are copied as they are. Check such methods in
the target course after copying.

## Optional: cloning Field rules enrolment per group

If the *Field rules enrolment* plugin (`enrol_fieldrules`) is installed, choose the **same course** as
source and target. Step 2 then offers a *Clone Field rules enrolment for other groups* section: for every
ticked Field rules method and every group value entered there, a new instance is created with the same
level and role, only the group changes. Rules that already exist (same level and group) are skipped.
Field rules methods are also listed per level, with a *select all/none* control for each level.

Without `enrol_fieldrules` this section never appears and the plugin works with any enrolment plugin.

## Permissions

`local/enrolcopy:use` (system context, managers by default). The user also needs the permission to add
the given enrolment method in the target course (for example `enrol/self:config`).

## Privacy

The plugin stores no personal data. It implements the Privacy API null provider.

## Licence

GNU GPL v3 or later. Copyright 2026 Odysseas Tsouvalas (SpinWorks).
