<?php

include("db.php");
include("../access-token.php");

// 1.send data to the database 
function sendMail($to, $subject, $message) {
    $headers = "From: no-reply@example.com\r\n";
    $headers .= "Reply-To: no-reply@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
} // Added closing brace for function


// 2. send data to zoho 
// 3. send data to mail 


//function for mapping form fields to zoho crm fields

function mapFormFields($formData) {
    return [
        'Last_Name'=>$formData['name'] ?? '',
        'Email'=>$formData['email'] ??'',
        'Phone'=>$formData['phone'] ??'',
        'Suburb'=>$formData['state'] ??'',
        'Zip_Code'=>$formData['zip'] ??''
    ];
}

//function for inserting data into database
function insertDataIntoDatabase($mappedData, $pdo) {
    try {
        $sql = "INSERT INTO homepage2 (name, email, phone, zip, state)
                VALUES (:name, :email, :phone, :zip, :state)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'  => $mappedData['Last_Name'],
            ':email' => $mappedData['Email'],
            ':phone' => $mappedData['Phone'],
            ':zip'   => $mappedData['Zip_Code'], // Corrected key
            ':state' => $mappedData['Suburb']
        ]);

        return true;
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

// Populate mappedData variable
$formData = $_POST; // Assuming form data is sent via POST
$mappedData = mapFormFields($formData);


//sendig data to zohocrm
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

// Check if data was inserted successfully and send email
if (insertDataIntoDatabase($mappedData, $pdo)) {
    // Prepare email content
    $to = $mappedData['Email'];
    $subject = "Confirmation of Your Submission";
    $message = "<h1>Thank you for your submission!</h1><p>Your data has been successfully recorded.</p>";
    
    // Send confirmation email
    if (sendMail($to, $subject, $message)) {
        echo "Data sent to database and confirmation email sent.";
    } else {
        echo "Data sent to database, but failed to send confirmation email.";
    }
} else {
    echo "Data send failed";
}

$message = "
<html>
            <head>
              <title>Insurance Inquiry</title>
              <style>
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background-color: #f2f2f2; }
              </style>
            </head>
            <body>
              <h2>New Insurance Inquiry</h2>
              <table>
<tr><th>Full Name</th><td>{$mappedData['Last_Name']}</td></tr>
<tr><th>Email</th><td>{$mappedData['Email']}</td></tr>
<tr><th>Phone Number</th><td>{$mappedData['Phone']}</td></tr>
<tr><th>City</th><td>{$mappedData['Suburb']}</td></tr>
<tr><th>Zip Code</th><td>{$mappedData['Zip_Code']}</td></tr>
<tr><th>Message</th><td>{$userMessage}</td></tr>
              </table>
            </body>
            </html>
"; // Added missing semicolon

?>
