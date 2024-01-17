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
        isset($data->email) &&
        isset($data->name) &&
        isset($data->phone1) &&
        !empty($data->phone1) &&
        !empty($data->email) &&
        !empty($data->name)
    ) {
        return true;
    } else {
        return false;
    }
}

if (isset($data->request_type)) {
    switch ($data->request_type) {
        
        case "ADD_CUSTOMER":{
            if (validateData($data)) {
                $email = "";
                $name = "";
                $local_name = "";
                $address = "";
                $city = "";
                $state = "";
                $pincode = "";
                $phone1 = "";
                $phone2 = "";
                $fax = "";
                $tally_name = "";
                $GST_no = "";
                $remarks = "";
                $entered_by = "System";
                $status = "Active";
                if(isset($data->tally_name)){
                    $tally_name = $data->tally_name;
                }
                if(isset($data->email)){
                    $email = $data->email;
                }
                if(isset($data->name)){
                    $name = $data->name;
                }
                if(isset($data->address)){
                    $address = $data->address;
                }
                if(isset($data->city)){
                    $city = $data->city;
                }
                if(isset($data->state)){
                    $state = $data->state;
                }
                if(isset($data->pincode)){
                    $pincode = $data->pincode;
                }
                if(isset($data->phone1)){
                    $phone1 = $data->phone1;
                }
                if(isset($data->phone2)){
                    $phone2 = $data->phone2;
                }
                if(isset($data->fax)){
                    $fax = $data->fax;
                }
                if(isset($data->GST_no)){
                    $GST_no = $data->GST_no;
                }
                if(isset($data->remarks)){
                    $remarks = $data->remarks;
                }
                if(isset($data->entered_by)){
                    $entered_by = $data->entered_by;
                }

                $sql = "INSERT INTO customer ( `name`, `address`, `city`, `state`, `pincode`, `phone1`, `phone2`, `email`, `fax`, `date_created`, `tally_name`, `GST_no`, `remarks`, `status`, `entered_by`) 
                VALUES ('$name', '$address', '$city', '$state', '$pincode', '$phone1', '$phone2', '$email', '$fax', '$currentDateTime', '$tally_name', '$GST_no', '$remarks', '$status', '$entered_by')";
                
                if ($conn->query($sql) === TRUE) {
                    Success("Customer added successfully");
                } else {
                    Failure("Error: " . $sql . "<br>" . $conn->error);
                }
        
            } else {
                Failure("Invalid data. Required fields are missing or empty.");
            }
        }
        break;
        case "GET_CUSTOMER":{
            $recordsPerPage = 10;
            $pagination = true; // Default pagination is true
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
            $totalRecords = 0;
            if ($pagination) {
                $sqlCount = "SELECT COUNT(*) AS total_records FROM customer WHERE 
                            `status` = '$status' AND
                            (`code` LIKE '%$search%' OR 
                            `name` LIKE '%$search%' OR 
                            `email` LIKE '%$search%')";
                $resultCount = $conn->query($sqlCount);
                $totalRecords = $resultCount->fetch_assoc()['total_records'];

            }
            $sql = "SELECT `code`, `name`, `address`, `city`, `state`, `pincode`, `phone1`, `phone2`, `email`, `fax`, `date_created`, `tally_name`, `GST_no`, `remarks`, `status`, `entered_by` FROM customer 
                    WHERE 
                    `status` = '$status' AND
                    (`code` LIKE '%$search%' OR 
                    `name` LIKE '%$search%' OR 
                    `email` LIKE '%$search%' )";
            // Append pagination only if the pagination flag is true
            if ($pagination) {
                $sql .= " LIMIT $offset, $recordsPerPage";
            }


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
            echo json_encode($response);
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
        case "UPDATE_CUSTOMER":{
            $customerCode = isset($data->customer_code) ? $data->customer_code : '';
            $updatedDetails = isset($data->updated_details) ? $data->updated_details : array();
            if (!empty($customerCode) && !empty($updatedDetails)) {
                $updateQuery = "UPDATE customer SET ";
                foreach ($updatedDetails as $key => $value) {
                    if ($key !== 'code') {
                        $updateQuery .= "`$key` = '$value', ";
                    }
                }
                $updateQuery .= "`entered_by` = 'System', ";

                $updateQuery = rtrim($updateQuery, ', ');
                $updateQuery .= " WHERE `code` = '$customerCode'";
                if ($conn->query($updateQuery) === TRUE) {
                    // echo json_encode(array("message" => "Customer updated successfully"));
                    Success('Customer updated successfully');
                } else {
                    // echo json_encode(array("error" => "Error updating customer: " . $conn->error));
                    Failure("Error updating customer: " . $conn->error);

                }
            } else {
                // echo json_encode(array("error" => "Customer code or updated details missing"));
                Failure("Customer code or updated details missing");
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