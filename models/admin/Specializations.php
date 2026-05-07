<?php
require_once('../../config/connectDB.php');

$conn = (new ConnectDB())->connection();

function createSpecialization($conn, $name, $desc) {
    $sql = "INSERT INTO Specialization (Name, Description) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $desc);
    return $stmt->execute();
}

function updateSpecialization($conn, $id, $name, $desc) {
    $sql = "UPDATE Specialization SET Name = ?, Description = ? WHERE Specialization_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $desc, $id);
    return $stmt->execute();
}

function deleteSpecialization($conn, $id) {

    $sqlDeleteMap = "DELETE FROM Doctor_Specializations WHERE Specialization_ID = ?";
    $stmtMap = $conn->prepare($sqlDeleteMap);
    $stmtMap->bind_param("i", $id);
    $stmtMap->execute();

    $sql = "DELETE FROM Specialization WHERE Specialization_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function getAllSpecializations($conn) {
    $sql = "SELECT * FROM Specialization ORDER BY Name ASC";
    $result = $conn->query($sql);

    $specializations = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $specializations[] = $row;
        }
    }
    return $specializations; 
}

function searchSpecializations($conn, $keyword) {
    $keyword = "%$keyword%";
    $sql = "SELECT * FROM Specialization
            WHERE Name LIKE ? OR Description LIKE ?
            ORDER BY Name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $keyword, $keyword);
    $stmt->execute();

    $result = $stmt->get_result();
    $specializations = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $specializations[] = $row;
        }
    }
    return $specializations;
}

function getSpecializationById($conn, $id) {
    $sql = "SELECT * FROM Specialization WHERE Specialization_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getSpecializations($conn) {
    if (!empty($_GET['keyword'])) {
        return searchSpecializations($conn, $_GET['keyword']);
    }
    return getAllSpecializations($conn);
}

function getDoctorSpecializations($conn, $doctor_id) {
    $sql = "SELECT Specialization_ID FROM Doctor_Specializations WHERE Doctor_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $specs = [];
    while ($row = $result->fetch_assoc()) {
        $specs[] = $row['Specialization_ID'];
    }
    return $specs;
}