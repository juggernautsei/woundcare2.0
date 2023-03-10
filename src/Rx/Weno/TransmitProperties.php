<?php

/**
 * TransmitProperties class.
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @author  Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2016-2017 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Rx\Weno;

use OpenEMR\Common\Crypto\CryptoGen;

class TransmitProperties
{
    private $payload;
    private $patient;
    private $provider_email;
    private $provider_pass;
    private $locid;
    private $vitals;
    private $subscriber;
    private $ncpdp;
    private $cryptoGen;

    /**
     * AdminProperties constructor.
     */
    public function __construct()
    {
             $this->cryptoGen = new CryptoGen();
                 $this->ncpdp = $this->getPharmacy();
                $this->vitals = $this->getVitals();
               $this->patient = $this->getPatientInfo(); //validate all info is in the patients chart
        $this->provider_email = $this->getProviderEmail();
         $this->provider_pass = $this->getProviderPassword();
                 $this->locid = $this->getFacilityInfo();
               $this->payload = $this->createJsonObject();
            $this->subscriber = $this->getSubscriber();
    }

    /**
     * @return false|string
     */
    public function createJsonObject()
    {
        //Check if a patient chart is open. If not return void
        if (empty($_SESSION['pid'])) {
            return '';
        }
        //default is testing mode
        $testing = isset($GLOBALS['weno_rx_enable_test']);
        if ($testing) {
            $mode = 'Y';
        } else {
            $mode = 'N';
        }
        if (is_array($this->patient)) {
            $gender = $this->patient['sex'] ?? null;
        }
        if (is_array($this->vitals)) {
            $heightDate = explode(" ", $this->vitals['date']) ?? null;
        }
        if (is_array($this->patient)) {
            $phoneprimary = preg_replace('/\D+/', '', $this->patient['phone_home']) ?? null;
        }

        //create json array
        $wenObj = [];
        $wenObj['UserEmail'] = $this->provider_email;
        $wenObj['MD5Password'] = md5($this->provider_pass);
        $wenObj['LocationID'] = $this->locid['weno_id'];
        $wenObj['TestPatient'] = $mode;
        $wenObj['PatientType'] = 'Human';
        $wenObj['OrgPatientID'] = $this->patient['pid'];
        $wenObj['LastName'] = $this->patient['lname'];

        $wenObj['FirstName'] = $this->patient['fname'];
        $wenObj['Gender'] = $gender[0];
        $wenObj['DateOfBirth'] = $this->patient['dob'];
        $wenObj['AddressLine1'] = $this->patient['street'];
        $wenObj['City'] = $this->patient['city'];
        $wenObj['State'] = $this->patient['state'];
        $wenObj['PostalCode'] = $this->patient['postal_code'];
        $wenObj['CountryCode'] = "US";
        $wenObj['PrimaryPhone'] = $phoneprimary;
        $wenObj['SupportsSMS'] = 'Y';

        $wenObj['PatientHeight'] = substr($this->vitals['height'], 0, -3);
        $wenObj['PatientWeight'] = substr($this->vitals['weight'], 0, -3);
        $wenObj['HeightWeightObservationDate'] = $heightDate[0];
        $wenObj["ResponsiblePartySameAsPatient"] = 'Y';
        $wenObj['PatientLocation'] = "Home";
        return json_encode($wenObj);

    }

    /**
     * @return mixed
     */
    public function getProviderEmail(): string
    {
        //TODO: need to determine how to figure out how is the admin
        $provider_info = sqlQuery("select email from users where id = ? ", [5]);
        if (empty($provider_info)) {
            echo xlt('Provider email address is missing. Go to address book to add providers email address');
            exit;
        } else {
            return $provider_info['email'];
        }
    }

    public function getFacilityInfo(): mixed
    {
        $locid = sqlQuery("select name, street, city, state, postal_code, phone, fax, weno_id from facility
                                                                   where id = ?", [$_SESSION['facilityId'] ?? null]);

        if (empty($locid['weno_id'])) {
            //if not in an encounter then get the first facility location id as default
            $default_facility = sqlQuery("select name, street, city, state, postal_code, phone, fax, weno_id from
                                                                       facility order by id asc limit 1");

            if (empty($default_facility['weno_id'])) {
                echo xlt('Facility ID is missing');
                exit;
            } else {
                return $default_facility;
            }
        }
        return $locid;
    }

    private function getPatientInfo()
    {
        //get patient data if in an encounter
        //Since the transmitproperties is called in the logproperties
        //need to check to see if in an encounter or not. Patient data is not required to view the Weno log
        //removing the call for an encounter. Just check if in a patient chart. If in a chart check patient info
        if (!empty($_SESSION['pid'])) {
            echo "<title>" . xlt('Missing Data') . "!</title>";
            $missing = 0;
            $pharmacy = $this->getPharmacy();
            if (empty($pharmacy)) {
                echo xlt('Select a pharmacy for patient') . "<br>";
                ++$missing;
            }
            $vitals = self::getVitals();
            if (is_array($vitals)) {
                if (empty($vitals['height'])) {
                    echo xlt('Vitals - Height missing ') . "<br>";
                    ++$missing;
                }
                if (empty($vitals['weight'])) {
                    echo xlt('Vitals - Weight missing ') . "<br>";
                    ++$missing;
                }
            } else {
                echo xlt('Height and Weight are missing ') . "<br>";
                ++$missing;
            }
            $patient = sqlQuery("select title, fname, lname, mname, street, state, city, email,
       phone_home, postal_code, dob, sex, pid from patient_data where pid=?", [$_SESSION['pid']]);
            if (empty($patient['fname'])) {
                echo xlt("First Name Missing") . "<br>";
                ++$missing;
            }
            if (empty($patient['lname'])) {
                echo xlt("Last Name Missing") . "<br>";
                ++$missing;
            }
            if (empty($patient['dob'])) {
                echo xlt("Date of Birth Missing") . "<br>";
                ++$missing;
            }
            if (empty($patient['sex'])) {
                echo xlt("Gender Missing") . "<br>";
                ++$missing;
            }
            if (empty($patient['postal_code'])) {
                echo xlt("Zip Code is Missing") . "<br>";
                ++$missing;
            }
            if (empty($patient['street'])) {
                echo xlt("Street Address is incomplete Missing") . "<br>";
                ++$missing;
            }
            if (empty($patient['phone_home'])) {
                echo xlt("Home Phone number is incomplete Missing") . "<br>";
                ++$missing;
            }
            if ($missing > 0) {
                die('Pleasae add the missing data and try again');
            }
            return $patient;
        }
    }

    /**
     * @return string
     * New Rx
     */
    public function cipherPayload(): string
    {
        $cipher = $this->wenoMethod(); // AES 256 CBC cipher
        $enc_key = $this->cryptoGen->decryptStandard($GLOBALS['weno_encryption_key']);
        if ($enc_key) {
            $key = substr(hash('sha256', $enc_key, true), 0, 32);
            $iv = $this->wenoChr();
            return base64_encode(openssl_encrypt($this->payload, $cipher, $key, OPENSSL_RAW_DATA, $iv));
        } else {
            return "error";
        }
    }

    /**
     * @return mixed
     */
    public function getProviderPassword()
    {
        $uid = 5; //$_SESSION['authUserID']; //over written during testing and need to reconfigure for production
        $sql = "select setting_value from user_settings where setting_user = ? and setting_label = 'weno_provider_password'";
        $prov_pass = sqlQuery($sql, [$uid]);
        if (!empty($prov_pass['setting_value'])) {
            return $prov_pass['setting_value'];
        } else {
            echo xlt('Provider Password is missing');
            die;
        }
    }

    /**
     * @return mixed
     */
    private function getVitals()
    {
        $vitals = sqlQuery("select date, height, weight from form_vitals where pid = ? ORDER BY id DESC",
            [$_SESSION["pid"] ?? null]);
        return $vitals;
    }

    private function getSubscriber()
    {
        $sql = sqlQuery("select subscriber_relationship from insurance_data where pid = ? and type = 'primary'",
            [$_SESSION['pid'] ?? null]);
        return $sql['subscriber_relationship'] ?? null;
    }

    /**
     * @return mixed
     */
    public function getPharmacy()
    {
        $sql = "SELECT p.ncpdp FROM pharmacies p JOIN patient_data pd ON p.id = pd.pharmacy_id WHERE pd.pid = ? ";
        $give = sqlQuery($sql, [$_SESSION['pid'] ?? null]);
        return $give['ncpdp'] ?? null;
    }

    public function wenoChr()
    {
        return
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0) .
            chr(0x0);
    }

    public function wenoMethod(): string
    {
        return "aes-256-cbc";
    }
}
