# About
This feature is for e prescriptions through the Weno Exchange service. This document is for those
that may need to debug or add enhancements to the feature.
## Directory Structure
From the OpenEMR root directory. These are the folders that contain all of the files for this feature

***
* /var/www/html/woundcare/interface/weno
* ├── facilities.php
* ├── indexrx.php
* ├── rxlogmanager.php
* ├── weno.js
* └── wenoconnected.php

* /var/www/html/woundcare/interface/super
* ├── edit_globals.js
* ├── edit_globals.php

* /var/www/html/woundcare/library/
* ├── weno_log_sync.php

* /var/www/html/woundcare/src/Rx/Weno
* ├── DownloadWenoPharmacies.php
* ├── FacilityProperties.php
* ├── LogDataInsert.php
* ├── LogImportBuild.php
* ├── LogProperties.php
* ├── TransmitProperties.php
* └── WenoPharmaciesJson.php

* /var/www/html/woundcare/templates/pharmacies
* ├── general_edit.html
* ├── general_import.html
* └── general_list.html

***
## Updating
It is strongly suggested to use any IDE to navigate these files and learn their relationship to one another.
The files are named according to their function in the feature. The TransmitProperties file is the main hub of
functions. Some of the files are hold-overs from past features.

