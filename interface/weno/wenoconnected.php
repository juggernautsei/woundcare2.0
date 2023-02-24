<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
/*
 *  @package OpenEMR
 *  @link    http://www.open-emr.org
 *  @author  Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @copyright Copyright (c) 2020 Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Rx\Weno\WenoPharmaciesJson;
use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Rx\Weno\TransmitProperties;

//ensure user has proper access
if (!AclMain::aclCheckCore('patient', 'med')) {
    echo xlt('Pharmacy Import not authorized');
    exit;
}
$cryptoGen = new CryptoGen();
$transmitProperties = new TransmitProperties();
$localPharmacyJson = new WenoPharmaciesJson(
    $cryptoGen,
    $transmitProperties
);

//check if the background service is active and set intervals to once a day
//Weno has decided to not force the import of pharmacies since they are using the iframe
//and the pharmacy can be selected at the time of creating the prescription.
$value = $localPharmacyJson->checkBackgroundService();
if ($value == 'active' || $value == 'live') {
    $status = $localPharmacyJson->storePharmacyDataJson();
} else {
    error_log('Weno Background service not active');  //This should never happen
}

//Check if file downloaded and set count
$count = 0;
if (!$status) {
    ++$count;
    error_log("Pharmacy Database did not download first try");
    $status = $localPharmacyJson->storePharmacyDataJson();
    die;
}

if (!$status && $count > 0) {
    sleep(300); //wait 5 minutes and try to download the file again.
    $status = $localPharmacyJson->storePharmacyDataJson();
}

if (!$status) {
    error_log('Unable to download Pharmacy database today');
}

echo "Pharmacies Downloaded";

