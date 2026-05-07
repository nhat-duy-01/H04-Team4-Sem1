<?php
require_once '../../models/admin/Cities.php';

function handleCitiesRequest($conn) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnAdd'])) {
        $name = $_POST['name'];
        $region = $_POST['region'];
        createCity($conn, $name, $region);
        header("Location: ./manage_cities.php");
        exit();
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnEdit'])) {
        $id = (int)$_POST['city_id'];
        $name = $_POST['name'];
        $region = $_POST['region'];
        updateCity($conn, $id, $name, $region);
        header("Location: ./manage_cities.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnDelete'])) {
        $id = (int)$_POST['city_id'];
        deleteCity($conn, $id);
        header("Location: ./manage_cities.php");
        exit();
    }


    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
        $id = (int)$_GET['id'];
        return getCityById($conn, $id); 
    }
    

    return null; 
}