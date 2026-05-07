<?php
require_once '../../config/connectDB.php';

$conn = (new ConnectDB())->connection();

function getAllCities($conn) {
    $sql = "SELECT * FROM Cities ORDER BY City_ID DESC";
    return $conn->query($sql);
}


function getCityById($conn, $id) {
    $sql = "SELECT * FROM Cities WHERE City_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function createCity($conn, $name, $region) {
    $sql = "INSERT INTO Cities (Name, Region) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $region);
    return $stmt->execute();
}

function updateCity($conn, $id, $name, $region) {
    $sql = "UPDATE Cities SET Name = ?, Region = ? WHERE City_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $region, $id);
    return $stmt->execute();
}

function deleteCity($conn, $id) {
    $sql = "DELETE FROM Cities WHERE City_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function searchCities($conn, $keyword) {
    $keyword = "%$keyword%";
    $sql = "SELECT * FROM Cities WHERE Name LIKE ? OR Region LIKE ? ORDER BY City_ID DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $keyword, $keyword);
    $stmt->execute();
    return $stmt->get_result();
}
function getCities($conn) {
    if (isset($_GET['search'])) {
        $keyword = $_GET['keyword'] ?? '';
        $result = searchCities($conn, $keyword);
    } else {
        $result = getAllCities($conn);
    }

    $cities = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cities[] = $row;
        }
    }
    return $cities;
}
?>