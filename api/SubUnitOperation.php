<?php
include( "config.php");
include_once("RequestQuery.php");

$data = json_decode(file_get_contents("php://input"));
$currentDateTime = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second
// echo $currentDateTime;
$customerField = array(
    array(
        "name" => "code",
        "type" => "int",
        "length" => 11,
        "auto_increment" => true,
        "primary" => true,
    ),
    array(
        "name" => "name",
        "type" => "varchar",
        "length" => 100,
    ),
    array(
        "name" => "address",
        "type" => "varchar",
        "length" => 1000,
    ),
    array(
        "name" => "city",
        "type" => "varchar",
        "length" => 20,
    ),
    array(
        "name" => "state",
        "type" => "varchar",
        "length" => 100,
    ),
    array(
        "name" => "pincode",
        "type" => "varchar",
        "length" => 6,
    ),
    array(
        "name" => "phone1",
        "type" => "varchar",
        "length" => 15,
    ),
    array(
        "name" => "phone2",
        "type" => "varchar",
        "length" => 15,
    ),
    array(
        "name" => "email",
        "type" => "varchar",
        "length" => 40,
    ),
    array(
        "name" => "fax",
        "type" => "varchar",
        "length" => 15,
    ),
    array(
        "name" => "date_created",
        "type" => "date",
    ),
    array(
        "name" => "tally_name",
        "type" => "varchar",
        "length" => 1000,
    ),
    array(
        "name" => "GST_no",
        "type" => "varchar",
        "length" => 20,
    ),
    array(
        "name" => "remarks",
        "type" => "varchar",
        "length" => 1000,
    ),
    array(
        "name" => "status",
        "type" => "enum",
        "values" => array('Active', 'Inactive'),
    ),
    array(
        "name" => "entered_by",
        "type" => "varchar",
        "length" => 100,
    ),
);

function validateInput($data) {
    // Implement your validation logic here
    // Example: Trim whitespace, prevent SQL injection, etc.
    $validatedData = array_map('trim', $data);
    $validatedData = array_map(array($this->conn, 'real_escape_string'), $validatedData);
    return $validatedData;
}
function validateData($data){
    if (
        isset($data->code) &&
        isset($data->srno) &&
        isset($data->password) &&
        isset($data->user_name) &&
        isset($data->entered_by) &&
        !empty($data->entered_by) &&
        !empty($data->user_name) &&
        !empty($data->password) &&
        !empty($data->code) &&
        !empty($data->srno)
    ) {
        return true;
    } else {
        return false;
    }
}

if (isset($data->request_type)) {
    switch ($data->request_type) {
        
        case "ADD_SUB_UNIT":{
            if (validateData($data)) {
                $code = "";
                $srno = "";
                $contactname = "";
                $contactdesignation = "";
                $contactmobile = "";
                $contactemail = "";
                $unit_location = "";
                $unit_address = "";
                $user_name = "";
                $password = "";
                $entered_by = "";
                $remarks = "";
                $type = "";
                $timestamp = $currentDateTime;

                if(isset($data->code)){
                    $code = $data->code;
                }
                if(isset($data->contactname)){
                    $contactname = $data->contactname;
                }
                if(isset($data->srno)){
                    $srno = $data->srno;
                }
                if(isset($data->contactdesignation)){
                    $contactdesignation = $data->contactdesignation;
                }
                if(isset($data->contactmobile)){
                    $contactmobile = $data->contactmobile;
                }
                if(isset($data->contactemail)){
                    $contactemail = $data->contactemail;
                }
                if(isset($data->unit_location)){
                    $unit_location = $data->unit_location;
                }
                if(isset($data->unit_address)){
                    $unit_address = $data->unit_address;
                }
                if(isset($data->user_name)){
                    $user_name = $data->user_name;
                }
                if(isset($data->password)){
                    $password = $data->password;
                }
                if(isset($data->entered_by)){
                    $entered_by = $data->entered_by;
                }
                if(isset($data->remarks)){
                    $remarks = $data->remarks;
                }
                if(isset($data->type)){
                    $type = $data->type;
                }

                $sql = "INSERT INTO customer_contact ( `code`, `srno`, `contactname`, `contactdesignation`, `contactmobile`, `contactemail`, `remarks`, `unit_location`, `unit_address`, `user_name`, `password`, `entered_by`, `type`, `timestamp` ) 
                VALUES ('$code', '$srno', '$contactname', '$contactdesignation', '$contactmobile', '$contactemail', '$remarks', '$unit_location', '$unit_address', '$user_name', '$password', '$entered_by', '$type', '$timestamp' )";

                if ($conn->query($sql) === TRUE) {
                    Success("Subunit added successfully");
                } else {
                    Failure("Error: " . $sql . "<br>" . $conn->error);
                }
        
            } else {
                Failure("Invalid data. Required fields are missing or empty.");
            }
        }
        break;
        case "GET_SUB_UNIT":{
            $recordsPerPage = 10;
            $pagination = true; // Default pagination is true
            if(isset($data->code)){
                $code = $data->code;
                if (isset($data->records_per_page)) {
                    $recordsPerPage = $data->records_per_page;
                }
    
                if(isset($data->records_per_page)){
                    $recordsPerPage = $data->records_per_page;
                }
                $page = isset($data->page) && is_numeric($data->page) ? $data->page : 1;
                $offset = ($page - 1) * $recordsPerPage;
    
                $search = '';
                $status = 'Active';
                if(isset($data->search)){
                    $search = $data->search;
                }
                if(isset($data->status)){
                    $status = $data->status;
                }
                // Check if pagination flag is set to false
                if (isset($data->pagination) && $data->pagination === false) {
                    $pagination = false;
                }
                // $res1 = $conn->query("ALTER TABLE `customer_contact` MODIFY COLUMN `id` INT;");
                // echo $res1;
                $totalRecords = 0;
                if ($pagination) {
                    $sqlCount = "SELECT COUNT(*) AS total_records FROM `customer_contact` WHERE 
                                `code` = '$code' AND
                                (`contactmobile` LIKE '%$search%' OR 
                                `contactname` LIKE '%$search%' OR 
                                `contactemail` LIKE '%$search%')";
                    $resultCount = $conn->query($sqlCount);
                    $totalRecords = $resultCount->fetch_assoc()['total_records'];
    
                }
                $sql = "SELECT `id`, `code`, `srno`, `contactname`, `contactdesignation`, `contactmobile`, `contactemail`, `remarks`, `unit_location`, `unit_address`, `user_name`, `entered_by`, `type`, `timestamp` FROM `customer_contact` 
                        WHERE 
                        `code` = '$code' AND 
                        ( `contactmobile` LIKE '%$search%' OR 
                        `contactname` LIKE '%$search%' OR 
                        `contactemail` LIKE '%$search%')";
                // Append pagination only if the pagination flag is true
                if ($pagination) {
                    $sql .= " LIMIT $offset, $recordsPerPage";
                }
    
                //  echo $sql;
                $result = $conn->query($sql);
                $response = array(
                    "total_records" => $totalRecords,
                    "current_page" => $page,
                    "records_per_page" => $recordsPerPage,
                    "data" => array()
                );
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $response["data"][] = $row;
                    }
                }
                // echo json_encode($response);
                Success("Fetch customer details successfully", $response);
            }else{
                Failure("Customer is not given");
            }
        }
        break;
        case "GET_CUSTOMER_DETAILS":{
            if(isset($data->customer_code)){
                $customerCode = $data->customer_code;
                $sql = "SELECT `code`, `name`, `address`, `city`, `state`, `pincode`, `phone1`, `phone2`, `email`, `fax`, `date_created`, `tally_name`, `GST_no`, `remarks`, `status`  FROM customer WHERE `code` = '$customerCode' AND  `status` = 'Active' ";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $customerDetails = $result->fetch_assoc();
                    // echo json_encode(array("message" => "Fetch customer details successfully", 'data'=>$customerDetails));
                    // echo json_encode($customerDetails);
                    Success("Fetch customer details successfully", $customerDetails);
                } else {
                    Failure("Customer not found");
                }
            }
        }
        break;
        case "UPDATE_SUB_UNIT":{
            $id = isset($data->id) ? $data->id : '';
            $updatedDetails = isset($data->updated_details) ? $data->updated_details : array();
            if (!empty($id) && !empty($updatedDetails)) {
                $updateQuery = "UPDATE `customer_contact` SET ";
                foreach ($updatedDetails as $key => $value) {
                    if ($key !== 'code') {
                        $updateQuery .= "`$key` = '$value', ";
                    }
                }
                $updateQuery .= "`entered_by` = 'System', ";

                $updateQuery = rtrim($updateQuery, ', ');
                $updateQuery .= " WHERE `id` = '$id'";
                // echo $updateQuery;
                if ($conn->query($updateQuery) === TRUE) {
                    Success('Subunit updated successfully');
                } else {
                    Failure("Error updating customer: " . $conn->error);
                }
            } else {
                // echo json_encode(array("error" => "Customer code or updated details missing"));
                Failure("Subunit code or updated details missing");
            }
        }
        break;
        default:
            echo json_encode(array("error" => "Invalid request type"));
        break;
    }
} else {
    echo json_encode(array("error" => "No data received."));
}