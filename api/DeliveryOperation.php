<?php
include( "config.php");

$data = json_decode(file_get_contents("php://input"));
$create_at = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second
// echo $email;

function validateData($data){
    if (
        isset($data->dcno) &&
        isset($data->cust_code) &&
        isset($data->transport_no) &&
        isset($data->cust_cont_srno) &&
        isset($data->item_list) &&
        !empty($data->dcno) &&
        !empty($data->cust_code) &&
        !empty($data->transport_no) &&
        !empty($data->item_list) &&
        !empty($data->cust_cont_srno)
    ) {
        return true;
    } else {
        return false;
    }
}
global $delivery_main, $transporter, $customer;

$delivery_main = "series.date_added.email.timestamp.cust_code.cust_cont_srno.transport_type.transport_no.driver.warehouse.no.dcno.manual_dc.transport_amt.tax_amt.total_amt.remarks.payment_status";
$transporter = "code.type.sub_type.name.email.address1.address2.address3.location_code.city.state.phone1.phone2.fax.user.invoice.GST_no.remarks.payment_type";
$customer = "code.type.sub_type.name.email.address1.address2.address3.location_code.city.state.phone1.phone2.fax.user.invoice.GST_no.remarks.payment_type";
$delivery_line = "srno.line_no.dcno.item.rate.quantity.quantity_volume.line_total.line_tax_rate.line_tax";

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
                $email = "";
                $timestamp = $create_at;
                $cust_code = "";
                $cust_cont_srno = "";
                $transport_type = "";
                $transport_no = "";
                $driver = "";
                $warehouse = "";
                $dcno = "";
                $manual_dc = "";
                $transport_amt = 0;
                $tax_amt = 0;
                $total_amt = 0;
                $remarks = "";
                $item_list = array();
                $payment_status = "Pending";
                if(isset($data->series)){
                    $series = $data->series;
                }
                if(isset($data->payment_status)){
                    $payment_status = $data->payment_status;
                }
                if(isset($data->cust_code)){
                    $cust_code = $data->cust_code;
                }
                if(isset($data->cust_cont_srno)){
                    $cust_cont_srno = $data->cust_cont_srno;
                }
                if(isset($data->transport_type)){
                    $transport_type = $data->transport_type;
                }
                if(isset($data->transport_no)){
                    $transport_no = $data->transport_no;
                }
                if(isset($data->driver)){
                    $driver = $data->driver;
                }
                if(isset($data->warehouse)){
                    $warehouse = $data->warehouse;
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
                if(isset($data->tax_amt)){
                    $tax_amt = $data->tax_amt;
                }
                if(isset($data->total_amt)){
                    $total_amt = $data->total_amt;
                }
                if(isset($data->remarks)){
                    $remarks = $data->remarks;
                }
                if(isset($data->item_list)){
                    $item_list = (array) $data->item_list;
                }
                if(isset($data->email)){
                    $email = $data->email;
                }
                $sql = "INSERT INTO `delivery_main` ( `series`, `date_added`, `timestamp`, `cust_code`, `cust_cont_srno`, `transport_type`, `transport_no`, `driver`, `warehouse`, `dcno`, `manual_dc`, `transport_amt`, `tax_amt`, `total_amt`, `payment_status`, `remarks`, `email`) VALUES
                ( '$series', '$date_added', '$date_added', '$cust_code', '$cust_cont_srno', '$transport_type', '$transport_no', '$driver', '$warehouse', '$dcno', '$manual_dc', $transport_amt, $tax_amt, $total_amt, '$payment_status', '$remarks', '$email') ";
                $result = $conn->query($sql);
                if ( $result) {
                    $last_id = $conn->insert_id;
                    $values = [];
                    
                    $sql1 = "INSERT INTO delivery_line (`line_no`, `dcno`, `item`, `rate`, `quantity`, `quantity_volume`, `line_total`, `line_tax_rate`, `line_tax`) VALUES";
                    foreach ($data->item_list as $item) {
                        $values[] = "('$item->line_no', '$last_id', '$item->item', $item->rate, $item->quantity, $item->quantity_volume, $item->line_total, $item->line_tax_rate, $item->line_tax )";
                    }
                    $sql1 .= implode(",", $values);
                    if ($conn->query($sql1) === TRUE) {
                        echo json_encode(array("message" => "Delivery entry added successfully"));
                    } else {
                        http_response_code(400);
                        echo json_encode(array("error" => "Error: " . $sql1 . "<br>" . $conn->error));
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(array("error" => "Error: " . $sql . "<br>" . $conn->error));
                }
            }else{
                http_response_code(400);
                echo json_encode(array("error" => "Invalid data. Required fields are missing or empty."));
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
            // Check if pagination flag is set to false
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

            $sql = "SELECT 
                        $delivery_fields, 
                        $transporter_fields, 
                        $customer_fields
                    FROM 
            pod.delivery_main 
            INNER JOIN 
            pod.transporter ON delivery_main.transport_no = transporter.code 
            INNER JOIN 
            pod.customer ON delivery_main.cust_code = customer.code";
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

            if ($result) {
                // $response = array(); // Initialize an array to store the result
                while ($row = $result->fetch_assoc()) {
                    $response["data"][] = array(
                        
                        "delivery" => createDeliveryArray($row, $delivery_main, 'delivery_main'),
                        "transporter" => createDeliveryArray($row, $transporter, 'transporter'),
                        "customer" => createDeliveryArray($row, $customer, 'customer')
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
                $customer_fields = getFieldsQuery($customer, 'customer');
                $delivery_line_fields = getFieldsQuery($delivery_line, 'delivery_line');

                $sql = "SELECT 
                            $delivery_fields, 
                            $transporter_fields, 
                            $customer_fields
                        FROM 
                            pod.delivery_main 
                        INNER JOIN 
                            pod.transporter ON delivery_main.transport_no = transporter.code 
                        INNER JOIN 
                            pod.customer ON delivery_main.cust_code = customer.code 
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
                        "customer" => createDeliveryArray($row, $customer, 'customer')
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
            $no = isset($data->no) ? $data->no : '';
            $custCode = isset($data->updatedDetailsMain->cust_code) ? $data->updatedDetailsMain->cust_code : '';
            $updatedDetailsMain = isset($data->updatedDetailsMain) ? $data->updatedDetailsMain : (object) array();
            $updatedDetailsLine = isset($data->updated_details_line) ? $data->updated_details_line : [];
            
            if (!empty($no) && !empty($updatedDetailsMain) && !empty($updatedDetailsLine)) {
                $data_query = [];
                $updateQueryMain = "UPDATE delivery_main SET ";
                foreach ($updatedDetailsMain as $key => $value) {
                    if ($key !== 'no') {
                        $updateQueryMain .= "`$key` = '$value', ";
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
                                $updateQueryLine .= "`$key` = '$value', ";
                            }
                        }
                        $updateQueryLine = rtrim($updateQueryLine, ', ');
                        $updateQueryLine .= " WHERE `srno` = '{$item->srno}' AND `no` = '$no'";
                        $data_query[] = $updateQueryLine;
                    } else {
                        $sql1 = "INSERT INTO delivery_line (`line_no`, `dcno`, `item`, `rate`, `quantity`, `quantity_volume`, `line_total`, `line_tax_rate`, `line_tax`) VALUES";
                        $insertQueryLine = $sql1 ."('$item->line_no', '$no', '$item->item', $item->rate, $item->quantity, $item->quantity_volume, $item->line_total, $item->line_tax_rate, $item->line_tax )";
                        $data_query[] = $insertQueryLine;
                    }
                }
                $queryString = implode("; ", $data_query);
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