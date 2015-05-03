<?php

/**
 * Lightweight SOAP API Wrapper for ThinkMinistry (Ministry Platform)
 * @author Daniel Boorn - daniel.boorn@gmail.com
 * @copyright Daniel Boorn
 * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 */


//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(-1);


require_once 'thinkministry/api.php';


$testData = array(
    // api credentials
    'domain'      => '',
    'guId'        => '',
    'apiPassword' => '',
    // test user credentials
    'username'    => '',
    'password'    => '',
    'server'      => '',
);


$mp = \ThinkMinistry\API::forge($testData['domain'], $testData['guId'], $testData['apiPassword']);

/*
 * Uncomment below to output debug info
 */
//$mp->debug = true;

/*
 * How to dump all endpoints
 */
var_dump($mp->getEndpoints());


/*
 * How to get user information (alternative)
 */
$r = $mp->GetUserInfo(array('UserID' => '196'));
var_dump($r);

/*
 * How to authenticate user
 */
$user = $mp->AuthenticateUser(array(
    'UserName'   => $testData['username'],
    'Password'   => $testData['password'],
    'ServerName' => $testData['server'],
));
var_dump(!empty($user->UserID) ? "Valid User" : "User Not Found", $user);


/*
 * How to Add Record
 */
$r = $mp->AddRecord(array(
    'TableName'       => 'Table',
    'PrimaryKeyField' => 'PrimaryKey',
    'RequestString'   => http_build_query(array(
        // insert data
        'Column' => 'Value',
    )),
));
var_dump($r);

/*
 * How to Execute Stored Procedure
 */
$r = $this->ExecuteStoredProcedure(array(
    'StoredProcedureName' => 'api_Name_Of_Procedure',
    'RequestString'       => http_build_query(array(
        // optional criteria
        'Field' => 'Value',
    )),
));
var_dump($r);




