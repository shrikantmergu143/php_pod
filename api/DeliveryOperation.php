<?php
include( "config.php");
include_once("RequestQuery.php");

$data = json_decode(file_get_contents("php://input"));
$create_at = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second

function deliveryLine($data){
    $state = true;
    if(empty($data->item_list) || !isset($data->item_list)){
        $state = false;
    }else{
        foreach ($data->item_list as $item) {
            // Validate each field in $item before adding to the query
            if (
                // !isset($item->line_no) ||
                !isset($item->item_code) ||
                !isset($item->product_name) ||
                !isset($item->rate) ||
                !isset($item->quantity) ||
                !isset($item->uom) ||
                !isset($item->item_type) ||
                !isset($item->warranty) ||
                // empty($item->line_no) ||
                empty($item->item_code) ||
                empty($item->product_name) ||
                empty($item->rate) ||
                empty($item->quantity) ||
                empty($item->uom) ||
                empty($item->item_type) ||
                empty($item->warranty)
            ) {
                $state = false;
            }
        }
    }
    return $state;
}

function validateData($data){
    if (
        isset($data->dcno) &&
        isset($data->cust_code) &&
        isset($data->transport_no) &&
        isset($data->cust_sub_unit_code) &&
        isset($data->item_list) &&
        !empty($data->dcno) &&
        !empty($data->cust_code) &&
        !empty($data->transport_no) &&
        !empty($data->item_list) &&
        !empty($data->cust_sub_unit_code)
    ) {
        if(deliveryLine($data)){
            return true;
        }else{
            return false;
        }
    } else {
        return false;
    }
}
global $delivery_main, $transporter, $customer;

$delivery_main = "date_added.timestamp.cust_code.cust_sub_unit_code.transport_no.transport_type.no.dcno.pono.manual_dc.transport_amt.remarks";
$transporter = "code.name.email.address.city.state.phone1.phone2.fax.GST_no.remarks.entered_by";
$customer = "code.name.email.address.city.state.phone1.phone2.fax.GST_no.remarks.entered_by";
$customer_contact = "id.code.srno.contactname.contactdesignation.contactmobile.contactemail.remarks.timestamp.unit_location.unit_address.user_name.entered_by.type.company_name.state.city.pincode";
$delivery_line = "srno.line_no.dcno.item_code.item_type.product_name.warranty.rate.quantity.uom.total_amount";

function getFieldsQuery($fieldsString, $type) {
    $delivery_main = explode('.', $fieldsString); // Convert string to an array
    $convertedFields = [];
    
    foreach ($delivery_main as $field) {
        $convertedFields[] = "$type.$field AS ${type}_$field";
    }
    
    $result = implode(', ', $convertedFields);
    return $result;
}

function createDeliveryArray($row, $keysString, $type_module) {
    $keysArray = explode('.', $keysString); // Convert string to an array

    $deliveryArray = array();
    foreach ($keysArray as $key) {
        if (isset($row[$type_module.'_' . $key])) {
            $deliveryArray[$key] = $row[$type_module.'_' . $key];
        }
    }
    return $deliveryArray;
}

if (isset($data->request_type)) {
    switch ($data->request_type) {
        case "ADD_DELIVERY":{
            if (validateData($data)) {
                $series = "";
                $date_added = $create_at;
                $timestamp = $create_at;
                $cust_code = "";
                $cust_sub_unit_code = "";
                $transport_no = "";
                $dcno = "";
                $pono = "";
                $manual_dc = "";
                $transport_amt = 0;
                $transport_type = "";
                $remarks = "";
                $item_list = array();
                if(isset($data->series)){
                    $series = $data->series;
                }
                if(isset($data->cust_code)){
                    $cust_code = $data->cust_code;
                }
                if(isset($data->cust_sub_unit_code)){
                    $cust_sub_unit_code = $data->cust_sub_unit_code;
                }
                if(isset($data->transport_no)){
                    $transport_no = $data->transport_no;
                }
                if(isset($data->transport_type)){
                    $transport_type = $data->transport_type;
                }
                if(isset($data->pono)){
                    $pono = $data->pono;
                }
                if(isset($data->dcno)){
                    $dcno = $data->dcno;
                }
                if(isset($data->manual_dc)){
                    $manual_dc = $data->manual_dc;
                }
                if(isset($data->transport_amt)){
                    $transport_amt = $data->transport_amt;
                }
                if(isset($data->remarks)){
                    $remarks = $data->remarks;
                }
                if(isset($data->item_list)){
                    $item_list = (array) $data->item_list;
                }
                $check_sql = "SELECT COUNT(*) as count FROM `delivery_main` WHERE dcno = '$dcno'";
                $resultCheck = $conn->query($check_sql);
                if ($resultCheck && $resultCheck->num_rows > 0) {
                    $row = $resultCheck->fetch_assoc();
                    $count = $row['count'];
                
                    if ($count > 0) {
                        Failure("Error: Delivery with the same dcno already exists");
                    } else {
                        $amount=0;
                        foreach ($data->item_list as $item) {
                            $total_amount = $item->quantity * $item->rate;
                            $amount = $amount + $total_amount;
                        }
                        $sql_last_delivery = "SELECT manual_dc FROM `delivery_main` WHERE cust_code = '$cust_code' ORDER BY timestamp DESC LIMIT 1";
                        $result_last_delivery = $conn->query($sql_last_delivery);

                        if ($result_last_delivery && $result_last_delivery->num_rows > 0) {
                            $row_last_delivery = $result_last_delivery->fetch_assoc();
                            $last_manual_dc = $row_last_delivery['manual_dc'];

                            // Extract the numeric part from the manual_dc
                            preg_match('/(\d+)$/', $last_manual_dc, $matches);
                            $numeric_part = isset($matches[1]) ? intval($matches[1]) : 0;

                            // Increment the numeric part by 1
                            $new_numeric_part = str_pad($numeric_part + 1, 6, '0', STR_PAD_LEFT);

                            // Generate the new manual_dc by concatenating customer name and incremented numeric part
                            $manual_dc = substr($last_manual_dc, 0, -strlen($new_numeric_part)) . $new_numeric_part;
                        } else {
                            // If no previous deliveries found for the customer, set manual_dc to default value
                            $manual_dc = $manual_dc . '000001';
                        }

                        // print_r($row_last_delivery);
                        $sql = "INSERT INTO `delivery_main` ( `date_added`, `timestamp`, `cust_code`, `cust_sub_unit_code`, `transport_no`, `transport_type`, `dcno`, `manual_dc`, `transport_amt`, `remarks`, `pono` ) VALUES
                        ( '$date_added', '$date_added', '$cust_code', '$cust_sub_unit_code', '$transport_no', '$transport_type', '$dcno', '$manual_dc', $amount, '$remarks', '$pono' ) ";
                        // echo $sql;
                        $result = $conn->query($sql);
                        if ( $result) {
                            $last_id = $conn->insert_id;
                            $values = [];
                            
                            $sql1 = "INSERT INTO delivery_line (`line_no`, `dcno`, `item_code`, `product_name`, `rate`, `quantity`, `uom`, `total_amount`, `pack_size`, `item_type`, `warranty`) VALUES";
                            foreach ($data->item_list as $item) {
                                $total_amount = $item->quantity * $item->rate;
                                $pack_size = 0;
                                if(isset($item->pack_size)){
                                    $pack_size = $item->pack_size;
                                }
                                $values[] = "('$item->line_no', $last_id, '$item->item_code', '$item->product_name', $item->rate, $item->quantity, '$item->uom', $total_amount, $pack_size, '$item->item_type', '$item->warranty' )";
                            }
                            $sql1 .= implode(",", $values);
                            if ($conn->query($sql1) === TRUE) {
                                Success("Delivery entry added successfully");
                            } else {
                                Failure("Error: " . $sql1 . "<br>" . $conn->error);
                            }
                        } else {
                            Failure("Error: " . $sql1 . "<br>" . $conn->error);
                        }
                    }
                } else {
                    Failure("Error checking item_code existence");
                }
            }else{
                Failure("Invalid data. Required fields are missing or empty.");
            }
        }
        break;
        case "GET_DELIVERY":{
            $recordsPerPage = 10;
            $pagination = true; // Default pagination is true
            if (isset($data->records_per_page)) {
                $recordsPerPage = $data->records_per_page;
            }
            $page = isset($data->page) && is_numeric($data->page) ? $data->page : 1;
            $offset = ($page - 1) * $recordsPerPage;
            if (isset($data->pagination) && $data->pagination === false) {
                $pagination = false;
            }
            $totalRecords = 0;
            if ($pagination) {
                $sqlCount = "SELECT 
                                COUNT(*) AS total_records
                            FROM 
                                pod.delivery_main";
                $resultCount = $conn->query($sqlCount);
                $totalRecords = $resultCount->fetch_assoc()['total_records'];
            }
            $delivery_fields = getFieldsQuery($delivery_main, 'delivery_main');
            $transporter_fields = getFieldsQuery($transporter, 'transporter');
            $customer_fields = getFieldsQuery($customer, 'customer');
            $customer_contact_fields = getFieldsQuery($customer_contact, 'customer_contact');

            $sql = "SELECT 
                        $delivery_fields, 
                        $transporter_fields, 
                        $customer_fields,
                        $customer_contact_fields
                    FROM 
            pod.delivery_main 
            INNER JOIN 
                pod.transporter ON delivery_main.transport_no = transporter.code
            INNER JOIN 
                    pod.customer_contact ON delivery_main.cust_sub_unit_code = customer_contact.id 
            INNER JOIN 
                pod.customer ON delivery_main.cust_code = customer.code";
            if ($pagination) {
                $sql .= " ORDER BY delivery_main.timestamp DESC LIMIT $offset, $recordsPerPage";
            } else {
                $sql .= " ORDER BY delivery_main.timestamp DESC";
            }
            $result = $conn->query($sql);
            $response = array(
                "total_records" => $totalRecords,
                "current_page" => $page,
                "records_per_page" => $recordsPerPage,
                "data" => array()
            );

            if ($result) {
                // $response = array(); // Initialize an array to store the result
                while ($row = $result->fetch_assoc()) {
                    $response["data"][] = array(
                        
                        "delivery" => createDeliveryArray($row, $delivery_main, 'delivery_main'),
                        "transporter" => createDeliveryArray($row, $transporter, 'transporter'),
                        "customer" => createDeliveryArray($row, $customer, 'customer'),
                        "customer_contact" => createDeliveryArray($row, $customer_contact, 'customer_contact')
                    );
                }
                // Send response as JSON
                echo json_encode($response);
            } else {
            // Handle query execution error
            $error = array("error" => "Error executing query: " . $conn->error);
            echo json_encode($error);
            }
        }
        break;
        case "GET_DELIVERY_DETAILS":{
            if(isset($data->delivery_code)){
                $delivery_fields = getFieldsQuery($delivery_main, 'delivery_main');
                $transporter_fields = getFieldsQuery($transporter, 'transporter');
                $customer_contact_fields = getFieldsQuery($customer_contact, 'customer_contact');
                $customer_fields = getFieldsQuery($customer, 'customer');
                $delivery_line_fields = getFieldsQuery($delivery_line, 'delivery_line');

                $sql = "SELECT 
                            $delivery_fields, 
                            $transporter_fields, 
                            $customer_fields,
                            $customer_contact_fields
                        FROM 
                            pod.delivery_main 
                        INNER JOIN 
                            pod.transporter ON delivery_main.transport_no = transporter.code 
                        INNER JOIN 
                            pod.customer ON delivery_main.cust_code = customer.code
                        INNER JOIN 
                            pod.customer_contact ON delivery_main.cust_sub_unit_code = customer_contact.id 
                        WHERE 
                            delivery_main.no = $data->delivery_code";
                
                $result = $conn->query($sql);
                $response = array(
                    "data" => null
                );
                
                // Process the query results
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    // Combine the data into a single array
                    $combinedData = array(
                        "delivery" => createDeliveryArray($row, $delivery_main, 'delivery_main'),
                        "transporter" => createDeliveryArray($row, $transporter, 'transporter'),
                        "customer" => createDeliveryArray($row, $customer, 'customer'),
                        "customer_contact" => createDeliveryArray($row, $customer_contact, 'customer_contact')
                    );
                    $response["data"] = $combinedData;
                } else {
                    // Handle no data found or query execution error
                    $response["error"] = "No data found or Error: " . $conn->error;
                }
                // Execute the second query
                $sql_delivery_line = "SELECT * FROM pod.delivery_line WHERE dcno = $data->delivery_code";
                $result_delivery_line = $conn->query($sql_delivery_line);

                // Process the second query results
                if ($result_delivery_line) {
                    $data_delivery_line = array();

                    // Fetch associative array for the second query
                    while ($row = $result_delivery_line->fetch_assoc()) {
                        $data_delivery_line[] = $row;
                    }

                    // Add the results of the second query to the response
                    $response['data']["delivery_line"] = $data_delivery_line;
                } else {
                    // Handle query execution error for the second query
                    $response['data']["error_delivery_line"] = "Error: " . $sql_delivery_line . "<br>" . $conn->error;
                }
                echo json_encode($response);
            }
        }
        break;
        case "GET_MAX_DELIVERY_ENTRY":{
            $sql = "SELECT MAX(no) AS dcno FROM pod.delivery_main";
            $result = $conn->query($sql);
            if ($result) {
                $row = $result->fetch_assoc();
                http_response_code(200);
                echo json_encode(array('data' => $row));
            } else {
                $error = array("error" => "Error executing query: " . $conn->error);
                http_response_code(400);
                echo json_encode($error);
            }
        }
        break;
        case "UPDATE_DELIVERY":{
            $no = isset($data->no) ? $conn->real_escape_string($data->no) : '';
$custCode = isset($data->updatedDetailsMain->cust_code) ? $conn->real_escape_string($data->updatedDetailsMain->cust_code) : '';
$updatedDetailsMain = isset($data->updatedDetailsMain) ? $data->updatedDetailsMain : (object) array();
$updatedDetailsLine = isset($data->updated_details_line) ? $data->updated_details_line : [];

if (!empty($no) && !empty($updatedDetailsMain) && !empty($updatedDetailsLine)) {
    $data_query = [];
    $updateQueryMain = "UPDATE delivery_main SET ";
    foreach ($updatedDetailsMain as $key => $value) {
        if ($key !== 'no') {
            $updateQueryMain .= "`$key` = '" . $conn->real_escape_string($value) . "', ";
        }
    }
    $updateQueryMain = rtrim($updateQueryMain, ', ');
    $updateQueryMain .= " WHERE `no` = '$no'";
    $data_query[] = $updateQueryMain;

    foreach ($updatedDetailsLine as $item) {
        if (isset($item->srno) && !empty($item->srno)) {
            $updateQueryLine = "UPDATE delivery_line SET ";
            foreach ($item as $key => $value) {
                if ($key !== 'srno') {
                    $escaped_value = $conn->real_escape_string($value);
                    $updateQueryLine .= "`$key` = '$escaped_value', ";
                }
            }
            $updateQueryLine = rtrim($updateQueryLine, ', ');
            $updateQueryLine .= " WHERE `srno` = '{$conn->real_escape_string($item->srno)}' AND `dcno` = '$no'";
            $data_query[] = $updateQueryLine;
        } else {
            // Check if all required properties exist before inserting
            if (isset($item->line_no, $item->item, $item->product_name, $item->rate, $item->quantity, $item->uom, $item->item_type, $item->warranty)) {
                $total_amount = $item->quantity * $item->rate;
                $pack_size = isset($item->pack_size) ? $item->pack_size : 0;
                $escaped_line_no = $conn->real_escape_string($item->line_no);
                $escaped_item = $conn->real_escape_string($item->item_code);
                $escaped_product_name = $conn->real_escape_string($item->product_name);
                $escaped_rate = $conn->real_escape_string($item->rate);
                $escaped_quantity = $conn->real_escape_string($item->quantity);
                $escaped_uom = $conn->real_escape_string($item->uom);
                $escaped_item_type = $conn->real_escape_string($item->item_type);
                $escaped_warranty = $conn->real_escape_string($item->warranty);
    
                $sql1 = "INSERT INTO delivery_line (`line_no`, `dcno`, `item_code`, `product_name`, `rate`, `quantity`, `uom`, `total_amount`, `pack_size`, `item_type`, `warranty`) VALUES ";
                $insertQueryLine = $sql1 . "('$escaped_line_no', '$no', '$escaped_item', '$escaped_product_name', '$escaped_rate', '$escaped_quantity', '$escaped_uom', '$total_amount', '$pack_size', '$escaped_item_type', '$escaped_warranty')";
                $data_query[] = $insertQueryLine;
            } else {
                // Log or handle the case when required properties are missing
            }
        }
    }
    
    $queryString = implode("; ", $data_query);
    // echo json_encode(array("error" => $queryString));

    if ($conn->multi_query($queryString) === TRUE) {
        echo json_encode(array("message" => "Queries executed successfully"));
    } else {
        echo json_encode(array("error" => "Error executing queries: " . $conn->error));
    }
} else {
    echo json_encode(array("error" => "No or incomplete data provided"));
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