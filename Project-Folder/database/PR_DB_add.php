<?php
session_start();

$table_name = 'purchase_requests';
$table_assoc = 'pr_item';
$date_needed = $_POST['dateNeeded'];
$status = $_POST['PRStatus'];
// $estimated_cost = $_POST['estimatedCost'];
$reason = $_POST['reason'];

// $PR_date_requested = 'current_timestamp()'; 
$user = $_SESSION['user']; //user data
$requested_by = $user['userID']; // Assuming this is passed from the form

$PR_id = isset($_POST['PRID']) ? $_POST['PRID'] : null;
$estimated_cost = 0.00;
$itemid = $_POST['itemID'];
$reQuant = $_POST['requestQuantity'];
$estCost = $_POST['productEstimatedCost'];
try {
    include('connect.php');

    if ($PR_id != null && $status = 'pending') { //can you implement something like this
        // Update existing purchase request without changing 'requestedBy'
        $command = "UPDATE $table_name SET PRDateRequested = current_timestamp(), dateNeeded = :dateNeeded, PRStatus = :PRStatus, estimatedCost = :estimatedCost, reason = :reason WHERE PRID = :PRID";
        $stmt = $conn->prepare($command); //check pr date requested
        $stmt->bindParam(':PRID', $PR_id);

        $commandItem = "UPDATE $table_assoc SET itemID = :item, requestQuantity = :req, estimatedCost = :est WHERE PRID = :PRID";
        $gftf = $conn->prepare($commandItem);
    } else {
        // Insert new purchase request
        $command = "INSERT INTO $table_name (requestedBy, PRDateRequested, dateNeeded, PRStatus, estimatedCost, reason) VALUES (:requestedBy, current_timestamp(), :dateNeeded, :PRStatus, :estimatedCost, :reason)";
        $stmt = $conn->prepare($command);
        $stmt->bindParam(':requestedBy', $requested_by); 
        

        $commandItem = "INSERT INTO $table_assoc (PRID, itemID, requestQuantity, estimatedCost) VALUES (:PRID, :item, :req, :est)";
        $gftf = $conn->prepare($commandItem);
    }

    $stmt->bindParam(':dateNeeded', $date_needed);
    $stmt->bindParam(':PRStatus', $status);
    $stmt->bindParam(':estimatedCost', $estimated_cost);
    $stmt->bindParam(':reason', $reason);
    $stmt->execute();

    $NEW = ($PR_id) ? $PR_id :$conn->lastInsertId(); //testing
    
    foreach ($itemid as $index => $itemId) {
        $estimated_cost += $estCost[$index];
        $gftf->execute([
            ':PRID' => $NEW,
            ':item' => $itemId,
            ':req' => $reQuant[$index],
            ':est' => $estCost[$index]
        ]); 
    }

    //updating the estimated cost
    $command = "UPDATE $table_name SET estimatedCost = :estimatedCost WHERE PRID = :PRID";
    $stmt = $conn->prepare($command);
    $stmt->bindParam(':PRID', $NEW);  
    $stmt->bindParam(':estimatedCost', $estimated_cost);
    $stmt->execute();

    $message = "Purchase request successfully " . ($PR_id ? "updated" : "added") . ".";
    $_SESSION['success_message'] = $message;
    header('location: ../PR.php');
} catch (PDOException $e) {
    // Handle any database errors
    $_SESSION['error_message'] = 'Error processing purchase request: ' . $e->getMessage();
    header('location: ../PR.php');
}
?>