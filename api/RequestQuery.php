<?php
    function GenerateAuthToken($data){
        $customKey = 'JASNDUASDIUskkkkkadmiortueritnreutvnrietvniutreutrieiuU67567JH';
        $payloadData = array(
            'id' => $data['id'],
            'email' => $data['email'],
            'access_type' => $data['access_type'],
            'exp' => time() + 43200
        );
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payloadData);
        $encodedHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $encodedPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $customKey, true);
        $encodedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $jwtToken = $encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature;
        return $jwtToken;
    }

    function AuthVerify($jwtToken){
        $customKey = 'JASNDUASDIUskkkkkadmiortueritnreutvnrietvniutreutrieiuU67567JH';
        $tokenParts = explode('.', $jwtToken);
        if (count($tokenParts) === 3) {
            $decodedHeader = base64_decode(strtr($tokenParts[0], '-_', '+/') . str_repeat('=', 3 - (3 + strlen($tokenParts[0])) % 4));
            $decodedPayload = base64_decode(strtr($tokenParts[1], '-_', '+/') . str_repeat('=', 3 - (3 + strlen($tokenParts[1])) % 4));
            $decodedSignature = base64_decode(strtr($tokenParts[2], '-_', '+/') . str_repeat('=', 3 - (3 + strlen($tokenParts[2])) % 4));
            $expectedSignature = hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $customKey, true);
            if (hash_equals($decodedSignature, $expectedSignature)) {
                $decodedHeaderArray = json_decode($decodedHeader, true);
                $decodedPayloadArray = json_decode($decodedPayload, true);
                
                // Validate token expiration
                if(isset($decodedPayloadArray['exp']) && $decodedPayloadArray['exp'] >= time()) {
                    return array("payload"=>$decodedPayloadArray, "status"=>true);
                } else {
                    return array("message"=>"Token expired", "status"=>false);
                }
            } else {
                return array("message"=>"Invalid JWT: Signatures don't match", "status"=>false);
            }
        } else {
            return array("message"=>"Invalid JWT: Signatures don't match", "status"=>false);
        }
    }
    function Success($message="", $data=null){
        $data_message = array();
        if(!empty($message)){
            $data_message['message'] = $message;
        }
        if(!empty($data)){
            $data_message['data'] = $data;
        }
        http_response_code(200);
        echo json_encode($data_message);
    }

    function Failure($message="", $data=null){
        $data_message = array();
        if(!empty($message)){
            $data_message['error'] = $message;
        }
        if(!empty($data)){
            $data_message['data'] = $data;
        }
        http_response_code(400);
        echo json_encode($data_message);
    }

?>