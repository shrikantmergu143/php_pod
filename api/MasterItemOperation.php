<?php
    include( "config.php");
    include_once("RequestQuery.php");
    $data = json_decode(file_get_contents("php://input"));
    $currentDateTime = date("Y-m-d H:i:s");

    function validateData($data){
        if (
            isset($data->item_code) &&
            isset($data->product_name) &&
            isset($data->rate) &&
            isset($data->uom) &&
            isset($data->warranty) &&
            isset($data->type) &&
            !empty($data->type) &&
            !empty($data->uom) &&
            !empty($data->warranty) &&
            !empty($data->rate) &&
            !empty($data->product_name) &&
            !empty($data->item_code)
        ) {
            return true;
        } else {
            return false;
        }
    }
    if (isset($data->request_type)) {
        switch ($data->request_type) {
            case "ADD_MASTER_ITEM":{
                $item_code = "";
                $product_name = "";
                $uom = "";
                $warranty = "";
                $rate = 0;
                $pack_size = 0;
                $type = "";
                if(validateData($data)){

                    if (isset($data->item_code)) {
                        $item_code = $data->item_code;
                    }
                    if (isset($data->product_name)) {
                        $product_name = $data->product_name;
                    }
                    if (isset($data->uom)) {
                        $uom = $data->uom;
                    }
                    if (isset($data->warranty)) {
                        $warranty = $data->warranty;
                    }
                    if (isset($data->rate)) {
                        $rate = $data->rate;
                    }
                    if (isset($data->pack_size)) {
                        $pack_size = $data->pack_size;
                    }
                    if (isset($data->type)) {
                        $type = $data->type;
                    }
                    // Check if item_code already exists
                    $check_sql = "SELECT COUNT(*) as count FROM item_master WHERE item_code = '$item_code'";
                    $result = $conn->query($check_sql);
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                    
                        if ($count > 0) {
                            Failure("Error: Item with the same item_code already exists");
                        } else {
                            $sql = "INSERT INTO `item_master` (`item_code`, `product_name`, `uom`, `warranty`, `rate`, `pack_size`, `type`) VALUES ('$item_code', '$product_name', '$uom', '$warranty', $rate, $pack_size, '$type')";
                            if ($conn->query($sql) === TRUE) {
                                Success("Master item added successfully");
                            }else{
                                Failure("Error: " . $sql . "<br>" . $conn->error);
                            }
                        }
                    } else {
                        // Handle the case where the check query failed
                        Failure("Error checking item_code existence");
                    }
                }else {
                    Failure("Fields missing");
                }
            }
            break;
            case "GET_MASTER_LIST":{
                $recordsPerPage = 10;
                $pagination = true; // Default pagination is true
                if (isset($data->records_per_page)) {
                    $recordsPerPage = $data->records_per_page;
                }
                
                $page = isset($data->page) && is_numeric($data->page) ? $data->page : 1;
                $offset = ($page - 1) * $recordsPerPage;
                
                $search = '';
                $status = 'Active';
                if (isset($data->search)) {
                    $search = $data->search;
                }
                
                // Check if pagination flag is set to false
                if (isset($data->pagination) && $data->pagination === false) {
                    $pagination = false;
                }
                
                $totalRecords = 0;
                if ($pagination) {
                    $sqlCount = "SELECT COUNT(*) AS total_records FROM item_master WHERE 
                                (`item_code` LIKE '%$search%' OR 
                                `product_name` LIKE '%$search%' OR 
                                `type` LIKE '%$search%')";
                    $resultCount = $conn->query($sqlCount);
                    $totalRecords = $resultCount->fetch_assoc()['total_records'];
                }
                
                $sql = "SELECT `srno`, `item_code`, `product_name`, `uom`, `warranty`, `rate`, `pack_size`, `type` FROM item_master 
                        WHERE (`item_code` LIKE '%$search%' OR `product_name` LIKE '%$search%' OR `type` LIKE '%$search%')";
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
            case "UPDATE_MASTER_ITEM":{
                $itemCode = isset($data->item_code) ? $data->item_code : '';
                $id = isset($data->srno) ? $data->srno : '';
                $updatedDetails = isset($data->updated_details) ? $data->updated_details : array();
                if (!empty($itemCode) && !empty($id) && !empty($updatedDetails)) {
                    $checkQuery = "SELECT * FROM `item_master` WHERE `item_code` = '$itemCode' AND `srno` = '$id'";
                    $checkResult = $conn->query($checkQuery);
                    if ($checkResult && $checkResult->num_rows > 0) {
                        $updateQuery = "UPDATE `item_master` SET ";
                        foreach ($updatedDetails as $key => $value) {
                            if ($key !== 'srno' && $key !== 'item_code') {
                                $updateQuery .= "`$key` = '$value', ";
                            }
                        }
                        $updateQuery = rtrim($updateQuery, ', ');
                        $updateQuery .= " WHERE `srno` = '$id' AND `item_code` = '$itemCode'";
                        if ($conn->query($updateQuery) === TRUE) {
                            Success('Item updated successfully');
                        } else {
                            Failure("Error updating item: " . $conn->error);
                        }
                    } else {
                        Failure("Item with item_code: $itemCode and srno: $id not found");
                    }
                } else {
                    Failure("Item item_code, srno, or updated details missing");
                }
            }
            break;
            case "GET_MASTER_ID":{
                $sql = "SELECT * FROM item_master ORDER BY `srno` DESC LIMIT 1";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $subUnit = $result->fetch_assoc();
                    Success("Fetch customer details successfully", $subUnit);
                } else {
                    Failure("Customer not found", $result);
                }
            }
            break;
            default:
            echo json_encode(array("error" => "Invalid request type"));
        }
    }else {
        echo json_encode(array("error" => "No data received."));
    }
?>