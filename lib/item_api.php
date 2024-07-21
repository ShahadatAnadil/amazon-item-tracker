<?php

function fetch_data($asin) {
    $data = ["asin" => $asin, "country" => "US"];
    $endpoint = "https://real-time-amazon-data.p.rapidapi.com/product-details";
    $isRapidAPI = true;
    $rapidAPIHost = "real-time-amazon-data.p.rapidapi.com";

    $result = get($endpoint, "Product_Details_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }

    if (isset($result["data"])) {
        $quote = $result["data"];
        
        $params = [];

        foreach ($quote as $key => $value) {
            $params[$key] = $value;
        }

        return [
            "params" => $params
        ];
    }
    
    return $result;
}
?>