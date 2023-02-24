<?php

/*
 *  @package OpenEMR
 *  @link    http://www.open-emr.org
 *  @author  Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @copyright Copyright (c) 2023 Sherwin Gaddis <sherwingaddis@gmail.com>
 *
 */

namespace OpenEMR\Rx\Weno;

class DownloadWenoPharmacies
{
    public function RetrieveDataFile($url, $storelocation)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: ASP.NET_SessionId=fy5zpkkeptxxf2cviwkncie2'
            ),
        ));
        $status = curl_getinfo($curl);
        $response = curl_exec($curl);

        curl_close($curl);

        $filename = "wenoPharmacyDirctory.zip";
        $directory = fopen($storelocation.$filename, 'w');
        fwrite($directory, $response);
        fclose($directory);
        $unzip = new \ZipArchive();
        $res = $unzip->open($storelocation.$filename);
        if ($res === true) {
            $unzip->extractTo($storelocation);
            $unzip->close();
            return 'complete';
        }
        unlink($storelocation.$filename);
    }
}
