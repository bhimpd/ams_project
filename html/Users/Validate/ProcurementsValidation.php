<?php

namespace ProcurementValidator;

class ProcurementsValidation
{
    public static function validateProcurement($decodedData, $validateKeys)
    {
        $procurementValidate = [];
        $productsData = $decodedData['products'] ?? [];

        foreach ($validateKeys as $key => $rules) {
            if (!array_key_exists($key, $decodedData)) {
                if (in_array('empty', $rules)) {
                    $procurementValidate[$key][] = "{$key} is a required field";
                }
                continue;
            }

            switch ($key) {
                case 'status':
                    if (!in_array($decodedData[$key], ['pending', 'approved'])) {
                        $procurementValidate[$key][] = "Only 'pending' or 'approved' is accepted for status";
                    }
                    break;
                case 'request_urgency':
                    if (!in_array($decodedData[$key], ['low', 'medium', 'high', 'urgent'])) {
                        $procurementValidate[$key][] = "Only 'low', 'medium', 'high' or 'urgent' is accepted for request_urgency";
                    }
                    break;
                default:
                    break;
            }
        }

        foreach ($productsData as $product) {
            foreach ($validateKeys as $key => $rules) {
                if (empty($product[$key])) {
                    if (in_array('required', $rules)) {
                        $procurementValidate[$key][] = "{$key} is a required field";
                    }
                    continue;
                }

                switch ($key) {
                    case 'estimated_price':
                        if (!is_numeric($product[$key])) {
                            $procurementValidate[$key][] = "Only numeric value is accepted for {$key}";
                        }
                        break;
                    case 'brand':
                    case 'product_name':
                        if (preg_match('/^\d+$/', $product[$key])) {
                            $procurementValidate[$key][] = "{$key} cannot contain only numeric characters";
                        }

                        // Check alphanumeric and special characters, and length
                        if (!preg_match('/^[a-zA-Z0-9\s!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/]{2,16}$/', $product[$key])) {
                            $procurementValidate[$key][] = "{$key} can accept alphabets, alphanumeric characters, and specific special characters. Minimum length: 2, Maximum length: 16";
                        }
                        break;
                    case 'link':
                        // Validate the URL format
                        if (!preg_match('/^www\.[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]{2,}$/', $product[$key])) {
                            $procurementValidate[$key][] = "Invalid {$key} format";
                        }
                        break;
                    default:
                        break;
                }
            }
        }


        if (!empty($procurementValidate)) {
            return [
                "status" => false,
                "message" => $procurementValidate
            ];
        }

        return [
            "status" => true
        ];
    }
}
