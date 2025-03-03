<?php

include("db.php");
include("../access-token.php");

// Function to send email
function sendMail($to, $subject, $message) {
    $headers = "From: no-reply@example.com\r\n";
    $headers .= "Reply-To: no-reply@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Function to map form fields to database fields
function mapFormFields($formData) {
    return [
        'Name' => $formData['name'] ?? '',
        'Address' => $formData['address'] ?? '',
        'Contact' => $formData['contact'] ?? '',
        'Email' => $formData['email'] ?? '',
        'CTP' => $formData['ctp'] ?? '',
        'Billing' => $formData['billing'] ?? '',
        'Plate' => $formData['plate'] ?? '',
        'VIN' => $formData['vin'] ?? '',
        'Customer' => $formData['customer'] ?? '',
        'Vehicle' => $formData['vehicle'] ?? '',
        'ManufactureYear' => $formData['manufacture-year'] ?? '',
        'Make' => $formData['make'] ?? '',
        'Shape' => $formData['shape'] ?? '',
        'GVM' => $formData['gvm'] ?? '',
        'Usage' => $formData['gvm'] ?? '',
        'Postcode' => $formData['postcode'] ?? '',
        'Suburb' => $formData['suburb'] ?? '',
        'CustomerType' => $formData['customer-type'] ?? '',
        'InsuranceDuration' => $formData['customer-type'] ?? '',
        'TaxCredit' => $formData['tax-credit'] ?? ''
    ];
}

// Function to insert data into the database
function insertDataIntoDatabase($mappedData, $pdo) {
    try {
        $sql = "INSERT INTO earthmoving(name, address, contact, email, ctp, billing, plate, vin, customer, vehicle, manufacture_year, make, shape, gvm, usage, postcode, suburb, customer_type, insurance_duration, tax_credit)
                VALUES (:name, :address, :contact, :email, :ctp, :billing, :plate, :vin, :customer, :vehicle, :manufacture_year, :make, :shape, :gvm, :usage, :postcode, :suburb, :customer_type, :insurance_duration, :tax_credit)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $mappedData['Name'],
            ':address' => $mappedData['Address'],
            ':contact' => $mappedData['Contact'],
            ':email' => $mappedData['Email'],
            ':ctp' => $mappedData['CTP'],
            ':billing' => $mappedData['Billing'],
            ':plate' => $mappedData['Plate'],
            ':vin' => $mappedData['VIN'],
            ':customer' => $mappedData['Customer'],
            ':vehicle' => $mappedData['Vehicle'],
            ':manufacture_year' => $mappedData['ManufactureYear'],
            ':make' => $mappedData['Make'],
            ':shape' => $mappedData['Shape'],
            ':gvm' => $mappedData['GVM'],
            ':usage' => $mappedData['Usage'],
            ':postcode' => $mappedData['Postcode'],
            ':suburb' => $mappedData['Suburb'],
            ':customer_type' => $mappedData['CustomerType'],
            ':insurance_duration' => $mappedData['InsuranceDuration'],
            ':tax_credit' => $mappedData['TaxCredit']
        ]);

        return true;
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

// Function to add record to Zoho
function addRecordToZoho($mappedData, $pdo) {
    getAccessToken($pdo);
    $accessToken = $_SESSION['access_token'] ?? null;

    if (!$accessToken) {
        error_log("Error: Missing Zoho access token.");
        return false;
    }

    $module = 'Leads';
    $apiUrl = "https://www.zohoapis.com.au/crm/v2/$module";
    $data = ['data' => [$mappedData]];
    $jsonData = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Zoho-oauthtoken $accessToken",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201) {
        return true;
    } else {
        error_log("Zoho API Error ({$httpCode}): " . $response);
        return false;
    }
}

// Populate mappedData variable
$formData = $_POST; // Assuming form data is sent via POST
$mappedData = mapFormFields($formData);

// Check if data was inserted successfully and send email
if (insertDataIntoDatabase($mappedData, $pdo)) {
    // Add record to Zoho
    if (addRecordToZoho($mappedData, $pdo)) {
        // Prepare email content
        $to = $mappedData['Email'];
        $subject = "Confirmation of Your Submission";
        $message = "<h1>Thank you for your submission!</h1><p>Your data has been successfully recorded.</p>";
        
        // Send confirmation email
        if (sendMail($to, $subject, $message)) {
            echo "Data sent to database, Zoho, and confirmation email sent.";
        } else {
            echo "Data sent to database and Zoho, but failed to send confirmation email.";
        }
    } else {
        echo "Data sent to database, but failed to add record to Zoho.";
    }
} else {
    echo "Data send failed";
}

?>
