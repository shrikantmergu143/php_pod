<?php
include( "config.php");
include("RequestQuery.php");

$data = json_decode(file_get_contents("php://input"));
$currentDateTime = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second
// echo $currentDateTime;

function validateData($data){
    if (
        isset($data->email) &&
        isset($data->name) &&
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
        
        case "ADD_TRANSPORTER":{
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
        
                $sql = "INSERT INTO transporter ( `name`, `address`, `city`, `state`, `pincode`, `phone1`, `phone2`, `email`, `fax`, `date_created`, `tally_name`, `GST_no`, `remarks`, `status`) 
                VALUES ('$name', '$address', '$city', '$state', '$pincode', '$phone1', '$phone2', '$email', '$fax', '$currentDateTime', '$tally_name', '$GST_no', '$remarks', '$status')";
                 if ($conn->query($sql) === TRUE) {
                    // Success("Customer added successfully");
                    http_response_code(200);
                    echo json_encode(array("message" => "Customer added successfully"));
                } else {
                    // Failure("Error: " . $sql . "<br>" . $conn->error);
                    http_response_code(400);
                    echo json_encode(array("error" => "Error: " . $sql . "<br>" . $conn->error));
                }
        
            } else {
                // Failure("Invalid data. Required fields are missing or empty.");
                http_response_code(400);
                echo json_encode(array("error" => "Invalid data. Required fields are missing or empty."));
            }
        }
        break;
        case "GET_TRANSPORTER":{
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
                $sqlCount = "SELECT COUNT(*) AS total_records FROM transporter WHERE 
                            `status` = '$status' AND
                            (`code` LIKE '%$search%' OR 
                            `name` LIKE '%$search%' OR 
                            `email` LIKE '%$search%')";
                $resultCount = $conn->query($sqlCount);
                $totalRecords = $resultCount->fetch_assoc()['total_records'];
            }
            $sql = "SELECT `code`, `name`, `address`, `city`, `state`, `pincode`, `phone1`, `phone2`, `email`, `fax`, `date_created`, `tally_name`, `GST_no`, `remarks`, `status` FROM transporter 
                    WHERE 
                    `status` = '$status' AND
                    (`code` LIKE '%$search%' OR 
                    `name` LIKE '%$search%' OR 
                    `email` LIKE '%$search%')";
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
        case "GET_TRANSPORTER_DETAILS":{
            if(isset($data->transporter_code)){
                $transporter_code = $data->transporter_code;
                $sql = "SELECT `code`, `name`, `address`, `city`, `state`, `pincode`, `phone1`, `phone2`, `email`, `fax`, `date_created`, `tally_name`, `GST_no`, `remarks`, `status`  FROM transporter WHERE `code` = '$transporter_code' AND  `status` = 'Active' ";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $customerDetails = $result->fetch_assoc();
                    // echo json_encode(array("message" => "Fetch transporter details successfully", 'data'=>$customerDetails));
                    // echo json_encode($customerDetails);
                    Success("Fetch transporter details successfully", $customerDetails);

                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Transporter not found"));
                }
            }
        }
        break;
        case "UPDATE_TRANSPORTER":{
            $transporter_code = isset($data->transporter_code) ? $data->transporter_code : '';
            $updatedDetails = isset($data->updated_details) ? $data->updated_details : array();
            if (!empty($transporter_code) && !empty($updatedDetails)) {
                $updateQuery = "UPDATE transporter SET ";
                foreach ($updatedDetails as $key => $value) {
                    if ($key !== 'code') {
                        $updateQuery .= "`$key` = '$value', ";
                    }
                }
                $updateQuery = rtrim($updateQuery, ', ');
                $updateQuery .= " WHERE `code` = '$transporter_code'";
                if ($conn->query($updateQuery) === TRUE) {
                    echo json_encode(array("message" => "Transporter updated successfully"));
                } else {
                    echo json_encode(array("error" => "Error updating transporter: " . $conn->error));
                }
            } else {
                echo json_encode(array("error" => "Transporter code or updated details missing"));
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